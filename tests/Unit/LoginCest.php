<?php

declare(strict_types=1);

namespace Tests\Unit;

use PhpPgAdmin\Config;
use PhpPgAdmin\Database\Connection;
use Tests\Support\UnitTester;

final class LoginCest
{
    // @phpcs:disable
    public function _before(UnitTester $I): void
    {
        // Code here will be executed before each test.
    }

    public function tryToTestLogin(UnitTester $i): void
    {
        $loginUsername = $_ENV['PHPPGADMIN_TEST_SERVER_USERNAME'] ?? 'postgres';
        $loginPassword = '';
        if (isset($_ENV['PHPPGADMIN_TEST_SERVER_PASSWORD']) && is_string($_ENV['PHPPGADMIN_TEST_SERVER_PASSWORD'])) {
            $loginPassword = $_ENV['PHPPGADMIN_TEST_SERVER_PASSWORD'];
        } else {
            $envPassword = getenv('PHPPGADMIN_TEST_SERVER_PASSWORD');
            if (is_string($envPassword)) {
                $loginPassword = $envPassword;
            }
        }

        $checkedServers = 0;
        $servers = Config::getServers();
        foreach ($servers as $server) {
            if ((string)$server->Name !== 'Running Server') {
                continue;
            }
            $valid = Connection::loginDataIsValid(
                host: (string)$server->Host,
                port: $server->Port->Value,
                sslmode: $server->SslMode,
                user: $loginUsername,
                password: $loginPassword
            );
            $i->assertTrue($valid);
            $checkedServers++;
        }

        $i->assertGreaterThan(0, $checkedServers);
    }
}
