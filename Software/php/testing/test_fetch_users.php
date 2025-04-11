<?php
define('RUNNING_TESTS', true);
/**
 * Test class for fetch_users.php functionality
 * 
 * Tests user fetching including:
 * - Admin role verification
 * - Proper user data structure
 * - Exclusion of current admin
 * - Error handling
 */
require_once __DIR__ . '/TestAssert.php';
include_once __DIR__ . '/../db_connect.php'; // Defines $pdo

class FetchUsersTest
{
    private $testAssert;
    private $pdo;
    public $passCount = 0;
    public $failCount = 0;
    private $testAdminId;
    private $testUserId;

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

        // Initialize input array with admin login values
        $GLOBALS['input'] = [
            'email' => 'admin@yhrocu.uk',
            'password' => 'Admin@123'
        ];
        echo "Admin login should redirect to 'html/verify_otp.html' see below ⬇️\n";
        require '../login.php';

        try {
            // Verify admin login
            if (!isset($_SESSION['user_id'])) {
                die("❌ Failed to log in test admin user");
            }

            $this->testAdminId = $_SESSION['user_id'];
            $_SESSION['role'] = 'Admin';

            // Create test user
            $stmt = $this->pdo->prepare(
                "INSERT INTO users (email, password_hash, first_name, last_name, role) 
                 VALUES ('testuser@example.com', 'hashed_password', 'Test', 'User', 'User')
                 RETURNING id"
            );
            $stmt->execute();
            $this->testUserId = $stmt->fetchColumn();

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
            $this->pdo->exec("DELETE FROM users WHERE email = 'testuser@example.com'");
        } catch (PDOException $e) {
            error_log("Cleanup error: " . $e->getMessage());
        }

        session_unset();
        session_destroy();
        $_SESSION = [];
    }

    /**
     * Test successful user fetch with admin session
     */
    public function testSuccessfulUserFetch()
    {
        ob_start();
        include __DIR__ . '/../fetch_users.php';
        $output = json_decode(ob_get_clean(), true);
        echo "\n";

        // Test basic response
        if (
            $this->testAssert->assertTrue(
                $output['success'],
                "Valid admin request should return success"
            )
        ) {
            $this->passCount++;
        } else {
            $this->failCount++;
        }

        // Test users array exists
        if (
            $this->testAssert->assertNotNull(
                $output['users'] ?? null,
                "Response should contain users array"
            )
        ) {
            $this->passCount++;
        } else {
            $this->failCount++;
        }

        // Test user structure
        if (!empty($output['users'])) {
            $user = $output['users'][0];
            $requiredFields = ['id', 'first_name', 'last_name', 'email', 'role'];
            foreach ($requiredFields as $field) {
                if (
                    $this->testAssert->assertNotNull(
                        $user[$field] ?? null,
                        "User should have $field field"
                    )
                ) {
                    $this->passCount++;
                } else {
                    $this->failCount++;
                }
            }

            // Verify admin is excluded
            $adminFound = false;
            foreach ($output['users'] as $user) {
                if ($user['id'] == $this->testAdminId) {
                    $adminFound = true;
                    break;
                }
            }

            if (
                $this->testAssert->assertFalse(
                    $adminFound,
                    "Admin user should be excluded from results"
                )
            ) {
                $this->passCount++;
            } else {
                $this->failCount++;
            }
        }
    }

    /**
     * Test unauthorized access (non-admin role)
     */
    public function testUnauthorizedAccess()
    {
        global $failSetting;
        $failSetting = 1;
        $_SESSION['role'] = 'User'; // Change role to non-admin

        ob_start();
        include __DIR__ . '/../fetch_users.php';
        $output = json_decode(ob_get_clean(), true);
        echo "\n";

        if (
            $this->testAssert->assertFalse(
                $output['success'],
                "Non-admin request should fail"
            )
        ) {
            $this->passCount++;
        } else {
            $this->failCount++;
        }

        if (
            $this->testAssert->assertEquals(
                "Unauthorized",
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
     * Test unauthenticated access
     */
    public function testUnauthenticatedAccess()
    {
        global $failSetting;
        $failSetting = 1;
        unset($_SESSION['user_id']);
        unset($_SESSION['role']);

        ob_start();
        include __DIR__ . '/../fetch_users.php';
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
                "Unauthorized",
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
     * Run all tests
     */
    public function runAllTests()
    {
        echo "=== Running Fetch Users Tests ===\n";

        $this->setUp();
        $this->testSuccessfulUserFetch();
        $this->tearDown();

        $this->setUp();
        $this->testUnauthorizedAccess();
        $this->tearDown();

        $this->setUp();
        $this->testUnauthenticatedAccess();
        $this->tearDown();

        echo "=== Tests Complete ===\n";
    }
}

// Execute tests if run directly
if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    $test = new FetchUsersTest();
    $test->runAllTests();
}