<?php
define('RUNNING_TESTS', true);
/**
 * Test class for fetch_task_files.php functionality
 * 
 * Tests task file fetching including:
 * - Authentication requirements
 * - Task ID parameter validation
 * - File data structure
 * - Error handling
 */
require_once __DIR__ . '/TestAssert.php';
include_once __DIR__ . '/../db_connect.php'; // Defines $pdo

class FetchTaskFilesTest
{
    private $testAssert;
    private $pdo;
    public $passCount = 0;
    public $failCount = 0;
    private $testUserId;
    private $testTaskId;
    private $testFileId;

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
        $_SERVER['REQUEST_METHOD'] = 'GET';

        // Initialize input array with default login values
        $GLOBALS['input'] = [
            'email' => 'user1@yhrocu.uk',
            'password' => 'User@123'
        ];
        echo "Login should redirect to 'html/verify_otp.html' see below ⬇️\n";
        require '../login.php';

        try {
            $this->testUserId = $_SESSION['user_id'];

            // Create test task
            $stmt = $this->pdo->prepare(
                "INSERT INTO tasks (title, description, status) 
                 VALUES ('Test Task', 'Test Description', 'Pending')
                 RETURNING id"
            );
            $stmt->execute();
            $this->testTaskId = $stmt->fetchColumn();

            // Create test file
            $stmt = $this->pdo->prepare(
                "INSERT INTO task_files (task_id, uploaded_by, file_name, file_path) 
                 VALUES (?, ?, 'test_file.txt', '/uploads/test_file.txt')
                 RETURNING id"
            );
            $stmt->execute([$this->testTaskId, $this->testUserId]);
            $this->testFileId = $stmt->fetchColumn();

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
            $this->pdo->exec("DELETE FROM task_files WHERE id = $this->testFileId");
            $this->pdo->exec("DELETE FROM tasks WHERE id = $this->testTaskId");
        } catch (PDOException $e) {
            error_log("Cleanup error: " . $e->getMessage());
        }

        session_unset();
        session_destroy();
        $_SESSION = [];
    }

    /**
     * Test successful file fetch with valid parameters
     */
    public function testSuccessfulFileFetch()
    {
        $_GET = ['task_id' => $this->testTaskId];

        ob_start();
        include __DIR__ . '/../fetch_task_files.php';
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

        // Test files array exists
        if (
            $this->testAssert->assertNotNull(
                $output['files'] ?? null,
                "Response should contain files array"
            )
        ) {
            $this->passCount++;
        } else {
            $this->failCount++;
        }

        // Test file structure
        if (!empty($output['files'])) {
            $file = $output['files'][0];
            $requiredFields = ['id', 'file_name', 'file_path', 'uploaded_at', 'first_name', 'last_name'];
            foreach ($requiredFields as $field) {
                if (
                    $this->testAssert->assertNotNull(
                        $file[$field] ?? null,
                        "File should have $field field"
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
     * Test unauthenticated access
     */
    public function testUnauthenticatedAccess()
    {
        global $failSetting;
        $failSetting = 1;
        unset($_SESSION['user_id']);

        ob_start();
        include __DIR__ . '/../fetch_task_files.php';
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
     * Test missing task ID
     */
    public function testMissingTaskId()
    {
        global $failSetting;
        $failSetting = 2;
        unset($_GET['task_id']);

        ob_start();
        include __DIR__ . '/../fetch_task_files.php';
        $output = json_decode(ob_get_clean(), true);
        echo "\n";

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
                "Missing task ID",
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
     * Test invalid task ID format
     */
    public function testInvalidTaskId()
    {
        $_GET = ['task_id' => 'invalid'];

        ob_start();
        include __DIR__ . '/../fetch_task_files.php';
        $output = json_decode(ob_get_clean(), true);
        echo "\n";

        if (
            $this->testAssert->assertFalse(
                $output['success'],
                "Request should fail with invalid task_id"
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
        include __DIR__ . '/../fetch_task_files.php';
        $output = json_decode(ob_get_clean(), true);
        echo "\n";

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
                count($output['files'] ?? []),
                "Files array should be empty"
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
        echo "=== Running Fetch Task Files Tests ===\n";

        $this->setUp();
        $this->testSuccessfulFileFetch();
        $this->tearDown();

        $this->setUp();
        $this->testUnauthenticatedAccess();
        $this->tearDown();

        $this->setUp();
        $this->testMissingTaskId();
        $this->tearDown();

        $this->setUp();
        $this->testInvalidTaskId();
        $this->tearDown();

        $this->setUp();
        $this->testNonExistentTaskId();
        $this->tearDown();

        echo "=== Tests Complete ===\n";
    }
}

// Execute tests if run directly
if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    $test = new FetchTaskFilesTest();
    $test->runAllTests();
}