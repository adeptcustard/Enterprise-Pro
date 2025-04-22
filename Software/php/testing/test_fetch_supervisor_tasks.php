<?php
define('RUNNING_TESTS', true);
/**
 * Test class for fetch_supervisor_tasks.php functionality
 * 
 * Tests supervisor task fetching including:
 * - Supervisor role verification
 * - Proper task data structure for both my_tasks and all_tasks
 * - Associated users and actions
 * - Error handling for unauthorized access
 */
require_once __DIR__ . '/TestAssert.php';
include_once __DIR__ . '/../db_connect.php'; // Defines $pdo

class FetchSupervisorTasksTest
{
    private $testAssert;
    private $pdo;
    public $passCount = 0;
    public $failCount = 0;
    private $testUserId;
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
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }

        // Clear all session data
        $_SESSION = [];

        // Set default request method
        $_SERVER['REQUEST_METHOD'] = 'POST';

        // Initialize input array with default login values
        $GLOBALS['input'] = [
            'email' => 'supervisor1@yhrocu.uk',
            'password' => 'Super@123'
        ];
        echo "Login should redirect to 'html/verify_otp.html' see below ⬇️\n";
        require '../login.php';

        try {
            // Set supervisor role in session
            $_SESSION['role'] = 'Supervisor';
            $this->testUserId = $_SESSION['user_id'];

            // Create test task owned by supervisor
            $stmt = $this->pdo->prepare(
                "INSERT INTO tasks (title, description, status, owner) 
                 VALUES ('Supervisor Test Task', 'Test Description', 'Pending', ?)
                 RETURNING id"
            );
            $stmt->execute([$this->testUserId]);
            $this->testTaskId = $stmt->fetchColumn();

            // Create another test task not owned by supervisor
            $stmt = $this->pdo->prepare(
                "INSERT INTO tasks (title, description, status, owner) 
                 VALUES ('Other User Task', 'Other Description', 'Pending', ?)
                 RETURNING id"
            );
            $stmt->execute([$this->testUserId + 1]); // Different owner

            // Assign tasks
            $stmt = $this->pdo->prepare(
                "INSERT INTO task_assignments (task_id, user_id) 
                 VALUES (?, ?)"
            );
            $stmt->execute([$this->testTaskId, $this->testUserId]);

            // Create test actions
            $stmt = $this->pdo->prepare(
                "INSERT INTO task_actions (task_id, action_description) 
                 VALUES (?, 'Supervisor Test Action')"
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
            $stmt = $this->pdo->prepare(
                "DELETE FROM tasks WHERE title = 'Test Task'"
            );
            $stmt->execute();
            $stmt = $this->pdo->prepare(
                "DELETE FROM users WHERE email = 'testuser@example.com'"
            );
            $stmt->execute();
            // Clean up test data
            $this->pdo->exec("DELETE FROM task_actions WHERE action_description = 'Admin Test Action'");
            $this->pdo->exec("DELETE FROM task_assignments WHERE task_id = $this->testTaskId");
            $this->pdo->exec("DELETE FROM tasks WHERE title = 'Admin Test Task'");
            $this->pdo->exec("DELETE FROM tasks WHERE title = 'Other User Task'");
        } catch (PDOException $e) {
            error_log("Cleanup error: " . $e->getMessage());
        }

        session_unset();
        session_destroy();
        $_SESSION = [];
    }

    /**
     * Test successful task fetch with valid supervisor session
     */
    public function testSuccessfulSupervisorTaskFetch()
    {
        ob_start();
        include __DIR__ . '/../fetch_supervisor_tasks.php';
        $output = json_decode(ob_get_clean(), true);
        echo "\n";

        // Test basic response
        if (
            $this->testAssert->assertTrue(
                $output['success'],
                "Valid supervisor request should return success"
            )
        ) {
            $this->passCount++;
        } else {
            $this->failCount++;
        }

        // Test both task arrays exist
        if (
            $this->testAssert->assertNotNull(
                $output['my_tasks'] ?? null,
                "Response should contain my_tasks array"
            )
        ) {
            $this->passCount++;
        } else {
            $this->failCount++;
        }

        if (
            $this->testAssert->assertNotNull(
                $output['all_tasks'] ?? null,
                "Response should contain all_tasks array"
            )
        ) {
            $this->passCount++;
        } else {
            $this->failCount++;
        }

        // Test my_tasks structure
        if (!empty($output['my_tasks'])) {
            $task = $output['my_tasks'][0];

            if (
                $this->testAssert->assertNotNull(
                    $task['id'] ?? null,
                    "Task should have id field"
                )
            ) {
                $this->passCount++;
            } else {
                $this->failCount++;
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
                    count($task['assigned_users'] ?? []),
                    "assigned_users should be an array"
                )
            ) {
                $this->passCount++;
            } else {
                $this->failCount++;
            }
        }
    }

    /**
     * Test unauthorized access (non-supervisor role)
     */
    public function testUnauthorizedAccess()
    {
        // Change role to non-supervisor
        global $failSetting;
        $failSetting = 1;
        $_SESSION['role'] = 'User';

        ob_start();
        include __DIR__ . '/../fetch_supervisor_tasks.php';
        $output = json_decode(ob_get_clean(), true);
        echo "\n";

        if (
            $this->testAssert->assertFalse(
                $output['success'],
                "Non-supervisor request should fail"
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
        unset($_SESSION['user_id']);
        unset($_SESSION['role']);

        ob_start();
        include __DIR__ . '/../fetch_supervisor_tasks.php';
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
        echo "=== Running Fetch Supervisor Tasks Tests ===\n";

        $this->setUp();
        $this->testSuccessfulSupervisorTaskFetch();
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
    $test = new FetchSupervisorTasksTest();
    $test->runAllTests();
}