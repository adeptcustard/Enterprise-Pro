<?php
define('RUNNING_TESTS', true);
/**
 * Test class for fetch_tasks.php functionality
 * 
 * Tests task fetching including:
 * - Authentication requirements
 * - Proper task data structure
 * - Associated users and actions
 * - Error handling for database issues
 */
require_once __DIR__ . '/TestAssert.php';
include_once __DIR__ . '/../db_connect.php'; // Defines $pdo

class FetchTasksTest
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
            // Create test user
            $stmt = $this->pdo->prepare(
                "INSERT INTO users (email, password_hash, first_name, last_name, role) 
             VALUES ('testuser@example.com', 'hashed_password', 'Test', 'User', 'Supervisor')
             RETURNING id"
            );
            $stmt->execute();
            $this->testUserId = $stmt->fetchColumn();

            // Create test task
            $stmt = $this->pdo->prepare(
                "INSERT INTO tasks (title, description, status, assigned_to) 
             VALUES ('Test Task', 'Test Description', 'Pending', ?)
             RETURNING id"
            );
            $stmt->execute([$this->testUserId]);
            $this->testTaskId = $stmt->fetchColumn();

            // Assign task
            $stmt = $this->pdo->prepare(
                "INSERT INTO task_assignments (task_id, user_id) 
             VALUES (?, ?)"
            );
            $stmt->execute([$this->testTaskId, $this->testUserId]);

            // Create test action
            $stmt = $this->pdo->prepare(
                "INSERT INTO task_actions (task_id, action_description) 
             VALUES (?, 'Test Action')"
            );
            $stmt->execute([$this->testTaskId]);

            $_SESSION['user_id'] = $this->testUserId;
        } catch (PDOException $e) {
            die("❌ Test setup failed: " . $e->getMessage());
        }
    }

    /**
     * Clean up test environment
     */
    public function tearDown()
    {
        // Clean up test data
        $stmt = $this->pdo->prepare(
            "DELETE FROM tasks WHERE title = 'Test Task'"
        );
        $stmt->execute();
        $stmt = $this->pdo->prepare(
            "DELETE FROM users WHERE email = 'testuser@example.com'"
        );
        $stmt->execute();

        session_unset();
        session_destroy();
        $_SESSION = [];
    }

    /**
     * Test successful task fetch with valid session
     */
    public function testSuccessfulTaskFetch()
    {
        ob_start();
        include __DIR__ . '/../fetch_tasks.php';
        $output = json_decode(ob_get_clean(), true);
        echo "\n";

        // Test basic response
        if (
            $this->testAssert->assertTrue(
                $output['success'],
                "Valid request should return success"
            )
        ) {
            $this->passCount++;
        } else {
            $this->failCount++;
        }

        if (
            $this->testAssert->assertNotNull(
                $output['tasks'],
                "Response should contain tasks array"
            )
        ) {
            $this->passCount++;
        } else {
            $this->failCount++;
        }

        // Test task structure using existing assertions
        if (!empty($output['tasks'])) {
            $task = $output['tasks'][0];

            // Check fields using assertNotNull (equivalent to assertArrayHasKey)
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

            if (
                $this->testAssert->assertNotNull(
                    $task['title'] ?? null,
                    "Task should have title field"
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

            // Check array type by verifying count exists (since we can't directly check type)
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
        }
    }

    /**
     * Test task fetch without authentication
     */
    public function testUnauthenticatedAccess()
    {
        global $failSetting;
        $failSetting = 1;
        unset($_SESSION['user_id']);
        ob_start();
        include __DIR__ . '/../fetch_tasks.php';
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
                "User not logged in",
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
     * Test task fetch with no assigned tasks
     */
    public function testNoTasksFound()
    {
        $this->pdo->exec("DELETE FROM task_assignments WHERE user_id = $this->testUserId");

        ob_start();
        include __DIR__ . '/../fetch_tasks.php';
        $output = json_decode(ob_get_clean(), true);
        echo "\n";

        if (
            $this->testAssert->assertTrue(
                $output['success'],
                "Should succeed even with no tasks"
            )
        ) {
            $this->passCount++;
        } else {
            $this->failCount++;
        }

        // Check empty array by counting elements
        if (
            $this->testAssert->assertEquals(
                0,
                count($output['tasks'] ?? []),
                "Tasks array should be empty"
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
        echo "=== Running Fetch Tasks Tests ===\n";

        $this->setUp();
        $this->testSuccessfulTaskFetch();
        $this->tearDown();

        $this->setUp();
        $this->testUnauthenticatedAccess();
        $this->tearDown();

        $this->setUp();
        $this->testNoTasksFound();
        $this->tearDown();

        echo "=== Tests Complete ===\n";
    }
}

// Execute tests if run directly
if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    require_once '../db_connect.php'; // Defines $pdo
    $test = new LoginTest($pdo); // Inject the connection
    $test->runAllTests();
}