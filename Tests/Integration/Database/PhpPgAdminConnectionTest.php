<?php

declare(strict_types=1);

namespace Tests\Integration\Database;

use PhpPgAdmin\Config;
use PhpPgAdmin\Database\PhpPgAdminConnection;
use PhpPgAdmin\DDD\Entities\Server;
use PhpPgAdmin\DDD\ValueObjects\Server\{Filename, Host, Name, Port};
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tests\Support\TestEnvironment;

#[CoversClass(PhpPgAdminConnection::class)]
final class PhpPgAdminConnectionTest extends TestCase
{
    public function testLoginDataIsValidForTheRunningServer(): void
    {
        self::assertTrue(self::loginDataIsValid(self::configuredServer(TestEnvironment::RUNNING_SERVER_DESC)));
    }

    public function testLoginDataIsInvalidForTheNotRunningServer(): void
    {
        // Built here instead of read from the configuration: loading the configuration can log missing pg_dump paths,
        // which would satisfy expectErrorLog() even if loginDataIsValid() logged nothing.
        $server = new Server(
            name: new Name(TestEnvironment::NOT_RUNNING_SERVER_DESC),
            host: new Host(TestEnvironment::NOT_RUNNING_SERVER_HOST),
            port: new Port(TestEnvironment::NOT_RUNNING_SERVER_PORT),
            pgDumpPath: new Filename(__FILE__),
            pgDumpAllPath: new Filename(__FILE__),
        );

        // loginDataIsValid() logs the connection error.
        $this->expectErrorLog();

        self::assertFalse(self::loginDataIsValid($server));
    }

    private static function configuredServer(string $description): Server
    {
        foreach (Config::getServers() as $server) {
            if ((string)$server->Name === $description) {
                return $server;
            }
        }

        self::fail("No server \"{$description}\" in the test configuration.");
    }

    private static function loginDataIsValid(Server $server): bool
    {
        return PhpPgAdminConnection::loginDataIsValid(
            host: (string)$server->Host,
            port: $server->Port->Value,
            sslmode: $server->SslMode,
            user: TestEnvironment::getEnvVar('PHPPGADMIN_TEST_SERVER_USERNAME') ?? 'postgres',
            password: TestEnvironment::getEnvVar('PHPPGADMIN_TEST_SERVER_PASSWORD') ?? '',
        );
    }
}
