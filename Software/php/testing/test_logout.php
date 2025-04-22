<?php
/**
 * Test class for logout.php functionality
 * 
 * Tests logout functionality including:
 * - Session destruction
 * - Redirection header
 */
require_once __DIR__ . '/TestAssert.php';

class LogoutTest
{
    private $testAssert;

    /**
     * @var int $passCount Counter for passed assertions
     */
    public $passCount = 0;

    /**
     * @var int $failCount Counter for failed assertions
     */
    public $failCount = 0;

    public function __construct()
    {
        $this->testAssert = new TestAssert();
    }

    /**
     * Set up test environment
     * - Starts session
     * - Sets test session variables
     */
    public function setUp()
    {
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
            'email' => 'user1@yhrocu.uk',
            'password' => 'User@123'
        ];
        echo "Login should redirect to 'php/tasks_user.php' see below ⬇️\n";
        require '../login.php';
    }

    /**
     * Clean up test environment
     * - Clears any remaining session data
     */
    public function tearDown()
    {
        // Reset superglobals
        $_SESSION = [];
        $_SERVER = [];
        $GLOBALS['input'] = [];
    }

    /**
     * Test session destruction
     */
    public function testSessionDestruction()
    {
        echo "\n";
        // Verify session exists before logout
        if (
            $this->testAssert->assertTrue(
                isset($_SESSION['user_id']),
                "Session should have user_id before logout"
            )
        ) {
            $this->passCount++;
        } else {
            $this->failCount++;
        }

        ob_start();
        include __DIR__ . '/../logout.php';
        ob_end_clean();

        // Verify session is destroyed
        if (
            $this->testAssert->assertTrue(
                empty($_SESSION),
                "Session should be empty after logout"
            )
        ) {
            $this->passCount++;
        } else {
            $this->failCount++;

        }

    }

    /**
     * Test redirection
     */
    public function testRedirection()
    {
        echo "\n";
        // Run logout script

        ob_start();
        include __DIR__ . '/../logout.php';
        $output = json_decode(ob_get_clean(), true);

        if (
            $this->testAssert->assertEquals(
                '../html/login.html',
                $output['Location'],
                "Should redirect to login page"
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
        echo "=== Running Logout Tests ===\n";
        $this->setUp();
        $this->testSessionDestruction();
        $this->tearDown();
        $this->setUp();
        $this->testRedirection();
        $this->tearDown();
        echo "=== Tests Complete ===\n";
    }
}

// Execute tests if run directly
if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    $test = new LogoutTest();
    $test->runAllTests();
}
?>