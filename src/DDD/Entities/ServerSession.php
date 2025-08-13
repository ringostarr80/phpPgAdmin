<?php

declare(strict_types=1);

namespace PhpPgAdmin\DDD\Entities;

use PhpPgAdmin\Config;
use PhpPgAdmin\Database\{Connection, PhpPgAdminConnection, Postgres};
use PhpPgAdmin\DDD\ValueObjects\Server\{DatabaseName, Filename, Host, Name, Port, SslMode};
use PhpPgAdmin\DDD\ValueObjects\ServerSession\{Username, Password, Platform};

/**
 * @property-read DatabaseName $DefaultDb
 * @property-read Host $Host
 * @property-read Name $Name
 * @property-read Password $Password
 * @property-read Filename $PgDumpAllPath
 * @property-read Filename $PgDumpPath
 * @property-read Platform $Platform
 * @property-read Port $Port
 * @property-read SslMode $SslMode
 * @property-read Username $Username
 */
class ServerSession extends Server
{
    public function __construct(
        private Username $username,
        private Password $password,
        Name $name,
        Host $host = new Host(),
        Port $port = new Port(),
        SslMode $sslMode = SslMode::ALLOW,
        DatabaseName $defaultDb = new DatabaseName('template1'),
        Filename $pgDumpPath = new Filename('/usr/bin/pg_dump'),
        Filename $pgDumpAllPath = new Filename('/usr/bin/pg_dumpall'),
        private Platform $platform = new Platform('PostgreSQL')
    ) {
        parent::__construct(
            name: $name,
            host: $host,
            port: $port,
            sslMode: $sslMode,
            defaultDb: $defaultDb,
            pgDumpPath: $pgDumpPath,
            pgDumpAllPath: $pgDumpAllPath
        );
    }

    public function __get(string $name): mixed
    {
        return match ($name) {
            'Password' => $this->password,
            'Platform' => $this->platform,
            'Username' => $this->username,
            default => parent::__get($name),
        };
    }

    public static function fromServerId(string $serverId): ?self
    {
        $servers = Config::getServers();
        foreach ($servers as $server) {
            if ($server->id() !== $serverId) {
                continue;
            }

            $sharedUsername = '';
            $sharedPassword = '';

            if (
                isset($_SESSION['sharedUsername']) &&
                is_string($_SESSION['sharedUsername']) &&
                isset($_SESSION['sharedPassword']) &&
                is_string($_SESSION['sharedPassword'])
            ) {
                $sharedUsername = $_SESSION['sharedUsername'];
                $sharedPassword = $_SESSION['sharedPassword'];
            }

            $platform = 'Unknown';

            if (
                $sharedUsername === '' &&
                $sharedPassword === '' &&
                isset($_SESSION['webdbLogin']) &&
                is_array($_SESSION['webdbLogin']) &&
                isset($_SESSION['webdbLogin'][$serverId]) &&
                is_array($_SESSION['webdbLogin'][$serverId])
            ) {
                if (
                    isset($_SESSION['webdbLogin'][$serverId]['username']) &&
                    is_string($_SESSION['webdbLogin'][$serverId]['username']) &&
                    isset($_SESSION['webdbLogin'][$serverId]['password']) &&
                    is_string($_SESSION['webdbLogin'][$serverId]['password'])
                ) {
                    $sharedUsername = $_SESSION['webdbLogin'][$serverId]['username'];
                    $sharedPassword = $_SESSION['webdbLogin'][$serverId]['password'];
                }

                if (
                    isset($_SESSION['webdbLogin'][$serverId]['platform']) &&
                    is_string($_SESSION['webdbLogin'][$serverId]['platform'])
                ) {
                    $platform = $_SESSION['webdbLogin'][$serverId]['platform'];
                }
            }

            if ($sharedUsername !== '' && $sharedPassword !== '') {
                return new self(
                    username: new Username($sharedUsername),
                    password: new Password($sharedPassword),
                    name: new Name((string)$server->Name),
                    host: new Host((string)$server->Host),
                    port: new Port($server->Port->Value),
                    sslMode: $server->SslMode,
                    defaultDb: new DatabaseName((string)$server->DefaultDb),
                    pgDumpPath: new Filename((string)$server->PgDumpPath),
                    pgDumpAllPath: new Filename((string)$server->PgDumpAllPath),
                    platform: new Platform($platform)
                );
            }
        }

        return null;
    }

    public static function fromRequestParameter(): ?self
    {
        if (!isset($_REQUEST['server']) || !is_string($_REQUEST['server'])) {
            return null;
        }

        return self::fromServerId($_REQUEST['server']);
    }

    public function getDatabaseConnection(): PhpPgAdminConnection
    {
        $connection = PhpPgAdminConnection::create(
            host: (string)$this->Host,
            port: $this->Port->Value,
            sslmode: $this->SslMode->value,
            user: (string)$this->Username,
            password: (string)$this->Password,
            database: 'postgres'
        );

        $connection->exec("SET client_encoding TO 'UTF-8'");
        $connection->exec("SET bytea_output TO escape");

        return $connection;
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
