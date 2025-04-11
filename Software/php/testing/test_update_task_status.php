<?php
define('RUNNING_TESTS', true);
/**
 * Test class for update_task_status.php functionality
 * 
 * Tests task status updates including:
 * - Authentication requirements
 * - Input validation
 * - Status transition logic
 * - Role-based permissions
 * - Database operations
 * - Error handling
 */
require_once __DIR__ . '/TestAssert.php';

class UpdateTaskStatusTest
{
    /** @var TestAssert $testAssert Instance of assertion utility class */
    private $testAssert;

    /** @var PDO $pdo Database connection */
    private $pdo;

    /** @var int $testUserId ID of test user created for testing */
    private $testUserId;

    /** @var int $testTaskId ID of test task created for testing */
    private $testTaskId;

    /** @var int $passCount Counter for passed assertions */
    public $passCount = 0;

    /** @var int $failCount Counter for failed assertions */
    public $failCount = 0;

    /**
     * Constructor - Initializes the test class
     * 
     * @param PDO $pdo Database connection
     */
    public function __construct(PDO $pdo)
    {
        $this->testAssert = new TestAssert();
        $this->pdo = $pdo;
    }

    /**
     * Set up test environment
     * - Creates test user
     * - Creates test task
     * - Initializes session
     */
    public function setUp()
    {
        global $failSetting;
        $failSetting = 0; // Reset fail setting

        // Clean up any existing session
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }

        $_SESSION = [];
        $_SERVER['REQUEST_METHOD'] = 'POST';

        // Create a test user
        $stmt = $this->pdo->prepare(
            "INSERT INTO users (email, password_hash, role, first_name, last_name) 
             VALUES ('testuser@yhrocu.uk', :hash, 'User', 'Test', 'User') 
             RETURNING id"
        );
        $hash = password_hash('Test@123', PASSWORD_DEFAULT);
        $stmt->execute([':hash' => $hash]);
        $this->testUserId = $stmt->fetchColumn();

        // Create a test task
        $stmt = $this->pdo->prepare(
            "INSERT INTO tasks (title, description, owner, status, last_updated_by) 
             VALUES ('Test Task', 'Test Description', :user_id, 'Pending', :user_id) 
             RETURNING id"
        );
        $stmt->execute([':user_id' => $this->testUserId]);
        $this->testTaskId = $stmt->fetchColumn();

