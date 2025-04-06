<?php
require_once __DIR__ . '/../db_connect.php';
/**
 * TestRunner - A comprehensive test suite executor with CLI and HTML reporting
 */
class TestRunner
{
    /**
     * @var TestAssert $testAssert Instance of assertion utility class
     */
    private $testAssert;

    /**
     * @var array $testClasses List of all test files to execute (without .php extension)
     */
    private $testClasses = [
        // Core functionality tests
        'test_db_connect',
        'test_login',

        'test_logout',

        // Task-related tests
        'test_add_comment',
        'test_fetch_tasks',
        'test_update_task_status',
        'test_upload_task_file',

        // User/admin tests
        'test_fetch_users',
        'test_users_admin',

        // Supervisor tests
        'test_fetch_supervisor_tasks',
        'test_tasks_supervisor'

    ];

    /**
     * @var int $totalPass Counter for total passed assertions
     */
    private $totalPass = 0;

    /**
     * @var int $totalFail Counter for total failed assertions
     */
    private $totalFail = 0;

    /**
     * @var float $startTime Test suite execution start time
     */
    private $startTime;

    /**
     * Constructor - Initializes the test runner
     */
    public function __construct()
    {
        $this->testAssert = new TestAssert();
        $this->startTime = microtime(true);
    }

    /**
     * Converts snake_case filename to PascalCase class name
     * @param string $filename The test filename (without .php extension)
     * 
     * @return string The corresponding class name in PascalCase
     */
    private function filenameToClassName($filename)
    {
        $stripped = strpos($filename, 'test_') === 0 ? substr($filename, 5) : $filename;
        return str_replace(' ', '', ucwords(str_replace('_', ' ', $stripped)));
    }

    /**
     * Runs all registered test classes and generates reports
     * @return void Outputs test results in CLI or HTML format based on the environment
     */
    public function runAllTests()
    {
        ob_start(); // Start output buffering
        if (php_sapi_name() === 'cli') {
            $this->runCliTests();
        } else {
            $this->runHtmlTests();
        }
        $output = ob_get_clean(); // Capture output without sending headers
    }

    /**
     * Executes tests with CLI output format
     * @return void Outputs test results in CLI format
     */
    private function runCliTests()
    {
        echo "=== Starting Test Suite ===\n\n";

        foreach ($this->testClasses as $testFile) {
            try {
                $this->executeTest($testFile, 'cli');
            } catch (Throwable $e) {
                echo "! Test {$testFile} crashed: " . $e->getMessage() . "\n";
                $this->totalFail++;
                continue; // Move to next test
            }
        }

        $this->outputCliSummary();
    }

