<?php
/**
 * Test class for add_comment.php functionality
 * 
 * Tests comment submission including:
 * - Authentication requirements
 * - Input validation
 * - Database operations
 * - Error handling
 */
require_once __DIR__ . '/TestAssert.php';

class AddCommentTest
{
    private $testAssert;
    private $pdo;
    private $testUserId;
    private $testTaskId;
    public $passCount = 0;
    public $failCount = 0;
    public function __construct()
    {
        $this->testAssert = new TestAssert();
        global $pdo;
        $this->pdo = $pdo;
    }

    /**
     * Set up test environment
     * - Creates test user
     * - Creates test task
     * - Starts session
     */
    public function setUp()
    {
        global $failSetting;
        $failSetting = 0; // Reset fail setting
        // Destroy any existing session first
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

        // Create test task
        $stmt = $this->pdo->prepare(
            "INSERT INTO tasks (title, description, owner, last_updated_by) 
             VALUES ('Test Task', 'Test Description', ?, ?) 
             RETURNING id"
        );
        $stmt->execute([$this->testUserId, $this->testUserId]);
        $this->testTaskId = $stmt->fetchColumn();
        echo "\n";
        // Start session
        session_start();
        $this->testUserId = $_SESSION['user_id'] ?? null;
    }

    /**
     * Clean up test environment
     * - Removes test data
     * - Clears session
     */
    public function tearDown()
    {
        // Remove test task from database
        $stmt = $this->pdo->prepare(
            "DELETE FROM tasks WHERE title = 'Test Task'"
        );
        session_unset();
        session_destroy();
        // Reset superglobals
        $_SESSION = [];
        $_SERVER = [];
        $GLOBALS['input'] = [];
    }

    /**
     * Test comment submission with valid data
     */
    public function testValidCommentSubmission()
    {
        $_POST = [
            'task_id' => $this->testTaskId,
            'comment' => 'This is a valid test comment'
        ];

        ob_start();
        include __DIR__ . '/../add_comment.php';
        $output = json_decode(ob_get_clean(), true);

        // Test response
        if (
            $this->testAssert->assertTrue(
                $output['success'],
                "Valid comment should return success"
            )
        ) {
            $this->passCount++;
        } else {
            $this->failCount++;
        }

        // Test database record
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM task_comments 
             WHERE task_id = ? AND user_id = ? AND comment = ?"
        );
        $stmt->execute([$this->testTaskId, $this->testUserId, $_POST['comment']]);
        $count = $stmt->fetchColumn();

        if (
            $this->testAssert->assertEquals(
                1,
                $count,
                "Comment should be saved in database"
            )
        ) {
            $this->passCount++;
        } else {
            $this->failCount++;
        }

        // Test log entry
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM task_log 
             WHERE task_id = ? AND user_id = ? AND action LIKE ?"
        );
        $stmt->execute([$this->testTaskId, $this->testUserId, "%Added a comment%"]);
        $logCount = $stmt->fetchColumn();

        if (
            $this->testAssert->assertEquals(
                1,
                $logCount,
                "Comment action should be logged"
            )
        ) {
            $this->passCount++;
        } else {
            $this->failCount++;
        }
    }

    /**
     * Test comment submission without authentication
     */
    public function testNoUserID()
    {
        unset($_SESSION['user_id']);
        global $failSetting;
        $failSetting = 1;

        $_POST = [
            'task_id' => $this->testTaskId,
            'comment' => 'Should fail'
        ];

        ob_start();
        include __DIR__ . '/../add_comment.php';
        $output = json_decode(ob_get_clean(), true);

        if (
            $this->testAssert->assertFalse(
                $output['success'],
                "Should fail without authentication"
            )
        ) {
            $this->passCount++;
        } else {
            $this->failCount++;
        }

        if (
            $this->testAssert->assertEquals(
                "Invalid request.",
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
     * Test empty comment submission
     */
    public function testEmptyComment()
    {
        global $failSetting;
        $failSetting = 2;

        $_POST = [
            'task_id' => $this->testTaskId,
            'comment' => ''
        ];

        ob_start();
        include __DIR__ . '/../add_comment.php';
        $output = json_decode(ob_get_clean(), true);

        if (
            $this->testAssert->assertFalse(
                $output['success'],
                "Empty comment should fail"
            )
        ) {
            $this->passCount++;
        } else {
            $this->failCount++;
        }

        if (
            $this->testAssert->assertEquals(
                "Comment cannot be empty.",
                $output['message'],
                "Should return empty comment error"
            )
        ) {
            $this->passCount++;
        } else {
            $this->failCount++;
        }
    }

    /**
     * Test invalid task ID
     */
    public function testInvalidTaskId()
    {
        global $failSetting;
        $failSetting = 3;

        $_POST = [
            'task_id' => 999999, // Non-existent task
            'comment' => 'Valid comment'
        ];

        ob_start();
        include __DIR__ . '/../add_comment.php';
        $output = json_decode(ob_get_clean(), true);

        if (
            $this->testAssert->assertFalse(
                $output['success'],
                "Invalid task ID should fail"
            )
        ) {
            $this->passCount++;
        } else {
            $this->failCount++;
        }

        if (
            $this->testAssert->assertStringContains(
                "Database error",
                $output['message'],
                "Should return database error"
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
        echo "=== Running Add Comment Tests ===\n";
        $this->setUp();
        $this->testValidCommentSubmission();
        $this->tearDown();
        $this->setUp();
        $this->testNoUserID();
        $this->tearDown();
        $this->setUp();
        $this->testEmptyComment();
        $this->tearDown();
        $this->setUp();
        $this->testInvalidTaskId();
        $this->tearDown();
        echo "=== Tests Complete ===\n";
    }
}

// Execute tests if run directly
if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    $test = new AddCommentTest();
    $test->runAllTests();
}
?>