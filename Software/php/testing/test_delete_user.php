<?php
/**
 * Test class for delete_user.php functionality
 * 
 * Tests user deletion including:
 * - Admin role verification
 * - Self-deletion prevention
 * - User existence check
 * - Database operations
 * - Error handling
 */
require_once __DIR__ . '/TestAssert.php';

class DeleteUserTest
{
    /** @var TestAssert $testAssert Instance of assertion utility class */
    private $testAssert;

    /** @var PDO $pdo Database connection */
    private $pdo;

    /** @var int $adminUserId ID of admin user created for testing */
    private $adminUserId;

    /** @var int $testUserId ID of test user created for testing */
    private $testUserId;

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
     * - Creates admin user
     * - Creates test user to be deleted
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
        $_SERVER['REQUEST_METHOD'] = 'POST';

        //Login as admin

        // Initialize input array with admin login values
        $GLOBALS['input'] = [
            'email' => 'admin@yhrocu.uk',
            'password' => 'Admin@123'
        ];
        echo "Admin login should redirect to 'html/verify_otp.html' see below ⬇️\n";
        require '../login.php';
        echo "\n";

        // Verify admin login
        if (!isset($_SESSION['user_id'])) {
            die("❌ Failed to log in test admin user");
        }

        $this->adminUserId = $_SESSION['user_id'];
        $_SESSION['role'] = 'Admin';

        // Create a test user to delete
        $stmt = $this->pdo->prepare(
            "INSERT INTO users (email, password_hash, role, first_name, last_name) 
             VALUES ('testuser@yhrocu.uk', :hash, 'User', 'Test', 'User') 
             RETURNING id"
        );
        $hash = password_hash('Test@123', PASSWORD_DEFAULT);
        $stmt->execute([':hash' => $hash]);
        $this->testUserId = $stmt->fetchColumn();

        // Initialize test data
        $GLOBALS['input'] = $this->testUserId;
    }

    /**
     * Clean up test environment
     * - Removes any remaining test data
     * - Clears session
     */
    public function tearDown()
    {
        // Clean up any remaining users (in case deletion failed)
        global $pdo;
        $stmt = $pdo->prepare("DELETE FROM users WHERE first_name = 'Test'");
        $stmt->execute();
        $pdo->prepare("DELETE FROM users WHERE email = 'testuser@yhrocu.uk'");
        $stmt->execute();
        $pdo->prepare("DELETE FROM tasks WHERE title = 'Test Task'");
        $stmt->execute();
        $pdo->prepare("DELETE FROM tasks WHERE title = 'Supervisor Test Task'");
        $stmt->execute();
    }

    /**
     * Test successful user deletion
     * - Verifies user is deleted from database
     * - Checks proper response
     */
    public function testSuccessfulUserDeletion()
    {
        // Capture output
        ob_start();
        include __DIR__ . '/../delete_user.php';
        $output = json_decode(ob_get_clean(), true);

        // Assert response
        if ($this->testAssert->assertTrue($output['success'], "User deletion should succeed")) {
            $this->passCount++;
        } else {
            $this->failCount++;
        }

        // Verify user is deleted from database
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM users WHERE id = ?");
        $stmt->execute([$this->testUserId]);
        $count = $stmt->fetchColumn();

        if ($this->testAssert->assertEquals(0, $count, "User should be deleted from database")) {
            $this->passCount++;
        } else {
            $this->failCount++;
        }
    }

    /**
     * Test unauthorized access (non-admin)
     * - Verifies proper error when user is not admin
     */
    public function testNonAdminAccess()
    {
        global $failSetting;
        $failSetting = 1;

        // Change session to non-admin user
        $_SESSION['role'] = 'User';

        ob_start();
        include __DIR__ . '/../delete_user.php';
        $output = json_decode(ob_get_clean(), true);

        if ($this->testAssert->assertFalse($output['success'], "Should fail when not admin")) {
            $this->passCount++;
        } else {
            $this->failCount++;
        }

        if ($this->testAssert->assertEquals("Unauthorised access.", $output['message'], "Should return correct error message")) {
            $this->passCount++;
        } else {
            $this->failCount++;
        }
    }

    /**
     * Test self-deletion prevention
     * - Verifies admin cannot delete their own account
     */
    public function testSelfDeletionPrevention()
    {
        global $failSetting;
        $failSetting = 2;

        // Try to delete admin's own account
        $GLOBALS['input'] = json_encode(['user_id' => $this->adminUserId]);

        ob_start();
        include __DIR__ . '/../delete_user.php';
        $output = json_decode(ob_get_clean(), true);

        if ($this->testAssert->assertFalse($output['success'], "Should prevent self-deletion")) {
            $this->passCount++;
        } else {
            $this->failCount++;
        }

        if ($this->testAssert->assertStringContains("cannot delete your own account", $output['message'], "Should return correct error message")) {
            $this->passCount++;
        } else {
            $this->failCount++;
        }
    }

    /**
     * Test invalid user ID
     * - Verifies proper error when user ID is invalid
     */
    public function testInvalidUserId()
    {
        global $failSetting;
        $failSetting = 2;

        $GLOBALS['input'] = json_encode(['user_id' => 0]);

        ob_start();
        include __DIR__ . '/../delete_user.php';
        $output = json_decode(ob_get_clean(), true);

        if ($this->testAssert->assertFalse($output['success'], "Should fail with invalid user ID")) {
            $this->passCount++;
        } else {
            $this->failCount++;
        }

        if ($this->testAssert->assertStringContains("Invalid user", $output['message'], "Should return correct error message")) {
            $this->passCount++;
        } else {
            $this->failCount++;
        }
    }

    /**
     * Test non-existent user
     * - Verifies proper error when user doesn't exist
     */
    public function testNonExistentUser()
    {
        global $failSetting;
        $failSetting = 3;

        $nonExistentUserId = 999999;
        $GLOBALS['input'] = json_encode(['user_id' => $nonExistentUserId]);

        ob_start();
        include __DIR__ . '/../delete_user.php';
        $output = json_decode(ob_get_clean(), true);

        if ($this->testAssert->assertFalse($output['success'], "Should fail with non-existent user")) {
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
        echo "=== Running Delete User Tests ===\n";

        $this->setUp();
        $this->testSuccessfulUserDeletion();
        $this->tearDown();

        $this->setUp();
        $this->testNonAdminAccess();
        $this->tearDown();

        $this->setUp();
        $this->testSelfDeletionPrevention();
        $this->tearDown();

        $this->setUp();
        $this->testInvalidUserId();
        $this->tearDown();

        $this->setUp();
        $this->testNonExistentUser();
        $this->tearDown();

        echo "=== Tests Complete ===\n";
    }
}

// Execute tests if run directly
if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    require_once __DIR__ . '/../db_connect.php';
    $test = new DeleteUserTest($pdo);
    $test->runAllTests();
}