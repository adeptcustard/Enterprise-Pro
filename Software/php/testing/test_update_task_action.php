<?php
define('RUNNING_TESTS', true);
/**
 * Test class for update_task_action.php functionality
 * 
 * Tests task action updates including:
 * - Authentication requirements
 * - Action status toggling
 * - Database operations
 * - Task logging
 * - Error handling
 */
require_once __DIR__ . '/TestAssert.php';

class UpdateTaskActionTest
{
    /** @var TestAssert $testAssert Instance of assertion utility class */
    private $testAssert;

    /** @var PDO $pdo Database connection */
    private $pdo;

    /** @var int $testUserId ID of test user created for testing */
    private $testUserId;

    /** @var int $testTaskId ID of test task created for testing */
    private $testTaskId;

    /** @var int $testActionId ID of test action created for testing */
    private $testActionId;

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
     * - Creates test action
     * - Initializes session
     */
    public function setUp()
    {
        global $failSetting;
        $failSetting = 0;

        // Clean up any existing session
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }

        $_SESSION = [];
        $_SERVER['REQUEST_METHOD'] = 'GET';

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

        // Create a test action
        $stmt = $this->pdo->prepare(
            "INSERT INTO task_actions (task_id, action_description, completed) 
             VALUES (:task_id, 'Test Action', false) 
             RETURNING id"
        );
        $stmt->execute([':task_id' => $this->testTaskId]);
        $this->testActionId = $stmt->fetchColumn();

        // Start session with test user
        session_start();
        $_SESSION['user_id'] = $this->testUserId;
    }

    /**
     * Clean up test environment
     * - Removes test data
     * - Clears session
     */
    public function tearDown()
    {
        // Remove task log entries
        $this->pdo->prepare("DELETE FROM task_log WHERE task_id = ?")
            ->execute([$this->testTaskId]);

        // Remove test actions
        $this->pdo->prepare("DELETE FROM task_actions WHERE task_id = ?")
            ->execute([$this->testTaskId]);

        // Remove the test task
        $this->pdo->prepare("DELETE FROM tasks WHERE id = ?")
            ->execute([$this->testTaskId]);

        // Remove the test user
        $this->pdo->prepare("DELETE FROM users WHERE id = ?")
            ->execute([$this->testUserId]);

        // Clean up session
        session_unset();
        session_destroy();
        $_SESSION = [];
        $_SERVER = [];
    }

    /**
     * Test successful action status toggle
     * - Verifies status is updated in database
     * - Checks proper log entry is created
     * - Validates response
     */
    public function testSuccessfulActionToggle()
    {
        $_GET = ['action_id' => $this->testActionId];

        // First toggle (mark complete)
        ob_start();
        include __DIR__ . '/../update_task_action.php';
        $output = json_decode(ob_get_clean(), true);

        // Assert response
        if ($this->testAssert->assertTrue($output['success'], "Action toggle should succeed")) {
            $this->passCount++;
        } else {
            $this->failCount++;
        }

        // Verify database update
        $stmt = $this->pdo->prepare("SELECT completed FROM task_actions WHERE id = ?");
        $stmt->execute([$this->testActionId]);
        $status = $stmt->fetchColumn();
        settype($status, "integer");
        $expected = 1;
        if ($this->testAssert->assertEquals($expected, $status, "Action should be marked complete")) {
            $this->passCount++;
        } else {
            $this->failCount++;
        }

        // Verify log entry
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM task_log 
             WHERE task_id = ? AND user_id = ? AND action LIKE ?"
        );
        $stmt->execute([$this->testTaskId, $this->testUserId, "%marked as complete%"]);
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
        $_GET = ['action_id' => $this->testActionId];

        ob_start();
        include __DIR__ . '/../update_task_action.php';
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
     * Test missing action ID
     * - Verifies proper error when action ID is missing
     */
    public function testMissingActionId()
    {
        global $failSetting;
        $failSetting = 2;

        $_GET = [];

        ob_start();
        include __DIR__ . '/../update_task_action.php';
        $output = json_decode(ob_get_clean(), true);

        if ($this->testAssert->assertFalse($output['success'], "Should fail with missing action ID")) {
            $this->passCount++;
        } else {
            $this->failCount++;
        }

        if ($this->testAssert->assertEquals("Action ID missing", $output['message'], "Should return correct error message")) {
            $this->passCount++;
        } else {
            $this->failCount++;
        }
    }

    /**
     * Test non-existent action
     * - Verifies proper error when action doesn't exist
     */
    public function testNonExistentAction()
    {
        global $failSetting;
        $failSetting = 1;
        $_GET = ['action_id' => 999999];

        ob_start();
        include __DIR__ . '/../update_task_action.php';
        $output = json_decode(ob_get_clean(), true);

        if ($this->testAssert->assertFalse($output['success'], "Should fail with non-existent action")) {
            $this->passCount++;
        } else {
            $this->failCount++;
        }

        if ($this->testAssert->assertEquals("Action not found", $output['message'], "Should return correct error message")) {
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
        echo "=== Running Update Task Action Tests ===\n";

        $this->setUp();
        $this->testSuccessfulActionToggle();
        $this->tearDown();

        $this->setUp();
        $this->testNotLoggedIn();
        $this->tearDown();

        $this->setUp();
        $this->testMissingActionId();
        $this->tearDown();

        $this->setUp();
        $this->testNonExistentAction();
        $this->tearDown();

        echo "=== Tests Complete ===\n";
    }
}

// Execute tests if run directly
if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    require_once __DIR__ . '/../db_connect.php';
    $test = new UpdateTaskActionTest($pdo);
    $test->runAllTests();
}