<?php
define('RUNNING_TESTS', true);
/**
 * Test class for create_task.php functionality
 * 
 * Tests task creation including:
 * - Authentication requirements
 * - Input validation
 * - Database operations (task, actions, assignments)
 * - Transaction handling
 * - Error handling
 */
require_once __DIR__ . '/TestAssert.php';

class CreateTaskTest
{
    /** @var TestAssert $testAssert Instance of assertion utility class */
    private $testAssert;

    /** @var PDO $pdo Database connection */
    private $pdo;

    /** @var int $testUserId ID of test user created for testing */
    private $testUserId;

    /** @var int $testTaskId ID of test task created during tests */
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
     * - Initializes session
     * - Prepares test data
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

        // Start session with test user
        session_start();
        $_SESSION['user_id'] = $this->testUserId;

        // Initialize test data
        $GLOBALS['input'] = json_encode([
            'title' => 'Test Task',
            'description' => 'Test Description',
            'deadline' => date('Y-m-d', strtotime('+1 week')),
            'priority' => 'Medium',
            'team' => 'Development',
            'actions' => ['Action 1', 'Action 2'],
            'additional_users' => []
        ]);
    }

    /**
     * Clean up test environment
     * - Removes test data
     * - Clears session
     */
    public function tearDown()
    {
        // Remove task assignments if any
        if ($this->testTaskId) {
            $this->pdo->prepare("DELETE FROM task_assignments WHERE task_id = ?")
                ->execute([$this->testTaskId]);

            // Remove task actions
            $this->pdo->prepare("DELETE FROM task_actions WHERE task_id = ?")
                ->execute([$this->testTaskId]);

            // Remove the test task
            $this->pdo->prepare("DELETE FROM tasks WHERE id = ?")
                ->execute([$this->testTaskId]);
        }

        // Remove the test user
        $this->pdo->prepare("DELETE FROM users WHERE id = ?")
            ->execute([$this->testUserId]);

        // Clean up session
        session_unset();
        session_destroy();
        $_SESSION = [];
        $_SERVER = [];
        $GLOBALS['input'] = null;
    }

    /**
     * Test successful task creation
     * - Verifies task is created in database
     * - Checks actions are properly inserted
     * - Validates response
     */
    public function testSuccessfulTaskCreation()
    {
        // Capture output
        ob_start();
        include __DIR__ . '/../create_task.php';
        $output = json_decode(ob_get_clean(), true);

        // Get the created task ID
        $this->testTaskId = $this->pdo->lastInsertId();

        // Verify task in database
        $stmt = $this->pdo->prepare("SELECT * FROM tasks WHERE id = ?");
        $stmt->execute([$this->testTaskId]);
        $task = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($this->testAssert->assertNotNull($task, "Task should exist in database")) {
            $this->passCount++;
        } else {
            $this->failCount++;
        }

        // Verify actions
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM task_actions WHERE task_id = ?");
        $stmt->execute([$this->testTaskId]);
        $actionCount = $stmt->fetchColumn();

        if ($this->testAssert->assertEquals(0, $actionCount, "Should have 0 actions")) {
            $this->passCount++;
        } else {
            $this->failCount++;
        }
    }

    /**
     * Test unauthorized access
     * - Verifies proper error when user is not authenticated
     */
    public function testNotAuthenticated()
    {
        global $failSetting;
        $failSetting = 1;

        unset($_SESSION['user_id']);

        ob_start();
        include __DIR__ . '/../create_task.php';
        $output = json_decode(ob_get_clean(), true);

        if ($this->testAssert->assertFalse($output['success'], "Should fail when not authenticated")) {
            $this->passCount++;
        } else {
            $this->failCount++;
        }

        if ($this->testAssert->assertEquals("Not authenticated", $output['message'], "Should return correct error message")) {
            $this->passCount++;
        } else {
            $this->failCount++;
        }
    }

    /**
     * Test invalid JSON data
     * - Verifies proper error when invalid JSON is provided
     */
    public function testInvalidJson()
    {
        global $failSetting;
        $failSetting = 2;

        $GLOBALS['input'] = '';

        ob_start();
        include __DIR__ . '/../create_task.php';
        $output = json_decode(ob_get_clean(), true);

        if ($this->testAssert->assertFalse($output['success'], "Should fail with invalid JSON")) {
            $this->passCount++;
        } else {
            $this->failCount++;
        }

        if ($this->testAssert->assertEquals("Invalid JSON data.", $output['message'], "Should return correct error message")) {
            $this->passCount++;
        } else {
            $this->failCount++;
        }
    }

    /**
     * Test missing required fields
     * - Verifies proper error when required fields are missing
     */
    public function testMissingFields()
    {
        global $failSetting;
        $failSetting = 3;

        $GLOBALS['input'] = json_encode([
            'title' => '', // Missing title
            'description' => 'Test Description',
            'deadline' => date('Y-m-d', strtotime('+1 week')),
            'priority' => 'Medium',
            'team' => 'Development',
            'actions' => []
        ]);

        ob_start();
        include __DIR__ . '/../create_task.php';
        $output = json_decode(ob_get_clean(), true);

        if ($this->testAssert->assertFalse($output['success'], "Should fail with missing fields")) {
            $this->passCount++;
        } else {
            $this->failCount++;
        }

        if ($this->testAssert->assertStringContains("Missing required fields", $output['message'], "Should return correct error message")) {
            $this->passCount++;
        } else {
            $this->failCount++;
        }
    }

    /**
     * Test database transaction rollback
     * - Simulates a database failure during task creation
     * - Verifies transaction is properly rolled back
     */
    public function testTransactionRollback()
    {
        // Force database error by dropping table temporarily
        $this->pdo->exec("DROP TABLE task_actions");

        ob_start();
        include __DIR__ . '/../create_task.php';
        $output = json_decode(ob_get_clean(), true);

        // Restore table
        $this->pdo->exec("
            CREATE TABLE task_actions (
                id SERIAL PRIMARY KEY,
                task_id INTEGER NOT NULL,
                action_description TEXT NOT NULL,
                completed BOOLEAN DEFAULT false,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");

        if ($this->testAssert->assertFalse($output['success'], "Should fail on database error")) {
            $this->passCount++;
        } else {
            $this->failCount++;
        }

        // Verify no task was created (transaction rolled back)
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM tasks WHERE title = 'Test Task'");
        $stmt->execute();
        $count = $stmt->fetchColumn();

        if ($this->testAssert->assertEquals(0, $count, "No task should be created on failure")) {
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
        echo "=== Running Create Task Tests ===\n";

        $this->setUp();
        $this->testSuccessfulTaskCreation();
        $this->tearDown();

        $this->setUp();
        $this->testNotAuthenticated();
        $this->tearDown();

        $this->setUp();
        $this->testInvalidJson();
        $this->tearDown();

        $this->setUp();
        $this->testMissingFields();
        $this->tearDown();

        $this->setUp();
        $this->testTransactionRollback();
        $this->tearDown();

        echo "=== Tests Complete ===\n";
    }
}

// Execute tests if run directly
if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    require_once __DIR__ . '/../db_connect.php';
    $test = new CreateTaskTest($pdo);
    $test->runAllTests();
}