        // Start session with test user
        session_start();
        $_SESSION['user_id'] = $this->testUserId;
        $_SESSION['role'] = 'User';
    }

    /**
     * Clean up test environment
     * - Removes test data
     * - Clears session
     */
    public function tearDown()
    {
        // Remove test task actions if any
        $this->pdo->prepare("DELETE FROM task_actions WHERE task_id = ?")
            ->execute([$this->testTaskId]);

        // Remove task log entries
        $this->pdo->prepare("DELETE FROM task_log WHERE task_id = ?")
            ->execute([$this->testTaskId]);

        // Remove the test task
        $this->pdo->prepare("DELETE FROM tasks WHERE id = ?")
            ->execute([$this->testTaskId]);

        $this->pdo->prepare("DELETE FROM tasks WHERE title = 'Test Task'")
            ->execute();

        // Remove the test user
        global $pdo;
        $stmt = $pdo->prepare("DELETE FROM users WHERE first_name = 'Test'");
        $stmt->execute();
        $pdo->prepare("DELETE FROM users WHERE email = 'testuser@yhrocu.uk'");
        $stmt->execute();
        $pdo->prepare("DELETE FROM tasks WHERE title = 'Test Task'");
        $stmt->execute();
        $pdo->prepare("DELETE FROM tasks WHERE title = 'Supervisor Test Task'");
        $stmt->execute();

        // Clean up session
        session_unset();
        session_destroy();
        $_SESSION = [];
        $_SERVER = [];
    }

    /**
     * Test successful status update
     * - Verifies valid status transition works
     * - Checks database is updated
     * - Verifies log entry is created
     */
    public function testValidStatusUpdate()
    {
        // Set test data
        $_POST = [
            'task_id' => $this->testTaskId,
            'new_status' => 'In Progress'
        ];

        // Capture output
        ob_start();
        include __DIR__ . '/../update_task_status.php';
        $output = json_decode(ob_get_clean(), true);

        // Assert response
        if ($this->testAssert->assertTrue($output['success'], "Status update should succeed")) {
            $this->passCount++;
        } else {
            $this->failCount++;
        }

        // Verify database update
        $stmt = $this->pdo->prepare("SELECT status FROM tasks WHERE id = ?");
        $stmt->execute([$this->testTaskId]);
        $status = $stmt->fetchColumn();

        if ($this->testAssert->assertEquals('In Progress', $status, "Status should be updated in database")) {
            $this->passCount++;
        } else {
            $this->failCount++;
        }

        // Verify log entry
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM task_log 
             WHERE task_id = ? AND user_id = ? AND action LIKE ?"
        );
        $stmt->execute([$this->testTaskId, $this->testUserId, "%Status changed from Pending to In Progress%"]);
        $logCount = $stmt->fetchColumn();

        if ($this->testAssert->assertEquals(1, $logCount, "Status change should be logged")) {
            $this->passCount++;
        } else {
            $this->failCount++;
        }
    }

    /**
     * Test unauthorized access (not logged in)
     * - Verifies proper error when user is not authenticated
     */
    public function testNotLoggedIn()
    {
        global $failSetting;
        $failSetting = 1;

        unset($_SESSION['user_id']);

        $_POST = [
            'task_id' => $this->testTaskId,
            'new_status' => 'In Progress'
        ];

        ob_start();
        include __DIR__ . '/../update_task_status.php';
        $output = json_decode(ob_get_clean(), true);

        if ($this->testAssert->assertFalse($output['success'], "Should fail when not logged in")) {
            $this->passCount++;
        } else {
            $this->failCount++;
        }

        if ($this->testAssert->assertEquals("Not logged in", $output['message'], "Should return correct error message")) {
            $this->passCount++;
        } else {
            $this->failCount++;
        }
    }

    /**
     * Test invalid input
     * - Verifies proper error when required fields are missing
     */
    public function testInvalidInput()
    {
        global $failSetting;
        $failSetting = 2;

        $_POST = [
            'task_id' => null,
            'new_status' => 'Invalid Status'
        ];

        ob_start();
        include __DIR__ . '/../update_task_status.php';
        $output = json_decode(ob_get_clean(), true);

        if ($this->testAssert->assertFalse($output['success'], "Should fail with invalid input")) {
            $this->passCount++;
        } else {
            $this->failCount++;
        }

        if ($this->testAssert->assertEquals("Invalid input", $output['message'], "Should return correct error message")) {
            $this->passCount++;
        } else {
            $this->failCount++;
        }
    }

    /**
     * Test role-based permissions
     * - Verifies only supervisors/admins can mark tasks as complete
     */
    public function testRoleBasedPermissions()
    {
        global $failSetting;
        $failSetting = 6;

        // Set up task with all actions completed
        $this->pdo->prepare("UPDATE tasks SET status = 'To Be Reviewed' WHERE id = ?")
            ->execute([$this->testTaskId]);

        // Add completed actions
        $stmt = $this->pdo->prepare(
            "INSERT INTO task_actions (task_id, action_description, completed) 
             VALUES (:task_id, 'Test Action', true)"
        );
        $stmt->execute([':task_id' => $this->testTaskId]);

        // Try to mark as complete as regular user (should fail)
        $_POST = [
            'task_id' => $this->testTaskId,
            'new_status' => 'Complete'
        ];

        ob_start();
        include __DIR__ . '/../update_task_status.php';
        $output = json_decode(ob_get_clean(), true);

        if ($this->testAssert->assertFalse($output['success'], "Should fail for non-supervisor/admin")) {
            $this->passCount++;
        } else {
            $this->failCount++;
        }

        if (
            $this->testAssert->assertStringContains(
                "Only Supervisors and Admins",
                $output['message'],
                "Should return role restriction message"
            )
        ) {
            $this->passCount++;
        } else {
            $this->failCount++;
        }
    }

    /**
     * Run all test cases
     */
    public function runAllTests()
    {
        echo "=== Running Update Task Status Tests ===\n";

        $this->setUp();
        $this->testValidStatusUpdate();
        $this->tearDown();

        $this->setUp();
        $this->testNotLoggedIn();
        $this->tearDown();

        $this->setUp();
        $this->testInvalidInput();
        $this->tearDown();

        $this->setUp();
        $this->testRoleBasedPermissions();
        $this->tearDown();

        echo "=== Tests Complete ===\n";
    }
}

// Execute tests if run directly
if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    require_once __DIR__ . '/../db_connect.php';
    $test = new UpdateTaskStatusTest($pdo);
    $test->runAllTests();
}