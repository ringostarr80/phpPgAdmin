<?php

declare(strict_types=1);

namespace PhpPgAdmin\Database;

use PhpPgAdmin\Config;
use PhpPgAdmin\DDD\Entities\ServerSession;
use PhpPgAdmin\DDD\ValueObjects\Server\SslMode;

final class PhpPgAdminConnection extends \PDO
{
    /**
     * Map of database encoding names to HTTP encoding names.  If a
     * database encoding does not appear in this list, then its HTTP
     * encoding name is the same as its database encoding name.
     *
     * @var array<string, string>
     */
    public const CODEMAP = [
        'BIG5' => 'BIG5',
        'EUC_CN' => 'GB2312',
        'EUC_JP' => 'EUC-JP',
        'EUC_KR' => 'EUC-KR',
        'EUC_TW' => 'EUC-TW',
        'GB18030' => 'GB18030',
        'GBK' => 'GB2312',
        'ISO_8859_5' => 'ISO-8859-5',
        'ISO_8859_6' => 'ISO-8859-6',
        'ISO_8859_7' => 'ISO-8859-7',
        'ISO_8859_8' => 'ISO-8859-8',
        'JOHAB' => 'CP1361',
        'KOI8' => 'KOI8-R',
        'LATIN1' => 'ISO-8859-1',
        'LATIN2' => 'ISO-8859-2',
        'LATIN3' => 'ISO-8859-3',
        'LATIN4' => 'ISO-8859-4',
        'LATIN5' => 'ISO-8859-9',
        'LATIN6' => 'ISO-8859-10',
        'LATIN7' => 'ISO-8859-13',
        'LATIN8' => 'ISO-8859-14',
        'LATIN9' => 'ISO-8859-15',
        'LATIN10' => 'ISO-8859-16',
        'SJIS' => 'SHIFT_JIS',
        'SQL_ASCII' => 'US-ASCII',
        'UHC' => 'WIN949',
        'UTF8' => 'UTF-8',
        'WIN866' => 'CP866',
        'WIN874' => 'CP874',
        'WIN1250' => 'CP1250',
        'WIN1251' => 'CP1251',
        'WIN1252' => 'CP1252',
        'WIN1256' => 'CP1256',
        'WIN1258' => 'CP1258',
    ];
    public const MAX_NAME_LENGTH = 63;

    public static function create(
        string $host,
        int $port,
        string $sslmode,
        string $user,
        string $password,
        string $database,
    ): self {
        $dsn = "pgsql:host={$host};port={$port};sslmode={$sslmode};dbname={$database}";
        $pdo = new self(dsn: $dsn, username: $user, password: $password);
        $pdo->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);

