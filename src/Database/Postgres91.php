<?php

declare(strict_types=1);

namespace PhpPgAdmin\Database;

use PhpPgAdmin\Config;

class Postgres91 extends Postgres92
{
    public float $majorVersion = 9.1;

    // Administration functions
    /**
     * Returns all available process information.
     * @param ?string $database (optional) Find only connections to specified database
     * @return mixed A recordset
     */
    public function getProcesses(?string $database = null)
    {
        if ($database === null) {
            $sql = "SELECT datname, usename, procpid AS pid, waiting, current_query AS query, query_start
				FROM pg_catalog.pg_stat_activity
				ORDER BY datname, usename, procpid";
        } else {
            $this->clean($database);
            $sql = "SELECT datname, usename, procpid AS pid, waiting, current_query AS query, query_start
				FROM pg_catalog.pg_stat_activity
				WHERE datname='{$database}'
				ORDER BY usename, procpid";
        }

        $rc = $this->selectSet($sql);

        return $rc;
    }

    // Tablespace functions

    /**
     * Retrieves information for all tablespaces
     * @param $all Include all tablespaces (necessary when moving objects back to the default space)
     * @return mixed A recordset
     */
    public function getTablespaces(bool $all = false)
    {
        $sql = "SELECT spcname, pg_catalog.pg_get_userbyid(spcowner) AS spcowner, spclocation,
            (SELECT description FROM pg_catalog.pg_shdescription pd WHERE pg_tablespace.oid=pd.objoid AND pd.classoid='pg_tablespace'::regclass) AS spccomment
            FROM pg_catalog.pg_tablespace";

        if (!Config::showSystem() && !$all) {
            $sql .= ' WHERE spcname NOT LIKE $$pg\_%$$';
        }

        $sql .= " ORDER BY spcname";

        return $this->selectSet($sql);
    }

    /**
     * Retrieves a tablespace's information
     * @return mixed A recordset
     */
    public function getTablespace(string $spcname)
    {
        $this->clean($spcname);

        $sql = "SELECT spcname, pg_catalog.pg_get_userbyid(spcowner) AS spcowner, spclocation,
            (SELECT description FROM pg_catalog.pg_shdescription pd WHERE pg_tablespace.oid=pd.objoid AND pd.classoid='pg_tablespace'::regclass) AS spccomment
            FROM pg_catalog.pg_tablespace WHERE spcname='{$spcname}'";

        return $this->selectSet($sql);
    }

    public function hasUserSignals(): bool
    {
        return false;
    }
}
