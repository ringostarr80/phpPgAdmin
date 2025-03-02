<?php

declare(strict_types=1);

namespace PhpPgAdmin\Database;

class Postgres95 extends Postgres96
{
    public float $majorVersion = 9.5;

    /**
     * Returns all available process information.
     * @param ?string $database (optional) Find only connections to specified database
     * @return \ADORecordSet|int A recordset
     */
    public function getProcesses(?string $database = null): \ADORecordSet|int
    {
        if ($database === null) {
            $sql = "SELECT datname, usename, pid, waiting, state_change as query_start,
                case when state='idle in transaction' then '<IDLE> in transaction'
                    when state = 'idle' then '<IDLE>' else query end as query
				FROM pg_catalog.pg_stat_activity
				ORDER BY datname, usename, pid";
        } else {
            $database = $this->clean($database);
            $sql = "SELECT datname, usename, pid, waiting, state_change as query_start,
                case when state='idle in transaction' then '<IDLE> in transaction'
                    when state = 'idle' then '<IDLE>' else query end as query
				FROM pg_catalog.pg_stat_activity
				WHERE datname='{$database}'
				ORDER BY usename, pid";
        }

        return $this->selectSet($sql);
    }
}
