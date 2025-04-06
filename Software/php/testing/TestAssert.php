<?php
/**
 * A simple assertion class providing basic testing functionality similar to PHPUnit
 */
class TestAssert
{
    /**
     * Asserts that a condition is true
     *
     * @param mixed $condition The condition to evaluate
     * @param string $message The message to display for the assertion
     * @return bool Returns true if $condition is true, false otherwise
     */
    public static function assertTrue($condition, $message): bool
    {
        if ($condition) {
            echo "✅ PASS: $message\n";
            return true;
        } else {
            echo "❌ FAIL: $message\n";
            return false;
        }
    }

    /**
     * Asserts that a condition is false
     *
     * @param mixed $condition The condition to evaluate
     * @param string $message The message to display for the assertion
     * @return bool Returns true if condition is false, false otherwise
     */
    public static function assertFalse($condition, $message): bool
    {
        if (!$condition) {
            echo "✅ PASS: $message\n";
            return true;
        } else {
            echo "❌ FAIL: $message\n";
            return false;
        }
    }

    /**
     * Asserts that a value is null
     * 
     * @param mixed $value The value to check
     * @param mixed $message The message to display for the assertion
     * @return bool Returns true if $value is null, false otherwise
     */
    public static function assertNull($value, $message): bool
    {
        if ($value === null) {
            echo "✅ PASS: $message\n";
            return true;
        } else {
            echo "❌ FAIL: $message\n";
            return false;
        }
    }

    /**
     * Asserts that a value is not null
     *
     * @param mixed $value The value to check
     * @param string $message The message to display for the assertion
     * @return bool Returns true if $value is not null, false otherwise
     */
    public static function assertNotNull($value, $message): bool
    {
        if ($value !== null) {
            echo "✅ PASS: $message\n";
            return true;
        } else {
            echo "❌ FAIL: $message\n";
            return false;
        }
    }

    /**
     * Asserts that two values are equal
     *
     * @param mixed $expected The expected value
     * @param mixed $actual The actual value to compare
     * @param string $message The message to display for the assertion
     * @return bool Returns true if $expected and $actual are identical, false otherwise
     */
    public static function assertEquals($expected, $actual, $message): bool
    {
        if ($expected === $actual) {
            echo "✅ PASS: $message\n";
            return true;
        } else {
            echo "❌ FAIL: $message (Expected: '$expected', Actual: '$actual')\n";
            return false;
        }
    }

    /**
     * Asserts that two values are not equal
     *
     * @param mixed $expected The value that should not match
     * @param mixed $actual The actual value to compare
     * @param string $message The message to display for the assertion
     * @return bool Returns true if $expected and $actual are not identical, false otherwise
     */
    public static function assertNotEquals($expected, $actual, $message): bool
    {
        if ($expected !== $actual) {
            echo "✅ PASS: $message\n";
            return true;
        } else {
            echo "❌ FAIL: $message (Expected not: '$expected', Actual: '$actual')\n";
            return false;
        }
    }

    /**
     * Asserts that a string contains another string
     *
     * @param string $needle The string to search for
     * @param string $haystack The string to search in
     * @param string $message The message to display for the assertion
     * @return bool Returns true if $needle is a substring of $haystack, false otherwise
     */
    public static function assertStringContains($needle, $haystack, $message): bool
    {
        if (strpos($haystack, $needle)) {
            echo "✅ PASS: $message\n";
            return true;
        } else {
            echo "❌ FAIL: $message (Expected to find: '$needle', In: '$haystack')\n";
            return false;
        }
    }

    /**
     * Asserts that a string does not contain another string
     *
     * @param string $needle The string that should not be found
     * @param string $haystack The string to search in
     * @param string $message The message to display for the assertion
     * @return bool Returns true if $needle is not a substring of $haystack, false otherwise
     */
    public static function assertStringNotContains($needle, $haystack, $message): bool
    {
        if (!strpos($haystack, $needle)) {
            echo "✅ PASS: $message\n";
            return true;
        } else {
            echo "❌ FAIL: $message (Found unexpected: '$needle')\n";
            return false;
        }
    }
}
?>
