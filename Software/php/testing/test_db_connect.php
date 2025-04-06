<?php
/**
 * Test class for verifying the database connection functionality
 * 
 * Tests the database connection established in db_connect.php
 * using the custom TestAssert class for assertions
 */
require_once __DIR__ . '/TestAssert.php';
require_once __DIR__ . '/../db_connect.php';

class DbConnect
{
    private $pdo;
    private $testAssert;

    /**
     * @var int $passCount Counter for passed assertions
     */
    public $passCount = 0;

    /**
     * @var int $failCount Counter for failed assertions
     */
    public $failCount = 0;


    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->testAssert = new TestAssert();
    }

    /**
     * Test if database connection is successfully established
     * 
     * Verifies that:
     * 1. The $pdo object is created
     * 2. The connection has no errors
     * 3. The connection can execute simple queries
     */
    public function testDatabaseConnection()
    {
        global $pdo; // Access the connection from db_connect.php
        if (
            // Test 1: Verify PDO object is created
            $this->testAssert->assertNotNull(
                $pdo,
                "PDO connection object should be created"
            )
        ) {
            // Test 1 passed
            $this->passCount++;
        } else {
            // Test 1 failed
            $this->failCount++;
        }
        if (
            // Test 2: Verify connection attributes
            $this->testAssert->assertEquals(
                PDO::ERRMODE_EXCEPTION,
                $pdo->getAttribute(PDO::ATTR_ERRMODE),
                "PDO should be set to exception error mode"
            )
        ) {
            // Test 2 passed
            $this->passCount++;
        } else {
            // Test 2 failed
            $this->failCount++;
        }
        // Test 3: Verify can execute simple query
        try {
            $result = $pdo->query("SELECT 1");
            if (
                $this->testAssert->assertTrue(
                    $result !== false,
                    "Should be able to execute simple query"
                )
            ) {
                // Test 3 passed
                $this->passCount++;
            } else {
                // Test 3 failed
                $this->failCount++;
            }

        } catch (PDOException $e) {
            $this->testAssert->assertTrue(
                false,
                "Query execution failed: " . $e->getMessage()
            );
            // Test 3 failed
            $this->failCount++;
        }

        // Test 4: Verify can access test tables
        try {
            $result = $pdo->query("SELECT COUNT(*) FROM users");
            if (
                $this->testAssert->assertTrue(
                    $result !== false,
                    "Should be able to access users table"
                )
            ) {
                // Test 4 passed
                $this->passCount++;
            } else {
                // Test 4 failed
                $this->failCount++;
            }
        } catch (PDOException $e) {
            $this->testAssert->assertTrue(
                false,
                "Table access failed: " . $e->getMessage()
            );
        }
    }

    /**
     * Run all test cases in this class
     */
    public function runAllTests()
    {
        echo "=== Running DB Connection Tests ===\n";
        $this->testDatabaseConnection();
        echo "=== Tests Complete ===\n";
    }
}

// Execute the tests if this file is run directly
if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    require_once 'db_connect.php'; // Defines $pdo
    $test = new DbConnect($pdo); // Inject the connection
    $test->runAllTests();
}
?>
