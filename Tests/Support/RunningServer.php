<?php

declare(strict_types=1);

namespace Tests\Support;

use PhpPgAdmin\Config;
use PhpPgAdmin\Database\PhpPgAdminConnection;
use PhpPgAdmin\Infrastructure\Session\Logins;
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
        self::logInAs(
            TestEnvironment::getEnvVar('PHPPGADMIN_TEST_SERVER_USERNAME') ?? 'postgres',
            TestEnvironment::getEnvVar('PHPPGADMIN_TEST_SERVER_PASSWORD') ?? '',
        );
    }

    /**
     * Logs in to it as the given role, in a session that has no other login. connection() then connects as that role.
     */
    public static function logInAs(string $username, string $password): void
    {
        $_SESSION = [
            'webdbLogin' => [
                self::id() => [
                    'password' => $password,
                    'username' => $username,
                ],
            ],
        ];
    }

    /**
     * A connection as the user of the tests, after logIn().
     */
    public static function connection(): PhpPgAdminConnection
    {
        $connection = PhpPgAdminConnection::createFromServerSession(
            Logins::serverSession(self::id(), Config::getServers()),
        );
        Assert::assertNotNull($connection);

        return $connection;
    }
}
