<?php

namespace ReallySpecific\SamplePlugin\Dependencies\RS_Utils\Testing;

use ReallySpecific\SamplePlugin\Dependencies\PHPUnit\Framework\TestCase as PhpUnitTestCase;
use ReallySpecific\SamplePlugin\Dependencies\RS_Utils\Mock_WP;
/**
 * Mock_WP test case.
 *
 * Projects using Mock_WP can extend this class in their unit tests.
 */
abstract class TestCase extends PhpUnitTestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        Mock_WP::setup_test_case();
    }
}
