<?php

declare(strict_types=1);

namespace PhpPgAdmin\Database;

use PhpPgAdmin\DDD\ValueObjects\Server\SslMode;

class Connection
{
    public \ADOConnection $conn;

    public string $platform = 'UNKNOWN';

    /**
     * Creates a new connection.  Will actually make a database connection.
     * @param $fetchMode Defaults to associative.  Override for different behaviour
     */
    public function __construct(
        ?string $host,
        ?int $port,
        string $sslmode,
        string $user,
        string $password,
        string $database,
        int $fetchMode = ADODB_FETCH_ASSOC
    ) {
        $this->conn = ADONewConnection('postgres') ?: throw new \Exception('Connection failed');
        $this->conn->setFetchMode($fetchMode);

        // Ignore host if null
        if ($host === null || $host == '') {
            if ($port !== null && $port !== 0) {
                $pghost = ':' . $port;
            } else {
                $pghost = '';
            }
        } else {
            $pghost = "{$host}:{$port}";
        }

        // Add sslmode to $pghost as needed
        if (($sslmode == 'disable') || ($sslmode == 'allow') || ($sslmode == 'prefer') || ($sslmode == 'require')) {
            $pghost .= ':' . $sslmode;
        } elseif ($sslmode == 'legacy') {
            $pghost .= ' requiressl=1';
        }

        if ($this->conn->connect($pghost, $user, $password, $database) === false) {
            throw new \Exception('Connection failed');
        }
    }

    public static function loginDataIsValid(
        ?string $host,
        ?int $port,
        SslMode $sslmode,
        string $user,
        string $password
    ): bool {
        $conn = ADONewConnection('postgres');
        if ($conn === false) {
            print 'if ($conn === false)';
            return false;
        }

        // Ignore host if null
        if ($host === null || $host == '') {
            if ($port !== null && $port !== 0) {
                $pghost = ':' . $port;
            } else {
                $pghost = '';
            }
        } else {
            $pghost = "{$host}:{$port}";
        }

        // Add sslmode to $pghost as needed
        if (
            $sslmode === SslMode::DISABLED ||
            $sslmode === SslMode::ALLOW ||
            $sslmode === SslMode::PREFER ||
            $sslmode === SslMode::REQUIRE
        ) {
            $pghost .= ':' . $sslmode->value;
        } elseif ($sslmode === SslMode::LEGACY) {
            $pghost .= ' requiressl=1';
        }

        return $conn->connect($pghost, $user, $password);
    }

    /**
     * Gets the name of the correct database driver to use.  As a side effect,
     * sets the platform.
     * @param string $description A description of the database and version
     * @return string|int|null The class name of the driver eg. Postgres84
     * null if version is < 7.4
     * -3 Database-specific failure
     */
    public function getDriver(string &$description): string|int|null
    {
        $version = '';
        $v = pg_version($this->conn->_connectionID); // @phpstan-ignore-line
        if (isset($v['server'])) {
            $version = $v['server'];
        }

        // If we didn't manage to get the version without a query, query...
        if ($version !== '') {
            $adodb = new ADOdbBase($this->conn);

            $sql = "SELECT VERSION() AS version";
            $field = $adodb->selectField($sql, 'version');
            if (is_int($field)) {
                return $field;
            }

            // Check the platform, if it's mingw, set it
            if (preg_match('/ mingw /i', $field)) {
                $this->platform = 'MINGW';
            }

            $params = explode(' ', $field);
            if (!isset($params[1])) {
                return -3;
            }

            $version = (string)$params[1]; // eg. 8.4.4
        }

        $description = "PostgreSQL {$version}";

        // Detect version and choose appropriate database driver
        switch (substr($version, 0, 2)) {
            case '14':
                return 'Postgres';
            case '13':
                return 'Postgres13';
            case '12':
                return 'Postgres12';
            case '11':
                return 'Postgres11';
            case '10':
                return 'Postgres10';
        }

        switch (substr($version, 0, 3)) {
            case '9.6':
                return 'Postgres96';
            case '9.5':
                return 'Postgres95';
            case '9.4':
                return 'Postgres94';
            case '9.3':
                return 'Postgres93';
            case '9.2':
                return 'Postgres92';
            case '9.1':
                return 'Postgres91';
            case '9.0':
                return 'Postgres90';
            case '8.4':
                return 'Postgres84';
            case '8.3':
                return 'Postgres83';
            case '8.2':
                return 'Postgres82';
            case '8.1':
                return 'Postgres81';
            case '8.0':
            case '7.5':
                return 'Postgres80';
            case '7.4':
                return 'Postgres74';
        }

        /* All <7.4 versions are not supported */
        // if major version is 7 or less and wasn't caught in the
        // switch/case block, we have an unsupported version.
        $floatVer = floatval(explode(' ', $version)[0]);
        if ($floatVer < 7.4) {
            return null;
        }

        // If unknown version, then default to latest driver
        return 'Postgres';
    }

    /**
     * Get the last error in the connection
     * @return string Error string
     */
    public function getLastError(): string
    {
        return pg_last_error($this->conn->_connectionID); // @phpstan-ignore-line
    }
}
