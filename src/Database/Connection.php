<?php

declare(strict_types=1);

namespace PhpPgAdmin\Database;

include_once dirname(__DIR__, 2) . '/vendor/adodb/adodb-php/adodb.inc.php';

class Connection
{
    public \ADOConnection $conn;

    public string $platform = 'UNKNOWN';

    /**
     * Creates a new connection.  Will actually make a database connection.
     * @param $fetchMode Defaults to associative.  Override for different behaviour
     */
    public function __construct($host, $port, $sslmode, $user, $password, $database, $fetchMode = ADODB_FETCH_ASSOC)
    {
        $this->conn = ADONewConnection('postgres') ?: throw new \Exception('Connection failed');
        $this->conn->setFetchMode($fetchMode);

        // Ignore host if null
        if ($host === null || $host == '') {
            if ($port !== null && $port != '') {
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

    /**
     * Gets the name of the correct database driver to use.  As a side effect,
     * sets the platform.
     * @param (return-by-ref) $description A description of the database and version
     * @return The class name of the driver eg. Postgres84
     * @return null if version is < 7.4
     * @return -3 Database-specific failure
     */
    public function getDriver(&$description)
    {
        $v = pg_version($this->conn->_connectionID);
        if (isset($v['server'])) {
            $version = $v['server'];
        }

        // If we didn't manage to get the version without a query, query...
        if (!isset($version)) {
            $adodb = new ADOdbBase($this->conn);

            $sql = "SELECT VERSION() AS version";
            $field = $adodb->selectField($sql, 'version');

            // Check the platform, if it's mingw, set it
            if (preg_match('/ mingw /i', $field)) {
                $this->platform = 'MINGW';
            }

            $params = explode(' ', $field);
            if (!isset($params[1])) {
                return -3;
            }

            $version = $params[1]; // eg. 8.4.4
        }

        $description = "PostgreSQL {$version}";

        // Detect version and choose appropriate database driver
        switch (substr($version, 0, 2)) {
            case '14':
                return 'Postgres';
            break;
            case '13':
                return 'Postgres13';
            break;
            case '12':
                return 'Postgres12';
            break;
            case '11':
                return 'Postgres11';
            break;
            case '10':
                return 'Postgres10';
            break;
        }

        switch (substr($version, 0, 3)) {
            case '9.6':
                return 'Postgres96';
            break;
            case '9.5':
                return 'Postgres95';
            break;
            case '9.4':
                return 'Postgres94';
            break;
            case '9.3':
                return 'Postgres93';
            break;
            case '9.2':
                return 'Postgres92';
            break;
            case '9.1':
                return 'Postgres91';
            break;
            case '9.0':
                return 'Postgres90';
            break;
            case '8.4':
                return 'Postgres84';
            break;
            case '8.3':
                return 'Postgres83';
            break;
            case '8.2':
                return 'Postgres82';
            break;
            case '8.1':
                return 'Postgres81';
            break;
            case '8.0':
            case '7.5':
                return 'Postgres80';
            break;
            case '7.4':
                return 'Postgres74';
            break;
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
     * @return Error string
     */
    public function getLastError()
    {
        return pg_last_error($this->conn->_connectionID);
    }
}
