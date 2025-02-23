<?php

declare(strict_types=1);

namespace PhpPgAdmin\Database;

use PhpPgAdmin\Config;
use PhpPgAdmin\DDD\Entities\ServerSession;

class Postgres81 extends Postgres82
{
    public float $majorVersion = 8.1;

    // List of all legal privileges that can be applied to different types
    // of objects.
    public array $privlist = [
        'table'      => ['SELECT', 'INSERT', 'UPDATE', 'DELETE', 'RULE', 'REFERENCES', 'TRIGGER', 'ALL PRIVILEGES'],
        'view'       => ['SELECT', 'INSERT', 'UPDATE', 'DELETE', 'RULE', 'REFERENCES', 'TRIGGER', 'ALL PRIVILEGES'],
        'sequence'   => ['SELECT', 'UPDATE', 'ALL PRIVILEGES'],
        'database'   => ['CREATE', 'TEMPORARY', 'ALL PRIVILEGES'],
        'function'   => ['EXECUTE', 'ALL PRIVILEGES'],
        'language'   => ['USAGE', 'ALL PRIVILEGES'],
        'schema'     => ['CREATE', 'USAGE', 'ALL PRIVILEGES'],
        'tablespace' => ['CREATE', 'ALL PRIVILEGES']
    ];
    // List of characters in acl lists and the privileges they
    // refer to.
    public array $privmap = [
        'r' => 'SELECT',
        'w' => 'UPDATE',
        'a' => 'INSERT',
        'd' => 'DELETE',
        'R' => 'RULE',
        'x' => 'REFERENCES',
        't' => 'TRIGGER',
        'X' => 'EXECUTE',
        'U' => 'USAGE',
        'C' => 'CREATE',
        'T' => 'TEMPORARY'
    ];
    /**
     * Array of allowed index types
     *
     * @var string[]
     */
    public array $typIndexes = ['BTREE', 'RTREE', 'GIST', 'HASH'];

    public function __construct(\ADOConnection $conn)
    {
        parent::__construct($conn);

        $this->helpPage['pg.role'] = 'user-manag.html';
        $this->helpPage['pg.role.create'] = ['sql-createrole.html','user-manag.html#DATABASE-ROLES'];
        $this->helpPage['pg.role.alter'] = ['sql-alterrole.html','role-attributes.html'];
        $this->helpPage['pg.role.drop'] = ['sql-droprole.html','user-manag.html#DATABASE-ROLES'];
    }

    // Database functions

    /**
     * Returns all databases available on the server
     * @return \ADORecordSet|int A list of databases, sorted alphabetically
     */
    public function getDatabases(?string $currentdatabase = null): \ADORecordSet|int
    {
        $serverSession = ServerSession::fromRequestParameter();

        if (Config::ownedOnly() && !$this->isSuperUser() && !is_null($serverSession)) {
            $username = (string)$serverSession->Username;
            $username = $this->clean($username);
            $clause = " AND pr.rolname='{$username}'";
        } else {
            $clause = '';
        }

        if ($currentdatabase != null) {
            $currentdatabase = $this->clean($currentdatabase);
            $orderby = "ORDER BY pdb.datname = '{$currentdatabase}' DESC, pdb.datname";
        } else {
            $orderby = "ORDER BY pdb.datname";
        }

        if (!Config::showSystem()) {
            $where = ' AND NOT pdb.datistemplate';
        } else {
            $where = ' AND pdb.datallowconn';
        }

        $sql = "SELECT pdb.datname AS datname, pr.rolname AS datowner, pg_encoding_to_char(encoding) AS datencoding,
                               (SELECT description FROM pg_catalog.pg_description pd WHERE pdb.oid=pd.objoid) AS datcomment,
                               (SELECT spcname FROM pg_catalog.pg_tablespace pt WHERE pt.oid=pdb.dattablespace) AS tablespace,
							   pg_catalog.pg_database_size(pdb.oid) as dbsize 
                        FROM pg_catalog.pg_database pdb LEFT JOIN pg_catalog.pg_roles pr ON (pdb.datdba = pr.oid)  
						WHERE true 
			{$where}
			{$clause}
			{$orderby}";

        return $this->selectSet($sql);
    }

