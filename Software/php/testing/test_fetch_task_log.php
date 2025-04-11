<?php
define('RUNNING_TESTS', true);
/**
 * Test class for fetch_task_log.php functionality
 * 
 * Tests task log fetching including:
 * - Task ID parameter validation
 * - Comment and log entry data structure
 * - Database error handling
 */
require_once __DIR__ . '/TestAssert.php';
include_once __DIR__ . '/../db_connect.php'; // Defines $pdo

class FetchTaskLogTest
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
        global $failSetting;
        $failSetting = 0;
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }

        // Clear all session data
        $_SESSION = [];

        // Set default request method
        $_SERVER['REQUEST_METHOD'] = 'GET';

        try {
            // Create test user
            $stmt = $this->pdo->prepare(
                "INSERT INTO users (email, password_hash, first_name, last_name, role) 
                 VALUES ('testuser@example.com', 'hashed_password', 'Test', 'User', 'User')
                 RETURNING id"
            );
            $stmt->execute();
            $this->testUserId = $stmt->fetchColumn();

            // Create test task
            $stmt = $this->pdo->prepare(
                "INSERT INTO tasks (title, description, status) 
                 VALUES ('Test Task', 'Test Description', 'Pending')
                 RETURNING id"
            );
            $stmt->execute();
            $this->testTaskId = $stmt->fetchColumn();

            // Add test comment
            $stmt = $this->pdo->prepare(
                "INSERT INTO task_comments (task_id, user_id, comment) 
                 VALUES (?, ?, 'Test comment')"
            );
            $stmt->execute([$this->testTaskId, $this->testUserId]);

            // Add test log entry
            $stmt = $this->pdo->prepare(
                "INSERT INTO task_log (task_id, user_id, action) 
                 VALUES (?, ?, 'Test action')"
            );
            $stmt->execute([$this->testTaskId, $this->testUserId]);

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
            $this->pdo->exec("DELETE FROM task_comments WHERE task_id = $this->testTaskId");
            $this->pdo->exec("DELETE FROM task_log WHERE task_id = $this->testTaskId");
            $this->pdo->exec("DELETE FROM tasks WHERE id = $this->testTaskId");
            $this->pdo->exec("DELETE FROM users WHERE id = $this->testUserId");
        } catch (PDOException $e) {
            error_log("Cleanup error: " . $e->getMessage());
        }

        session_unset();
        session_destroy();
        $_SESSION = [];
    }

    /**
     * Test successful log fetch with valid task ID
     */
    public function testSuccessfulLogFetch()
    {
        $_GET = ['task_id' => $this->testTaskId];

        ob_start();
        include __DIR__ . '/../fetch_task_log.php';
        $output = json_decode(ob_get_clean(), true);


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

        // Test comments array
        if (
            $this->testAssert->assertNotNull(
                $output['comments'] ?? null,
                "Response should contain comments array"
            )
        ) {
            $this->passCount++;
        } else {
            $this->failCount++;
        }

        // Test log entries array
        if (
            $this->testAssert->assertNotNull(
                $output['log_entries'] ?? null,
                "Response should contain log_entries array"
            )
        ) {
            $this->passCount++;
        } else {
            $this->failCount++;
        }

        // Test comment structure
        if (!empty($output['comments'])) {
            $comment = $output['comments'][0];
            $requiredFields = ['comment', 'created_at', 'first_name', 'last_name'];
            foreach ($requiredFields as $field) {
                if (
                    $this->testAssert->assertNotNull(
                        $comment[$field] ?? null,
                        "Comment should have $field field"
                    )
                ) {
                    $this->passCount++;
                } else {
                    $this->failCount++;
                }
            }
        }

        // Test log entry structure
        if (!empty($output['log_entries'])) {
            $log = $output['log_entries'][0];
            $requiredFields = ['action', 'created_at', 'first_name', 'last_name'];
            foreach ($requiredFields as $field) {
                if (
                    $this->testAssert->assertNotNull(
                        $log[$field] ?? null,
                        "Log entry should have $field field"
                    )
                ) {
                    $this->passCount++;
                } else {
                    $this->failCount++;
                }
            }
        }
    }

    /**
     * Test missing task ID parameter
     */
    public function testMissingTaskId()
    {
        global $failSetting;
        $failSetting = 1;
        unset($_GET['task_id']);

        ob_start();
        include __DIR__ . '/../fetch_task_log.php';
        $output = json_decode(ob_get_clean(), true);

        if (
            $this->testAssert->assertFalse(
                $output['success'],
                "Request should fail without task_id"
            )
        ) {
            $this->passCount++;
        } else {
            $this->failCount++;
        }

        if (
            $this->testAssert->assertEquals(
                "Task ID is required.",
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
     * Test non-existent task ID
     */
    public function testNonExistentTaskId()
    {
        $_GET = ['task_id' => 999999];

        ob_start();
        include __DIR__ . '/../fetch_task_log.php';
        $output = json_decode(ob_get_clean(), true);

        if (
            $this->testAssert->assertTrue(
                $output['success'],
                "Request should succeed even with non-existent task_id"
            )
        ) {
            $this->passCount++;
        } else {
            $this->failCount++;
        }

        if (
            $this->testAssert->assertEquals(
                0,
                count($output['comments'] ?? []),
                "Comments array should be empty"
            )
        ) {
            $this->passCount++;
        } else {
            $this->failCount++;
        }

        if (
            $this->testAssert->assertEquals(
                0,
                count($output['log_entries'] ?? []),
                "Log entries array should be empty"
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
        echo "=== Running Fetch Task Log Tests ===\n";

        $this->setUp();
        $this->testSuccessfulLogFetch();
        $this->tearDown();

        $this->setUp();
        $this->testMissingTaskId();
        $this->tearDown();

        $this->setUp();
        $this->testNonExistentTaskId();
        $this->tearDown();

        echo "=== Tests Complete ===\n";
    }
}

// Execute tests if run directly
if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    $test = new FetchTaskLogTest();
    $test->runAllTests();
}