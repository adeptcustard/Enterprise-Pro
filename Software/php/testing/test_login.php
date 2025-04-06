<?php
define('RUNNING_TESTS', true);
require_once 'TestAssert.php';

/**
 * Tests for login functionality
 */
class Login
{
    /** 
     * @var PDO $pdo Database connection
     */
    private $pdo;

    /**
     *  @var TestAssert $testAssert Custom assertion class instance 
     */
    private $testAssert;

    /** 
     * @var string $testEmail Test user email
     */
    private $testEmail = 'user1@yhrocu.uk';

    /**
     * @var string $testPassword user password 
     */
    private $testPassword = 'User@123';

    /** 
     * @var string $superEmail Supervisor email
     */
    private $superEmail = 'supervisor1@yhrocu.uk';

    /**
     * @var string $superPassword Supervisor password 
     */
    private $superPassword = 'Super@123';

    /** 
     * @var string $adminEmail Admin email 
     */
    private $adminEmail = 'admin@yhrocu.uk';

    /** 
     * @var string $adminPassword Admin password
     */
    private $adminPassword = 'Admin@123';

    /**
     * @var int $passCount Counter for passed assertions
     */
    public $passCount = 0;

    /**
     * @var int $failCount Counter for failed assertions
     */
    public $failCount = 0;