    /**
     * Alters a database
     * the multiple return vals are for postgres 8+ which support more functionality in alter database
     * @param $dbName The name of the database
     * @param $newName new name for the database
     * @param $newOwner The new owner for the database
     * @return bool|int 0 success
     * -1 transaction error
     * -2 owner error
     * -3 rename error
     */
    public function alterDatabase(string $dbName, string $newName, string $newOwner = '', string $comment = ''): bool|int
    {
        $dbName = $this->clean($dbName) ?? $dbName;
        $newName = $this->clean($newName) ?? $newName;
        $newOwner = $this->clean($newOwner) ?? $newOwner;
        //ignore $comment, not supported pre 8.2

        $status = $this->beginTransaction();
        if ($status != 0) {
            $this->rollbackTransaction();
            return -1;
        }

        if ($dbName != $newName) {
            $status = $this->alterDatabaseRename($dbName, $newName);
            if ($status != 0) {
                $this->rollbackTransaction();
                return -3;
            }
        }

        $status = $this->alterDatabaseOwner($newName, $newOwner);
        if ($status != 0) {
            $this->rollbackTransaction();
            return -2;
        }
        return $this->endTransaction();
    }

    // Autovacuum functions

    public function saveAutovacuum(
        string $table,
        ?string $vacenabled,
        ?string $vacthreshold,
        ?string $vacscalefactor,
        ?string $anathresold,
        ?string $anascalefactor,
        ?string $vaccostdelay,
        ?string $vaccostlimit
    ): int {
        $defaults = $this->getAutovacuum();
        $c_schema = $this->_schema;
        $c_schema = $this->clean($c_schema);
        $table = $this->clean($table);

        $rs = $this->selectSet("
			SELECT c.oid
			FROM pg_catalog.pg_class AS c
				LEFT JOIN pg_catalog.pg_namespace AS n ON (n.oid=c.relnamespace)
			WHERE
				c.relname = '{$table}' AND n.nspname = '{$c_schema}'
		");
        if (is_int($rs) || $rs->EOF) {
            return -1;
        }

        $toid = $rs->fields('oid');
        if (!is_string($toid) || !is_numeric($toid)) {
            return -1;
        }
        unset($rs);

        if (empty($_POST['autovacuum_vacuum_threshold'])) {
            $_POST['autovacuum_vacuum_threshold'] = $defaults['autovacuum_vacuum_threshold'];
        }

        if (empty($_POST['autovacuum_vacuum_scale_factor'])) {
            $_POST['autovacuum_vacuum_scale_factor'] = $defaults['autovacuum_vacuum_scale_factor'];
        }

        if (empty($_POST['autovacuum_analyze_threshold'])) {
            $_POST['autovacuum_analyze_threshold'] = $defaults['autovacuum_analyze_threshold'];
        }

        if (empty($_POST['autovacuum_analyze_scale_factor'])) {
            $_POST['autovacuum_analyze_scale_factor'] = $defaults['autovacuum_analyze_scale_factor'];
        }

        if (empty($_POST['autovacuum_vacuum_cost_delay'])) {
            $_POST['autovacuum_vacuum_cost_delay'] = $defaults['autovacuum_vacuum_cost_delay'];
        }

        if (empty($_POST['autovacuum_vacuum_cost_limit'])) {
            $_POST['autovacuum_vacuum_cost_limit'] = $defaults['autovacuum_vacuum_cost_limit'];
        }

        $rs = $this->selectSet("SELECT vacrelid
			FROM \"pg_catalog\".\"pg_autovacuum\"
			WHERE vacrelid = {$toid};");
        if (is_int($rs)) {
            return -1;
        }

        $status = -1; // ini
        if ($rs->recordCount() && is_array($rs->fields) && $rs->fields['vacrelid'] == $toid) {
            // table exists in pg_autovacuum, UPDATE
            $sql = sprintf(
                "UPDATE \"pg_catalog\".\"pg_autovacuum\" SET
						enabled = '%s',
						vac_base_thresh = %s,
						vac_scale_factor = %s,
						anl_base_thresh = %s,
						anl_scale_factor = %s,
						vac_cost_delay = %s,
						vac_cost_limit = %s
					WHERE vacrelid = {$toid};
				",
                $_POST['autovacuum_enabled'] == 'on' ? 't' : 'f',
                is_string($_POST['autovacuum_vacuum_threshold']) ? $_POST['autovacuum_vacuum_threshold'] : '',
                is_string($_POST['autovacuum_vacuum_scale_factor']) ? $_POST['autovacuum_vacuum_scale_factor'] : '',
                is_string($_POST['autovacuum_analyze_threshold']) ? $_POST['autovacuum_analyze_threshold'] : '',
                is_string($_POST['autovacuum_analyze_scale_factor']) ? $_POST['autovacuum_analyze_scale_factor'] : '',
                is_string($_POST['autovacuum_vacuum_cost_delay']) ? $_POST['autovacuum_vacuum_cost_delay'] : '',
                is_string($_POST['autovacuum_vacuum_cost_limit']) ? $_POST['autovacuum_vacuum_cost_limit'] : ''
            );
            $status = $this->execute($sql);
        } else {
            // table doesn't exists in pg_autovacuum, INSERT
            $sql = sprintf(
                "INSERT INTO \"pg_catalog\".\"pg_autovacuum\"
				VALUES (%s, '%s', %s, %s, %s, %s, %s, %s)",
                $toid,
                $_POST['autovacuum_enabled'] == 'on' ? 't' : 'f',
                is_string($_POST['autovacuum_vacuum_threshold']) ? $_POST['autovacuum_vacuum_threshold'] : '',
                is_string($_POST['autovacuum_vacuum_scale_factor']) ? $_POST['autovacuum_vacuum_scale_factor'] : '',
                is_string($_POST['autovacuum_analyze_threshold']) ? $_POST['autovacuum_analyze_threshold'] : '',
                is_string($_POST['autovacuum_analyze_scale_factor']) ? $_POST['autovacuum_analyze_scale_factor'] : '',
                is_string($_POST['autovacuum_vacuum_cost_delay']) ? $_POST['autovacuum_vacuum_cost_delay'] : '',
                is_string($_POST['autovacuum_vacuum_cost_limit']) ? $_POST['autovacuum_vacuum_cost_limit'] : ''
            );
            $status = $this->execute($sql);
        }

        return $status;
    }

    /**
     * Returns all available process information.
     * @param ?string $database (optional) Find only connections to specified database
     * @return \ADORecordSet|int A recordset
     */
    public function getProcesses(?string $database = null): \ADORecordSet|int
    {
        if ($database === null) {
            $sql = "SELECT datname, usename, procpid AS pid, current_query AS query, query_start, 
                  case when (select count(*) from pg_locks where pid=pg_stat_activity.procpid and granted is false) > 0 then 't' else 'f' end as waiting  
				FROM pg_catalog.pg_stat_activity
				ORDER BY datname, usename, procpid";
        } else {
            $database = $this->clean($database);
            $sql = "SELECT datname, usename, procpid AS pid, current_query AS query, query_start
                    case when (select count(*) from pg_locks where pid=pg_stat_activity.procpid and granted is false) > 0 then 't' else 'f' end as waiting 
				FROM pg_catalog.pg_stat_activity
				WHERE datname='{$database}'
				ORDER BY usename, procpid";
        }

        return $this->selectSet($sql);
    }

    // Tablespace functions

    /**
     * Retrieves a tablespace's information
     * @return \ADORecordSet|int A recordset
     */
    public function getTablespace(string $spcname): \ADORecordSet|int
    {
        $spcname = $this->clean($spcname);

        $sql = "SELECT spcname, pg_catalog.pg_get_userbyid(spcowner) AS spcowner, spclocation
            FROM pg_catalog.pg_tablespace WHERE spcname='{$spcname}'";

        return $this->selectSet($sql);
    }

    /**
     * Retrieves information for all tablespaces
     * @param $all Include all tablespaces (necessary when moving objects back to the default space)
     * @return \ADORecordSet|int A recordset
     */
    public function getTablespaces(bool $all = false): \ADORecordSet|int
    {
        $sql = "SELECT spcname, pg_catalog.pg_get_userbyid(spcowner) AS spcowner, spclocation
					FROM pg_catalog.pg_tablespace";

        if (!Config::showSystem() && !$all) {
            $sql .= ' WHERE spcname NOT LIKE $$pg\_%$$';
        }

        $sql .= " ORDER BY spcname";

        return $this->selectSet($sql);
    }

    // Capabilities
    public function hasCreateTableLikeWithConstraints(): bool
    {
        return false;
    }

    public function hasSharedComments(): bool
    {
        return false;
    }

    public function hasConcurrentIndexBuild(): bool
    {
        return false;
    }
}
