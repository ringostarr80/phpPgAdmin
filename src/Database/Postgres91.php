<?php

declare(strict_types=1);

namespace PhpPgAdmin\Database;

class Postgres91 extends Postgres92
{
    public float $major_version = 9.1;

    /**
     * Constructor
     * @param $conn The database connection
     */
    public function __construct($conn)
    {
        parent::__construct($conn);
    }

    // Help functions

    public function getHelpPages()
    {
        include_once('./help/PostgresDoc91.php');
        return $this->help_page;
    }

    // Administration functions
    /**
     * Returns all available process information.
     * @param $database (optional) Find only connections to specified database
     * @return A recordset
     */
    public function getProcesses($database = null)
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
     * @return A recordset
     */
    public function getTablespaces($all = false)
    {
        global $conf;

        $sql = "SELECT spcname, pg_catalog.pg_get_userbyid(spcowner) AS spcowner, spclocation,
                    (SELECT description FROM pg_catalog.pg_shdescription pd WHERE pg_tablespace.oid=pd.objoid AND pd.classoid='pg_tablespace'::regclass) AS spccomment
					FROM pg_catalog.pg_tablespace";

        if (!$conf['show_system'] && !$all) {
            $sql .= ' WHERE spcname NOT LIKE $$pg\_%$$';
        }

        $sql .= " ORDER BY spcname";

        return $this->selectSet($sql);
    }

    /**
     * Retrieves a tablespace's information
     * @return A recordset
     */
    public function getTablespace($spcname)
    {
        $this->clean($spcname);

        $sql = "SELECT spcname, pg_catalog.pg_get_userbyid(spcowner) AS spcowner, spclocation,
                    (SELECT description FROM pg_catalog.pg_shdescription pd WHERE pg_tablespace.oid=pd.objoid AND pd.classoid='pg_tablespace'::regclass) AS spccomment
					FROM pg_catalog.pg_tablespace WHERE spcname='{$spcname}'";

        return $this->selectSet($sql);
    }


    // Capabilities
    public function hasUserSignals()
    {
        return false;
    }
}
