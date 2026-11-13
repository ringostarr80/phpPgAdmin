<?php

/**
 * PHPUnit bootstrap: writes the test configuration before the first test and removes it after the run.
 *
 * Config caches the configuration on first use, so it has to be in place before any test runs.
 */

declare(strict_types=1);

use Tests\Support\TestEnvironment;

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'autoload.php';

TestEnvironment::setUp();
register_shutdown_function(TestEnvironment::tearDown(...));
