<?php

declare(strict_types=1);

namespace PhpPgAdmin\DDD\Entities;

use PhpPgAdmin\Config;
use PhpPgAdmin\DDD\ValueObjects\Server\{DatabaseName, Filename, Host, Name, Port, SslMode};
use PhpPgAdmin\DDD\ValueObjects\ServerSession\{Username, Password};

/**
 * @property-read Username $Username
 * @property-read Password $Password
 */
class ServerSession extends Server
{
    public function __construct(
        private Username $username,
        private Password $password,
        private Name $name,
        private Host $host = new Host(),
        private Port $port = new Port(),
        private SslMode $sslMode = SslMode::ALLOW,
        private DatabaseName $defaultDb = new DatabaseName('template1'),
        private Filename $pgDumpPath = new Filename('/usr/bin/pg_dump'),
        private Filename $pgDumpAllPath = new Filename('/usr/bin/pg_dumpall')
    ) {
    }

    public function __get(string $name): mixed
    {
        return match ($name) {
            'Username' => $this->username,
            'Password' => $this->password,
            default => parent::__get($name),
        };
    }

    public static function fromRequestParameter(): ?self
    {
        if (!isset($_REQUEST['server']) || !is_string($_REQUEST['server'])) {
            return null;
        }

        $serverId = $_REQUEST['server'];
        $servers = Config::getServers();
        foreach ($servers as $server) {
            if ($server->id() !== $serverId) {
                continue;
            }

            if (
                isset($_SESSION['sharedUsername']) &&
                is_string($_SESSION['sharedUsername']) &&
                isset($_SESSION['sharedPassword']) &&
                is_string($_SESSION['sharedPassword'])
            ) {
                return new self(
                    username: new Username($_SESSION['sharedUsername']),
                    password: new Password($_SESSION['sharedPassword']),
                    name: new Name((string)$server->Name),
                    host: new Host((string)$server->Host),
                    port: new Port($server->Port->Value),
                    sslMode: $server->SslMode,
                    defaultDb: new DatabaseName((string)$server->DefaultDb),
                    pgDumpPath: new Filename((string)$server->PgDumpPath),
                    pgDumpAllPath: new Filename((string)$server->PgDumpAllPath)
                );
            }
        }

        return null;
    }
}
