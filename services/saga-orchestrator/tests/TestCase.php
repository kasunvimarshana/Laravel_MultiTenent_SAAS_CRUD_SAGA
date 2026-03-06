<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

/**
 * Base test case for the SAGA Orchestrator service.
 *
 * Extends Laravel's foundation test case to provide the full application
 * container, database transaction helpers, and HTTP testing utilities for
 * feature tests.  Pure unit tests may extend PHPUnit\Framework\TestCase
 * directly to avoid the overhead of booting the application.
 */
abstract class TestCase extends BaseTestCase
{
    //
}