    /**
     * Executes tests with HTML output format
     * @return void Outputs test results in HTML format
     */
    private function runHtmlTests()
    {
        header('Content-Type: text/html');
        echo '<!DOCTYPE html>
        <html>
        <head>
            <title>Test Suite Results</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                .test-class { margin-bottom: 30px; border-bottom: 1px solid #ddd; padding-bottom: 15px; }
                .pass { color: green; }
                .fail { color: red; }
                .summary { background: #f5f5f5; padding: 15px; margin-top: 20px; }
                pre { background: #f0f0f0; padding: 10px; border-radius: 4px; }
            </style>
        </head>
        <body>
        <h1>Test Suite Results</h1>';

        foreach ($this->testClasses as $testFile) {
            ob_start();
            $this->executeTest($testFile, 'html');
            $output = ob_get_clean();

            echo '<div class="test-class">
                <h2>' . htmlspecialchars($this->filenameToClassName($testFile)) . '</h2>
                <pre>' . htmlspecialchars($output) . '</pre>
              </div>';
        }

        $this->outputHtmlSummary();
        echo '</body></html>';
    }

    /**
     * Executes a single test file and captures its output
     * @param string $testFile The test filename (without .php extension)
     * @param string $format Output format ('cli' or 'html')
     * 
     * @throws RuntimeException If the test file or class is not found
     * @throws PDOException If database connection fails (for DbConnect tests)
     * 
     * @return void Modifies internal pass/fail counters and outputs test results
     */
    private function executeTest($testFile, $format)
    {
        global $pdo; // Access the PDO connection

        $fullPath = "{$testFile}.php";

        if (!file_exists($fullPath)) {
            $msg = "❌ Test file not found: {$testFile}.php";
            if ($format === 'cli')
                echo "$msg\n";
            $this->totalFail++;
            return;
        }

        require_once $fullPath;
        $className = $this->filenameToClassName($testFile);

        if (!class_exists($className)) {
            $msg = "❌ Test class {$className} not found in {$testFile}.php";
            if ($format === 'cli')
                echo "$msg\n";
            $this->totalFail++;
            return;
        }

        if ($format === 'cli') {
            echo "🚀 Running {$className} Tests\n";
            echo str_repeat("=", strlen($className) + 8) . "\n";
        }

        try {
            // Special case for DbConnect - pass the PDO connection
            if ($className === 'DbConnect' || $className === 'Login') {
                $test = new $className($pdo);
            } else {
                $test = new $className();
            }

            $test->runAllTests();

            if (property_exists($test, 'passCount')) {
                $this->totalPass += $test->passCount;
            }
            if (property_exists($test, 'failCount')) {
                $this->totalFail += $test->failCount;
            }
        } catch (Exception $e) {
            $msg = "❌ ERROR: Failed to run {$className} - " . $e->getMessage();
            if ($format === 'cli')
                echo "$msg\n";
            $this->totalFail++;
        } catch (Throwable $e) {
            $msg = "❌ ERROR: Failed to run {$className}\nTEST CRASHED: " . $e->getMessage();
            if ($format === 'cli')
                echo "$msg\n";
            $this->totalFail++;
            error_log($msg . "\n" . $e->getTraceAsString());
            return; // Continue to next test instead of dying
        }

        if ($format === 'cli')
            echo "\n";
    }

    /**
     * Outputs CLI-formatted test summary
     * @return void Outputs test summary in CLI format
     */
    private function outputCliSummary()
    {
        $duration = round(microtime(true) - $this->startTime, 2);
        $successRate = $this->calculateSuccessRate();

        echo "=== Test Suite Complete ===\n";
        echo "⏱️  Duration: {$duration}s\n";
        echo "✅ TOTAL PASSED: {$this->totalPass}\n";
        echo "❌ TOTAL FAILED: {$this->totalFail}\n";
        echo "📊 SUCCESS RATE: {$successRate}%\n";
        echo str_repeat("=", 30) . "\n";
    }

    /**
     * Outputs HTML-formatted test summary
     * @return void Outputs test summary in HTML format
     */
    private function outputHtmlSummary()
    {
        $duration = round(microtime(true) - $this->startTime, 2);
        $successRate = $this->calculateSuccessRate();

        echo '<div class="summary">
            <h2>Test Suite Summary</h2>
            <p><strong>Duration:</strong> ' . htmlspecialchars($duration) . ' seconds</p>
            <p class="pass"><strong>Passed:</strong> ' . htmlspecialchars($this->totalPass) . '</p>
            <p class="fail"><strong>Failed:</strong> ' . htmlspecialchars($this->totalFail) . '</p>
            <p><strong>Success Rate:</strong> ' . htmlspecialchars($successRate) . '%</p>
          </div>';
    }

    /**
     * Calculates test suite success rate
     * @return float The success rate as a percentage
     */
    private function calculateSuccessRate()
    {
        $total = $this->totalPass + $this->totalFail;
        return $total > 0 ? round(($this->totalPass / $total) * 100, 2) : 0;
    }
}

// Execute all tests if run directly
if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    require_once 'TestAssert.php';
    $runner = new TestRunner();
    $runner->runAllTests();
}
