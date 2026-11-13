<?php

/**
 * Usage: php Tests/test-environment.php setup|teardown
 *
 * "setup" runs as part of the Playwright web server command; "teardown" is for manual cleanup
 * (the Playwright global teardown does the same in TypeScript).
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