    /**    
     * @param PDO $pdo Database connection
     */
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->testAssert = new TestAssert();
    }
    /**
     * Set up test environment
     * Note: In a real scenario, you would mock the database or use a test database
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


        // Initialize empty input array
        $GLOBALS['input'] = [
            'email' => '',
            'password' => ''
        ];
    }

    /**
     * Clean up test environment
     */
    public function tearDown()
    {
        // Reset superglobals
        $_SESSION = [];
        $_SERVER = [];
        $GLOBALS['input'] = [];
    }

    /**
     * Test successful user login
     */
    public function testSuccessfulUserLogin()
    {
        // Create test input
        $GLOBALS['input'] = [
            'email' => $this->testEmail,
            'password' => $this->testPassword
        ];

        // Capture output
        ob_start();
        require '../login.php';
        $output = json_decode(ob_get_clean(), true);

        // Assertions
        if ($this->testAssert->assertTrue($output['success'], 'Login should be successful')) {
            $this->passCount++;
        } else {
            $this->failCount++;
        }
        if ($this->testAssert->assertEquals('../php/tasks_user.php', $output['redirect'], 'Should redirect to user tasks page')) {
            $this->passCount++;
        } else {
            $this->failCount++;
        }
        if ($this->testAssert->assertNotNull($_SESSION['user_id'], 'Session should have user_id')) {
            $this->passCount++;
        } else {
            $this->failCount++;
        }
        if ($this->testAssert->assertEquals($this->testEmail, $_SESSION['email'], 'Session email should match')) {
            $this->passCount++;
        } else {
            $this->failCount++;
        }
    }

    /**
     * Test successful Supervisor login
     */
    public function testSuccessfulSupervisorLogin()
    {
        // Create test input
        $GLOBALS['input'] = [
            'email' => $this->superEmail,
            'password' => $this->superPassword
        ];
        // Capture output
        ob_start();
        require '../login.php';
        $output = json_decode(ob_get_clean(), true);

        // Assertions
        if ($this->testAssert->assertTrue($output['success'], 'Supervisor login should be successful')) {
            $this->passCount++;
        } else {
            $this->failCount++;
        }
        if ($this->testAssert->assertEquals('../php/tasks_supervisor.php', $output['redirect'], 'Should redirect to Supervisor tasks page')) {
            $this->passCount++;
        } else {
            $this->failCount++;
        }
        if ($this->testAssert->assertEquals('Supervisor', $_SESSION['role'], 'Session role should be Supervisor')) {
            $this->passCount++;
        } else {
            $this->failCount++;
        }
    }

    /**
     * Test successful admin login
     */
    public function testSuccessfulAdminLogin()
    {
        // Create test input
        $GLOBALS['input'] = [
            'email' => $this->adminEmail,
            'password' => $this->adminPassword
        ];
        // Capture output
        ob_start();
        require '../login.php';
        $output = json_decode(ob_get_clean(), true);

        // Assertions
        if ($this->testAssert->assertTrue($output['success'], 'Admin login should be successful')) {
            $this->passCount++;
        } else {
            $this->failCount++;

        }
        if ($this->testAssert->assertEquals('../php/tasks_admin.php', $output['redirect'], 'Should redirect to admin tasks page')) {
            $this->passCount++;
        } else {
            $this->failCount++;
        }
        if ($this->testAssert->assertEquals('Admin', $_SESSION['role'], 'Session role should be Admin')) {
            $this->passCount++;
        } else {
            $this->failCount++;
        }

    }

    /**
     * Test login with invalid credentials
     */
    public function testInvalidCredentials()
    {
        // Create test input
        $GLOBALS['input'] = [
            'email' => $this->testEmail,
            'password' => 'wrongpassword'
        ];

        // Capture output
        ob_start();
        require '../login.php';
        $output = json_decode(ob_get_clean(), true);

        if ($this->testAssert->assertFalse($output['success'], 'Login should fail with wrong password')) {
            $this->passCount++;
        } else {
            $this->failCount++;
        }
        if ($this->testAssert->assertEquals('Invalid Email or Password', $output['message'], 'Should return invalid credentials message')) {
            $this->passCount++;
        } else {
            $this->failCount++;
        }
        if ($this->testAssert->assertTrue(empty($_SESSION['user_id']), 'Session should not have user_id')) {
            $this->passCount++;
        } else {
            $this->failCount++;
        }
    }

    /**
     * Test login with empty fields
     */
    public function testEmptyFields()
    {
        // Create test input
        $GLOBALS['input'] = [
            'email' => '',
            'password' => ''
        ];

        // Capture output
        ob_start();
        require '../login.php';
        $output = json_decode(ob_get_clean(), true);

        if ($this->testAssert->assertFalse($output['success'], 'Login should fail with empty fields')) {
            $this->passCount++;
        } else {
            $this->failCount++;
        }
        //$this->testAssert->assertEquals('Email and Password are required.', $output['message'], 'Should return required fields message');
    }

    /**
     * Test login with invalid email format
     */
    public function testInvalidEmailFormat()
    {
        // Create test input
        $GLOBALS['input'] = [
            'email' => 'not-an-email',
            'password' => $this->testPassword
        ];

        // Capture output
        ob_start();
        require '../login.php';
        $output = json_decode(ob_get_clean(), true);

        if ($this->testAssert->assertFalse($output['success'], 'Login should fail with invalid email')) {
            $this->passCount++;
        } else {
            $this->failCount++;
        }
        //$this->testAssert->assertEquals('Invalid Email Format.', $output['message'], 'Should return invalid email format message');
    }

    /**
     * Test login with non-existent email
     */
    public function testNonExistentEmail()
    {
        // Create test input
        $GLOBALS['input'] = [
            'email' => 'nonexistant@example.com',
            'password' => $this->testPassword
        ];

        ob_start();
        require '../login.php';
        $output = json_decode(ob_get_clean(), true);

        if ($this->testAssert->assertFalse($output['success'], 'Login should fail with non-existent email')) {
            $this->passCount++;
        } else {
            $this->failCount++;
        }
        if ($this->testAssert->assertEquals('Invalid Email or Password', $output['message'], 'Should return invalid credentials message')) {
            $this->passCount++;
        } else {
            $this->failCount++;
        }
    }

    /**
     * Test login with invalid request method
     */
    public function testInvalidRequestMethod()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET'; // Explicitly set to GET

        ob_start();
        require '../login.php';
        $output = json_decode(ob_get_clean(), true);

        if ($this->testAssert->assertNull($output, 'Output should be null for GET request')) {
            $this->passCount++;
        } else {
            $this->failCount++;
        }
        if ($this->testAssert->assertTrue(empty($_SESSION), 'Session should be empty for GET request')) {
            $this->passCount++;
        } else {
            $this->failCount++;
        }

        if ($this->testAssert->assertFalse($output['success'], 'Should fail with GET request')) {
            $this->passCount++;
        } else {
            $this->failCount++;
        }
    }


    /**
     * Run all test cases in this class
     */
    public function runAllTests()
    {
        echo "=== Running Login Tests ===\n";

        $this->setUp();
        $this->testSuccessfulUserLogin();
        $this->tearDown();

        $this->setUp();
        $this->testSuccessfulSupervisorLogin();
        $this->tearDown();

        $this->setUp();
        $this->testSuccessfulAdminLogin();
        $this->tearDown();

        $this->setUp();
        $this->testInvalidCredentials();
        $this->tearDown();

        $this->setUp();
        $this->testEmptyFields();
        $this->tearDown();

        $this->setUp();
        $this->testInvalidEmailFormat();
        $this->tearDown();

        $this->setUp();
        $this->testNonExistentEmail();
        $this->tearDown();

        $this->setUp();
        $this->testInvalidRequestMethod();
        $this->tearDown();

        echo "=== Tests Complete ===\n";
    }
}

// Execute the tests if this file is run directly
if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    require_once '../db_connect.php'; // Defines $pdo
    $test = new Login($pdo); // Inject the connection
    $test->runAllTests();
}
