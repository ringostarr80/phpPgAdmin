<?php

declare(strict_types=1);

namespace Tests\Support;

use PhpPgAdmin\Config;
use PHPUnit\Framework\Assert;

/**
 * The server of the test configuration that runs, for the tests of the pages that need a login.
 */
final class RunningServer
{
    public static function id(): string
    {
        foreach (Config::getServers() as $server) {
            if ((string)$server->Name === TestEnvironment::RUNNING_SERVER_DESC) {
                return $server->id();
            }
        }

        Assert::fail('No server "' . TestEnvironment::RUNNING_SERVER_DESC . '" in the test configuration.');
    }

    /**
     * Logs in to it as the user of the tests, in a session that has no other login.
     */
    public static function logIn(): void
    {
        $_SESSION = [
            'webdbLogin' => [
                self::id() => [
                    'password' => TestEnvironment::getEnvVar('PHPPGADMIN_TEST_SERVER_PASSWORD') ?? '',
                    'username' => TestEnvironment::getEnvVar('PHPPGADMIN_TEST_SERVER_USERNAME') ?? 'postgres',
                ],
            ],
        ];
    }
}
