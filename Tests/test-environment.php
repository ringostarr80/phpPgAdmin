<?php

/**
 * Usage: php Tests/test-environment.php setup|teardown
 *
 * Called by the Playwright config (setup, as part of the web server command) and its global teardown.
 */

declare(strict_types=1);

use Tests\Support\TestEnvironment;

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

$command = $argv[1] ?? '';

if ($command === 'setup') {
    TestEnvironment::setUp();
} elseif ($command === 'teardown') {
    TestEnvironment::tearDown();
} else {
    fwrite(STDERR, "Usage: php Tests/test-environment.php setup|teardown\n");
    exit(1);
}
