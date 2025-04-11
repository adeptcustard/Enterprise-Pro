<?php
define('RUNNING_TESTS', true);
/**
 * Test class for fetch_tasks_admin.php functionality
 * 
 * Tests admin task fetching including:
 * - Admin role verification
 * - Complete task data structure
 * - Associated users and actions
 * - Error handling for unauthorized access
 */
require_once __DIR__ . '/TestAssert.php';
include_once __DIR__ . '/../db_connect.php'; // Defines $pdo

class FetchTasksAdminTest
{
    private $testAssert;
    private $pdo;
    public $passCount = 0;
    public $failCount = 0;
    private $testAdminId;
    private $testTaskId;

    public function __construct()
    {
        global $pdo;
        $this->pdo = $pdo;
        $this->testAssert = new TestAssert();
    }

    /**
     * Set up test environment
     */
    public function setUp()
    {
        global $failSetting;
        $failSetting = 0;

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }

        // Clear all session data
        $_SESSION = [];

        // Set default request method
        $_SERVER['REQUEST_METHOD'] = 'POST';

        // Initialize input array with admin login values
        $GLOBALS['input'] = [
            'email' => 'admin@yhrocu.uk',
            'password' => 'Admin@123'
        ];
        echo "Admin login should redirect to 'html/verify_otp.html' see below ⬇️\n";
        require '../login.php';

        try {
            // Verify admin login
            if (!isset($_SESSION['user_id'])) {
                die("❌ Failed to log in test admin user");
            }

            $this->testAdminId = $_SESSION['user_id'];
            $_SESSION['role'] = 'Admin';

            // Create test task
            $stmt = $this->pdo->prepare(
                "INSERT INTO tasks (title, description, status, assigned_to) 
                 VALUES ('Admin Test Task', 'Test Description', 'Pending', ?)
                 RETURNING id"
            );
            $stmt->execute([$this->testAdminId]);
            $this->testTaskId = $stmt->fetchColumn();

            // Assign task
            $stmt = $this->pdo->prepare(
                "INSERT INTO task_assignments (task_id, user_id) 
                 VALUES (?, ?)"
            );
            $stmt->execute([$this->testTaskId, $this->testAdminId]);

            // Create test action
            $stmt = $this->pdo->prepare(
                "INSERT INTO task_actions (task_id, action_description) 
                 VALUES (?, 'Admin Test Action')"
            );
            $stmt->execute([$this->testTaskId]);

        } catch (PDOException $e) {
            die("❌ Test setup failed: " . $e->getMessage());
        }
    }

    /**
     * Clean up test environment
     */
    public function tearDown()
    {
        try {
            // Clean up test data
            $this->pdo->exec("DELETE FROM task_actions WHERE task_id = $this->testTaskId");
            $this->pdo->exec("DELETE FROM task_assignments WHERE task_id = $this->testTaskId");
            $this->pdo->exec("DELETE FROM tasks WHERE id = $this->testTaskId");
        } catch (PDOException $e) {
            error_log("Cleanup error: " . $e->getMessage());
        }

        session_unset();
        session_destroy();
        $_SESSION = [];
    }

    /**
     * Test successful task fetch with admin session
     */
    public function testSuccessfulAdminTaskFetch()
    {
        ob_start();
        include __DIR__ . '/../fetch_tasks_admin.php';
        $output = json_decode(ob_get_clean(), true);
        echo "\n";

        // Test basic response
        if (
            $this->testAssert->assertTrue(
                $output['success'],
                "Valid admin request should return success"
            )
        ) {
            $this->passCount++;
        } else {
            $this->failCount++;
        }

        // Test tasks array exists
        if (
            $this->testAssert->assertNotNull(
                $output['tasks'] ?? null,
                "Response should contain tasks array"
            )
        ) {
            $this->passCount++;
        } else {
            $this->failCount++;
        }

        // Test task structure
        if (!empty($output['tasks'])) {
            $task = $output['tasks'][0];

            // Check required fields
            $requiredFields = ['id', 'title', 'description', 'status', 'deadline', 'created_at'];
            foreach ($requiredFields as $field) {
                if (
                    $this->testAssert->assertNotNull(
                        $task[$field] ?? null,
                        "Task should have $field field"
                    )
                ) {
                    $this->passCount++;
                } else {
                    $this->failCount++;
                }
            }

            // Check nested structures
            if (
                $this->testAssert->assertNotNull(
                    $task['primary_user'] ?? null,
                    "Task should have primary_user"
                )
            ) {
                $this->passCount++;
            } else {
                $this->failCount++;
            }

            if (
                $this->testAssert->assertNotNull(
                    count($task['additional_users'] ?? []),
                    "additional_users should be an array"
                )
            ) {
                $this->passCount++;
            } else {
                $this->failCount++;
            }

            if (
                $this->testAssert->assertNotNull(
                    count($task['actions'] ?? []),
                    "actions should be an array"
                )
            ) {
                $this->passCount++;
            } else {
                $this->failCount++;
            }
        }
    }

    /**
     * Test unauthorized access (non-admin role)
     */
    public function testUnauthorizedAccess()
    {
        global $failSetting;
        $failSetting = 1;
        $_SESSION['role'] = 'User'; // Change role to non-admin

        ob_start();
        include __DIR__ . '/../fetch_tasks_admin.php';
        $output = json_decode(ob_get_clean(), true);
        echo "\n";

        if (
            $this->testAssert->assertFalse(
                $output['success'],
                "Non-admin request should fail"
            )
        ) {
            $this->passCount++;
        } else {
            $this->failCount++;
        }

        if (
            $this->testAssert->assertEquals(
                "Unauthorized",
                $output['message'],
                "Should return correct error message"
            )
        ) {
            $this->passCount++;
        } else {
            $this->failCount++;
        }
    }

    /**
     * Test unauthenticated access
     */
    public function testUnauthenticatedAccess()
    {
        global $failSetting;
        $failSetting = 1;
        unset($_SESSION['user_id']);
        unset($_SESSION['role']);

        ob_start();
        include __DIR__ . '/../fetch_tasks_admin.php';
        $output = json_decode(ob_get_clean(), true);
        echo "\n";

        if (
            $this->testAssert->assertFalse(
                $output['success'],
                "Unauthenticated request should fail"
            )
        ) {
            $this->passCount++;
        } else {
            $this->failCount++;
        }

        if (
            $this->testAssert->assertEquals(
                "Unauthorized",
                $output['message'],
                "Should return correct error message"
            )
        ) {
            $this->passCount++;
        } else {
            $this->failCount++;
        }
    }

    /**
     * Run all tests
     */
    public function runAllTests()
    {
        echo "=== Running Fetch Tasks Admin Tests ===\n";

        $this->setUp();
        $this->testSuccessfulAdminTaskFetch();
        $this->tearDown();

        $this->setUp();
        $this->testUnauthorizedAccess();
        $this->tearDown();

        $this->setUp();
        $this->testUnauthenticatedAccess();
        $this->tearDown();

        echo "=== Tests Complete ===\n";
    }
}

// Execute tests if run directly
if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    $test = new FetchTasksAdminTest();
    $test->runAllTests();
}