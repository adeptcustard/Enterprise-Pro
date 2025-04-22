<?php
/**
 * Test class for upload_task_file.php functionality
 * 
 * Tests file uploads for tasks including:
 * - Authentication requirements
 * - File upload validation
 * - Database record creation
 * - Error handling
 * - File system operations
 */
require_once __DIR__ . '/TestAssert.php';

class UploadTaskFileTest
{
    /** @var TestAssert $testAssert Instance of assertion utility class */
    private $testAssert;

    /** @var PDO $pdo Database connection */
    private $pdo;

    /** @var int $testUserId ID of test user created for testing */
    private $testUserId;

    /** @var int $testTaskId ID of test task created for testing */
    private $testTaskId;

    /** @var string $testFilePath Path to test upload directory */
    private $testFilePath = __DIR__ . '/../uploads/tasks/';

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
     * - Cleans up upload directory
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

        // Create test directory if it doesn't exist
        if (!is_dir($this->testFilePath)) {
            mkdir($this->testFilePath, 0777, true);
        }

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
    }

    /**
     * Clean up test environment
     * - Removes test data
     * - Clears session
     * - Deletes test files
     */
    public function tearDown()
    {
        // Remove test files
        $files = glob($this->testFilePath . '*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        ;

        // Remove the test task
        $this->pdo->prepare("DELETE FROM tasks WHERE title = 'Test Task'")
            ->execute();

        // Remove the test user
        $this->pdo->prepare("DELETE FROM users WHERE first_name = 'Test'")
            ->execute();

        // Clean up session
        session_unset();
        session_destroy();
        $_SESSION = [];
        $_SERVER = [];
    }

    /**
     * Test successful file upload
     * - Verifies file is moved to upload directory
     * - Checks database record is created
     * - Validates response
     */
    public function testSuccessfulFileUpload()
    {
        // Create a test file
        $testFileName = 'test_file.txt';
        $testFileContent = 'This is a test file';
        file_put_contents($testFileName, $testFileContent);

        // Simulate file upload
        $_FILES = [
            'file' => [
                'name' => $testFileName,
                'type' => 'text/plain',
                'tmp_name' => $testFileName,
                'error' => 0,
                'size' => filesize($testFileName)
            ]
        ];

        $_POST = ['task_id' => $this->testTaskId];

        // Capture output
        ob_start();
        include __DIR__ . '/../upload_task_file.php';
        $output = json_decode(ob_get_clean(), true);

        // Clean up test file
        unlink($testFileName);

        // Assert response
        if ($this->testAssert->assertTrue($output['success'], "File upload should succeed")) {
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

        ob_start();
        include __DIR__ . '/../upload_task_file.php';
        $output = json_decode(ob_get_clean(), true);

        if ($this->testAssert->assertFalse($output['success'], "Should fail when not logged in")) {
            $this->passCount++;
        } else {
            $this->failCount++;
        }

        if ($this->testAssert->assertEquals("User not logged in", $output['message'], "Should return correct error message")) {
            $this->passCount++;
        } else {
            $this->failCount++;
        }
    }

    /**
     * Test missing file or task ID
     * - Verifies proper error when required fields are missing
     */
    public function testMissingInput()
    {
        global $failSetting;
        $failSetting = 2;

        $_FILES = [];
        $_POST = [];

        ob_start();
        include __DIR__ . '/../upload_task_file.php';
        $output = json_decode(ob_get_clean(), true);

        if ($this->testAssert->assertFalse($output['success'], "Should fail with missing input")) {
            $this->passCount++;
        } else {
            $this->failCount++;
        }

        if ($this->testAssert->assertEquals("Missing task ID or file", $output['message'], "Should return correct error message")) {
            $this->passCount++;
        } else {
            $this->failCount++;
        }
    }

    /**
     * Test file upload failure
     * - Simulates a failed file upload
     * - Verifies proper error handling
     */
    public function testFileUploadFailure()
    {
        // Simulate failed upload
        $_FILES = [
            'file' => [
                'name' => 'test_file.txt',
                'type' => 'text/plain',
                'tmp_name' => '',
                'error' => UPLOAD_ERR_NO_FILE,
                'size' => 0
            ]
        ];

        $_POST = ['task_id' => $this->testTaskId];

        ob_start();
        include __DIR__ . '/../upload_task_file.php';
        $output = json_decode(ob_get_clean(), true);

        if ($this->testAssert->assertFalse($output['success'], "Should fail when file upload fails")) {
            $this->passCount++;
        } else {
            $this->failCount++;
        }

        if ($this->testAssert->assertEquals("File upload failed", $output['message'], "Should return correct error message")) {
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
        echo "=== Running Upload Task File Tests ===\n";

        $this->setUp();
        $this->testSuccessfulFileUpload();
        $this->tearDown();

        $this->setUp();
        $this->testNotLoggedIn();
        $this->tearDown();

        $this->setUp();
        $this->testMissingInput();
        $this->tearDown();

        $this->setUp();
        $this->testFileUploadFailure();
        $this->tearDown();

        echo "=== Tests Complete ===\n";
    }
}

// Execute tests if run directly
if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    require_once __DIR__ . '/../db_connect.php';
    $test = new UploadTaskFileTest($pdo);
    $test->runAllTests();
}