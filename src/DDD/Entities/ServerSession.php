<?php

declare(strict_types=1);

namespace PhpPgAdmin\DDD\Entities;

use PhpPgAdmin\Config;
use PhpPgAdmin\DDD\ValueObjects\Server\{DatabaseName, Filename, Host, Name, Port, SslMode};
use PhpPgAdmin\DDD\ValueObjects\ServerSession\{Username, Password};

/**
 * @property-read DatabaseName $DefaultDb
 * @property-read Host $Host
 * @property-read Name $Name
 * @property-read Password $Password
 * @property-read Filename $PgDumpAllPath
 * @property-read Filename $PgDumpPath
 * @property-read Port $Port
 * @property-read SslMode $SslMode
 * @property-read Username $Username
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
            'DefaultDb' => $this->defaultDb,
            'Host' => $this->host,
            'Name' => $this->name,
            'Password' => $this->password,
            'PgDumpAllPath' => $this->pgDumpAllPath,
            'PgDumpPath' => $this->pgDumpPath,
            'Port' => $this->port,
            'SslMode' => $this->sslMode,
            'Username' => $this->username,
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

    public static function isLoggedIn(string $serverId): bool
    {
        if (
            isset($_SESSION['webdbLogin']) &&
            is_array($_SESSION['webdbLogin']) &&
            isset($_SESSION['webdbLogin'][$serverId]) &&
            is_array($_SESSION['webdbLogin'][$serverId]) &&
            isset($_SESSION['webdbLogin'][$serverId]['username']) &&
            is_string($_SESSION['webdbLogin'][$serverId]['username']) &&
            $_SESSION['webdbLogin'][$serverId]['username'] !== '' &&
            isset($_SESSION['webdbLogin'][$serverId]['password']) &&
            is_string($_SESSION['webdbLogin'][$serverId]['password']) &&
            $_SESSION['webdbLogin'][$serverId]['password'] !== ''
        ) {
            return true;
        }

        return false;
    }
}
