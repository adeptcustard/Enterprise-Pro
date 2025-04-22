<?php
/**
 * Test class for delete_task.php functionality
 * 
 * Tests task deletion including:
 * - HTTP method validation
 * - Input validation
 * - Database operations (task, actions, assignments)
 * - Transaction handling
 * - Error handling
 */
require_once __DIR__ . '/TestAssert.php';

class DeleteTaskTest
{
    /** @var TestAssert $testAssert Instance of assertion utility class */
    private $testAssert;

    /** @var PDO $pdo Database connection */
    private $pdo;

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
     * - Creates test task with related data
     * - Sets up HTTP method
     */
    public function setUp()
    {
        global $failSetting;
        $failSetting = 0;

        $_SERVER['REQUEST_METHOD'] = 'DELETE';

        // Create a test task
        $stmt = $this->pdo->prepare(
            "INSERT INTO tasks (title, description, owner, status) 
             VALUES ('Test Task', 'Test Description', 1, 'Pending') 
             RETURNING id"
        );
        $stmt->execute();
        $this->testTaskId = $stmt->fetchColumn();

        // Add test task actions
        $stmt = $this->pdo->prepare(
            "INSERT INTO task_actions (task_id, action_description) 
             VALUES (?, 'Test Action')"
        );
        $stmt->execute([$this->testTaskId]);

        // Add test task assignments
        $stmt = $this->pdo->prepare(
            "INSERT INTO task_assignments (task_id, user_id) 
             VALUES (?, 1)"
        );
        $stmt->execute([$this->testTaskId]);

        // Set up input data
        $GLOBALS['input'] = $this->testTaskId;
    }

    /**
     * Clean up test environment
     * - Removes any remaining test data
     */
    public function tearDown()
    {
        // Clean up any remaining data
        $this->pdo->prepare("DELETE FROM task_actions WHERE task_id = ?")
            ->execute([$this->testTaskId]);
        $this->pdo->prepare("DELETE FROM task_assignments WHERE task_id = ?")
            ->execute([$this->testTaskId]);
        $this->pdo->prepare("DELETE FROM tasks WHERE id = ?")
            ->execute([$this->testTaskId]);

        $_SERVER = [];
        $GLOBALS['input'] = null;
    }

    /**
     * Test successful task deletion
     * - Verifies task and related data are deleted
     * - Checks proper response
     */
    public function testSuccessfulTaskDeletion()
    {
        // Capture output
        ob_start();
        include __DIR__ . '/../delete_task.php';
        $output = json_decode(ob_get_clean(), true);

        // Assert response
        if ($this->testAssert->assertTrue($output['success'], "Task deletion should succeed")) {
            $this->passCount++;
        } else {
            $this->failCount++;
        }

        // Verify task is deleted
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM tasks WHERE id = ?");
        $stmt->execute([$this->testTaskId]);
        $count = $stmt->fetchColumn();

        if ($this->testAssert->assertEquals(0, $count, "Task should be deleted from database")) {
            $this->passCount++;
        } else {
            $this->failCount++;
        }

        // Verify actions are deleted
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM task_actions WHERE task_id = ?");
        $stmt->execute([$this->testTaskId]);
        $actionCount = $stmt->fetchColumn();

        if ($this->testAssert->assertEquals(0, $actionCount, "Task actions should be deleted")) {
            $this->passCount++;
        } else {
            $this->failCount++;
        }

        // Verify assignments are deleted
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM task_assignments WHERE task_id = ?");
        $stmt->execute([$this->testTaskId]);
        $assignmentCount = $stmt->fetchColumn();

        if ($this->testAssert->assertEquals(0, $assignmentCount, "Task assignments should be deleted")) {
            $this->passCount++;
        } else {
            $this->failCount++;
        }
    }

    /**
     * Test invalid HTTP method
     * - Verifies proper error when using wrong HTTP method
     */
    public function testInvalidHttpMethod()
    {
        global $failSetting;
        $failSetting = 1;

        $_SERVER['REQUEST_METHOD'] = 'POST';

        ob_start();
        include __DIR__ . '/../delete_task.php';
        $output = json_decode(ob_get_clean(), true);

        if ($this->testAssert->assertFalse($output['success'], "Should fail with invalid HTTP method")) {
            $this->passCount++;
        } else {
            $this->failCount++;
        }

        if ($this->testAssert->assertEquals("❌ Invalid request method.", $output['message'], "Should return correct error message")) {
            $this->passCount++;
        } else {
            $this->failCount++;
        }
    }

    /**
     * Test missing task ID
     * - Verifies proper error when task ID is missing
     */
    public function testMissingTaskId()
    {
        global $failSetting;
        $failSetting = 1;

        $GLOBALS['input'] = "";

        ob_start();
        include __DIR__ . '/../delete_task.php';
        $output = json_decode(ob_get_clean(), true);

        if ($this->testAssert->assertFalse($output['success'], "Should fail with missing task ID")) {
            $this->passCount++;
        } else {
            $this->failCount++;
        }

        if ($this->testAssert->assertEquals("❌ Task ID is required.", $output['message'], "Should return correct error message")) {
            $this->passCount++;
        } else {
            $this->failCount++;
        }
    }

    /**
     * Test non-existent task
     * - Verifies proper handling when task doesn't exist
     */
    public function testNonExistentTask()
    {
        $nonExistentTaskId = 999999;
        $GLOBALS['input'] = "id=$nonExistentTaskId";

        ob_start();
        include __DIR__ . '/../delete_task.php';
        $output = json_decode(ob_get_clean(), true);

        // Should still succeed as DELETE is idempotent
        if ($this->testAssert->assertFalse($output['success'], "Should fail if task doesn't exist")) {
            $this->passCount++;
        } else {
            $this->failCount++;
        }
    }

    /**
     * Test database transaction rollback
     * - Simulates a database failure during deletion
     * - Verifies transaction is properly rolled back
     */
    public function testTransactionRollback()
    {
        // Force database error by dropping table temporarily
        $this->pdo->exec("DROP TABLE task_actions");

        ob_start();
        include __DIR__ . '/../delete_task.php';
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

        // Verify task still exists (transaction rolled back)
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM tasks WHERE id = ?");
        $stmt->execute([$this->testTaskId]);
        $count = $stmt->fetchColumn();

        if ($this->testAssert->assertEquals(1, $count, "Task should still exist after failed deletion")) {
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
        echo "=== Running Delete Task Tests ===\n";

        $this->setUp();
        $this->testSuccessfulTaskDeletion();
        $this->tearDown();

        $this->setUp();
        $this->testInvalidHttpMethod();
        $this->tearDown();

        $this->setUp();
        $this->testMissingTaskId();
        $this->tearDown();

        $this->setUp();
        $this->testNonExistentTask();
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
    $test = new DeleteTaskTest($pdo);
    $test->runAllTests();
}