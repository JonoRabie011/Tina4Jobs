<?php

/**
 * Trait Tina4JobsTestHelperTrait
 * @package Tina4Jobs\test
 * @author Jonathan Rabie
 */

namespace Tina4Jobs\test;

Trait Tina4JobsTestHelperTrait
{

    /**
     * Helper function to print colorful output
     */
    protected function printResult(string $testName, bool $passed): void
    {
        $passText = $passed ? "\033[32mPASSED\033[0m" : "\033[31mFAILED\033[0m";
        echo sprintf("%-40s %s\n", $testName, $passText);
        echo "-----------------------------------------------\n";
    }

    protected function printHeader(string $header): void
    {
        echo "\n\033[1m$header\033[0m\n";
        echo "-----------------------------------------------\n";
    }
}