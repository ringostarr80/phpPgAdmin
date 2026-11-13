<?php

/**
 * PHPUnit bootstrap: writes the test configuration before the first test and removes it after the run.
 *
 * Config caches the configuration on first use, so it has to be in place before any test runs.
 */

declare(strict_types=1);

use Tests\Support\TestEnvironment;

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'autoload.php';

// Infection runs the mutants in parallel and gives every thread its own TEST_TOKEN. One configuration file per thread
// keeps a finished process from removing the file another one still reads.
$testToken = getenv('TEST_TOKEN');
$configYamlFile = is_string($testToken) && $testToken !== ''
    ? "config-test-{$testToken}.yml"
    : TestEnvironment::CONFIG_YAML_FILE;

TestEnvironment::setUp($configYamlFile);
register_shutdown_function(static fn () => TestEnvironment::tearDown($configYamlFile));