        return $pdo;
    }

    public function alterDatabase(
        string $dbName,
        string $newName,
        ?string $newOwner = null,
        ?string $comment = null,
    ): void {
        if (!$this->beginTransaction()) {
            throw new \PDOException('Failed to begin transaction.');
        }

        if ($dbName !== $newName) {
            try {
                $this->alterDatabaseRename($dbName, $newName);
            } catch (\PDOException $e) {
                if (!$this->rollBack()) {
                    throw new \PDOException('Failed to roll back transaction after renaming database.', 0, $e);
                }

                return;
            }

            $dbName = $newName;
        }

        if (!is_null($newOwner) && $newOwner !== '') {
            try {
                $this->alterDatabaseOwner($dbName, $newOwner);
            } catch (\PDOException $e) {
                if (!$this->rollBack()) {
                    throw new \PDOException('Failed to roll back transaction after altering database owner.', 0, $e);
                }

                return;
            }
        }

        try {
            $this->setDatabaseComment($dbName, $comment);
        } catch (\PDOException $e) {
            if (!$this->rollBack()) {
                throw new \PDOException('Failed to roll back transaction after setting database comment.', 0, $e);
            }

            return;
        }

        if (!$this->commit()) {
            throw new \PDOException('Failed to commit transaction.');
        }
    }

    public function alterDatabaseOwner(string $dbName, string $newOwner): void
    {
        $escapedDbName = self::escapeIdentifier($dbName);
        $escapedNewOwner = self::escapeIdentifier($newOwner);
        $statement = "ALTER DATABASE \"{$escapedDbName}\" OWNER TO \"{$escapedNewOwner}\"";

        if ($this->exec($statement) === false) {
            throw new \PDOException('Failed to execute SQL statement for altering database owner.');
        }
    }

    public function alterDatabaseRename(string $oldName, string $newName): void
    {
        $escapedOldName = self::escapeIdentifier($oldName);
        $escapedNewName = self::escapeIdentifier($newName);
        $statement = "ALTER DATABASE \"{$escapedOldName}\" RENAME TO \"{$escapedNewName}\"";

        if ($this->exec($statement) === false) {
            throw new \PDOException('Failed to execute SQL statement for renaming database.');
        }
    }

    public function createDatabase(
        string $database,
        string $encoding,
        string $tablespace = '',
        string $comment = '',
        string $template = 'template1',
        string $lcCollate = '',
        string $lcCType = '',
    ): void {
        $escapedDatabase = self::escapeIdentifier($database);
        $escapedTemplate = self::escapeIdentifier($template);
        $sql = "CREATE DATABASE \"{$escapedDatabase}\" WITH TEMPLATE = \"{$escapedTemplate}\"";
        $sqlParams = [];

        if ($encoding !== '') {
            if (!in_array(needle: $encoding, haystack: array_keys(self::CODEMAP), strict: true)) {
                throw new \InvalidArgumentException("Invalid encoding: {$encoding}");
            }

            $sql .= " ENCODING = '{$encoding}'";
        }

        $availableCollations = $this->getAvailableCollations();

        if ($lcCollate !== '') {
            if (!in_array(needle: $lcCollate, haystack: $availableCollations, strict: true)) {
                throw new \InvalidArgumentException("Invalid LC_COLLATE: {$lcCollate}");
            }

            $sql .= " LC_COLLATE = '{$lcCollate}'";
        }

        if ($lcCType !== '') {
            if (!in_array(needle: $lcCType, haystack: $availableCollations, strict: true)) {
                throw new \InvalidArgumentException("Invalid LC_CTYPE: {$lcCType}");
            }

            $sql .= " LC_CTYPE = '{$lcCType}'";
        }

        if ($tablespace !== '') {
            $escapedTablespace = self::escapeIdentifier($tablespace);
            $sql .= " TABLESPACE \"{$escapedTablespace}\"";
        }

        $statement = $this->prepare($sql);

        if ($statement === false) {
            throw new \PDOException('Failed to prepare SQL statement for creating database.');
        }

        if (!$statement->execute($sqlParams)) {
            throw new \PDOException('Failed to execute SQL statement for creating database.');
        }

        if ($comment !== '') {
            $this->setDatabaseComment($database, $comment);
        }
    }

    public function dropDatabase(string $database): void
    {
        $escapedDatabase = self::escapeIdentifier($database);
        $statement = "DROP DATABASE \"{$escapedDatabase}\"";

        if ($this->exec($statement) === false) {
            throw new \PDOException('Failed to execute SQL statement for dropping database.');
        }
    }

    /**
     * @return string[]
     */
    public function getAvailableCollations(): array
    {
        $query = "SELECT collname FROM pg_collation WHERE collname LIKE '%.%' ORDER BY collname";
        $statement = $this->prepare($query);

        if ($statement === false) {
            throw new \PDOException('Failed to prepare SQL statement for getting available collations.');
        }

        if (!$statement->execute()) {
            throw new \PDOException('Failed to execute SQL statement for getting available collations.');
        }

        $collations = [];

        while ($row = $statement->fetch()) {
            if (is_array($row) && isset($row['collname']) && is_string($row['collname'])) {
                $collations[] = $row['collname'];
            }
        }

        return $collations;
    }

    public function getDatabaseComment(string $database): string
    {
        $sql = "SELECT description
            FROM pg_catalog.pg_database
                JOIN pg_catalog.pg_shdescription ON (oid=objoid AND classoid='pg_database'::regclass)
            WHERE pg_database.datname = :database";
        $sqlParams = ['database' => $database];
        $statement = $this->prepare($sql);

        if ($statement === false) {
            throw new \PDOException('Failed to prepare SQL statement for getting database comment.');
        }

        if (!$statement->execute($sqlParams)) {
            throw new \PDOException('Failed to execute SQL statement for getting database comment.');
        }

        $result = $statement->fetch();

        if ($result === false) { // no comment found
            return '';
        }

        if (!is_array($result)) {
            throw new \PDOException('Failed to fetch database comment.');
        }

        if (isset($result['description']) && is_string($result['description'])) {
            return $result['description'];
        }

        return '';
    }

    public function getDatabaseOwner(string $database): string
    {
        $sql = "SELECT usename
            FROM pg_user, pg_database
            WHERE pg_user.usesysid = pg_database.datdba AND pg_database.datname = :database";
        $sqlParams = [
            'database' => $database,
        ];
        $statement = $this->prepare($sql);

        if ($statement === false) {
            throw new \PDOException('Failed to prepare SQL statement for getting database owner.');
        }

        if (!$statement->execute($sqlParams)) {
            throw new \PDOException('Failed to execute SQL statement for getting database owner.');
        }

        $result = $statement->fetch();

        if (!is_array($result)) {
            throw new \PDOException('Failed to fetch database owner.');
        }

        if (isset($result['usename']) && is_string($result['usename'])) {
            return $result['usename'];
        }

        return '';
    }

    /**
     * @return array<array{
     *  'datname': string,
     *  'datowner': string,
     *  'datencoding': string,
     *  'datcollate': string,
     *  'datctype': string,
     *  'tablespace': string,
     *  'dbsize': int,
     *  'datcomment': string
     * }> The list of databases.
     */
    public function getDatabases(): array
    {
        $serverInfo = ServerSession::fromRequestParameter();

        $whereClause = Config::showSystem()
            ? 'pdb.datallowconn'
            : 'NOT pdb.datistemplate';

        if (!is_null($serverInfo) && Config::ownedOnly() && !$this->isSuperUser()) {
            $whereClause = " AND pg_has_role('{$serverInfo->Username}'::name, pr.rolname, 'USAGE')";
        }

        $orderBy = "pdb.datname";

        $sql = "
            SELECT pdb.datname AS datname, pr.rolname AS datowner, pg_encoding_to_char(encoding) AS datencoding,
                pdb.datcollate, pdb.datctype,
                (SELECT description
                    FROM pg_catalog.pg_shdescription pd
                    WHERE pdb.oid=pd.objoid AND pd.classoid='pg_database'::regclass
                ) AS datcomment,
                (SELECT spcname
                    FROM pg_catalog.pg_tablespace pt
                    WHERE pt.oid=pdb.dattablespace
                ) AS tablespace,
                CASE WHEN pg_catalog.has_database_privilege(current_user, pdb.oid, 'CONNECT')
                    THEN pg_catalog.pg_database_size(pdb.oid)
                    ELSE -1 -- set this magic value, which we will convert to no access later
                END AS dbsize
            FROM pg_catalog.pg_database pdb
                LEFT JOIN pg_catalog.pg_roles pr ON (pdb.datdba = pr.oid)
            WHERE {$whereClause}
            ORDER BY {$orderBy}";

        $statement = $this->prepare($sql);

        if ($statement === false) {
            throw new \PDOException('Failed to prepare SQL statement for getting databases.');
        }

        if (!$statement->execute()) {
            throw new \PDOException('Failed to execute SQL statement for getting databases.');
        }

        $result = [];
        $requiredFields = [
            'datname',
            'datowner',
            'datencoding',
            'datcollate',
            'datctype',
            'tablespace',
            'dbsize',
        ];

        while ($row = $statement->fetch()) {
            if (!is_array($row)) {
                continue;
            }

            foreach ($requiredFields as $field) {
                if (!isset($row[$field])) {
                    continue 2;
                }
            }

            if (
                !is_string($row['datname']) ||
                !is_string($row['datowner']) ||
                !is_string($row['datencoding']) ||
                !is_string($row['datcollate']) ||
                !is_string($row['datctype']) ||
                !is_string($row['tablespace']) ||
                !is_int($row['dbsize'])
            ) {
                continue;
            }

            $result[] = [
                'datcollate' => $row['datcollate'],
                'datcomment' => isset($row['datcomment']) && is_string($row['datcomment'])
                    ? $row['datcomment']
                    : '',
                'datctype' => $row['datctype'],
                'datencoding' => $row['datencoding'],
                'datname' => $row['datname'],
                'datowner' => $row['datowner'],
                'dbsize' => $row['dbsize'],
                'tablespace' => $row['tablespace'],
            ];
        }

        return $result;
    }

    /**
     * @return array<array{
     *  'usename': string,
     *  'usesuper': bool,
     *  'usecreatedb': bool,
     *  'useexpires': string,
     *  'useconfig': string
     * }> The list of databases.
     */
    public function getUsers(): array
    {
        $sql = "SELECT usename, usesuper, usecreatedb, valuntil AS useexpires, useconfig
            FROM pg_user
            ORDER BY usename";
        $statement = $this->prepare($sql);

        if ($statement === false) {
            throw new \PDOException('Failed to prepare SQL statement for getting users.');
        }

        if (!$statement->execute()) {
            throw new \PDOException('Failed to execute SQL statement for getting users.');
        }

        $result = [];
        $requiredFields = [
            'usename',
            'usesuper',
            'usecreatedb',
        ];

        while ($row = $statement->fetch()) {
            if (!is_array($row)) {
                continue;
            }

            foreach ($requiredFields as $field) {
                if (!isset($row[$field])) {
                    continue 2;
                }
            }

            if (!is_string($row['usename']) || !is_bool($row['usesuper']) || !is_bool($row['usecreatedb'])) {
                continue;
            }

            $result[] = [
                'useconfig' => isset($row['useconfig']) && is_string($row['useconfig'])
                    ? $row['useconfig']
                    : '',
                'usecreatedb' => $row['usecreatedb'],
                'useexpires' => isset($row['useexpires']) && is_string($row['useexpires'])
                    ? $row['useexpires']
                    : '',
                'usename' => $row['usename'],
                'usesuper' => $row['usesuper'],
            ];
        }

        return $result;
    }

    /**
     * Determines whether or not a user is a super user
     *
     * @param string $username The username of the user
     * @return bool true if is a super user, false otherwise.
     */
    public function isSuperUser(string $username = ''): bool
    {
        if (empty($username)) {
            $statement = $this->query("SHOW is_superuser");

            if ($statement !== false) {
                $isSuperUserColumn = $statement->fetchColumn();

                return $isSuperUserColumn === 'on';
            }
        }

        $sql = "SELECT usesuper FROM pg_user WHERE usename = :username";
        $statement = $this->prepare($sql);

        if ($statement === false) {
            return false;
        }

        if (!$statement->execute(['username' => $username])) {
            return false;
        }

        $rows = $statement->fetchAll();

        if (empty($rows)) {
            return false;
        }

        foreach ($rows as $row) {
            if (is_array($row) && isset($row['usesuper']) && is_bool($row['usesuper'])) {
                return $row['usesuper'];
            }
        }

        return false;
    }

    public function setDatabaseComment(string $database, ?string $comment = null): void
    {
        $escapedDatabase = self::escapeIdentifier($database);
        $statement = "COMMENT ON DATABASE \"{$escapedDatabase}\" IS ";
        $statement .= !is_null($comment)
            ? $this->quote($comment)
            : 'NULL';

        if ($this->exec($statement) === false) {
            throw new \PDOException('Failed to execute SQL statement for setting database comment.');
        }
    }

    public static function loginDataIsValid(
        string $host,
        int $port,
        SslMode $sslmode,
        string $user,
        string $password,
    ): bool {
        $dsn = "pgsql:host={$host};port={$port};sslmode={$sslmode->value}";

        try {
            $_ = new \PDO(dsn: $dsn, username: $user, password: $password);

            return true;
        } catch (\PDOException $e) {
            error_log($e->getMessage());

            return false;
        }
    }

    /**
     * From PHP 8.4 on, there is a native escapeIdentifier function.
     *
     * @see https://www.php.net/manual/en/pdo-pgsql.escapeidentifier.php
     */
    private static function escapeIdentifier(string $identifier): string
    {
        return str_replace('"', '""', $identifier);
    }
}
