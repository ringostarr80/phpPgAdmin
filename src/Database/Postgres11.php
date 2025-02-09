<?php

declare(strict_types=1);

namespace PhpPgAdmin\Database;

class Postgres11 extends Postgres
{
    public float $majorVersion = 11;

    /**
     * Returns the current default_with_oids setting
     * @return default_with_oids setting
     */
    public function getDefaultWithOid()
    {

        $sql = "SHOW default_with_oids";

        return $this->selectField($sql, 'default_with_oids');
    }

    /**
     * Checks to see whether or not a table has a unique id column
     * @param $table The table name
     * @return True if it has a unique id, false otherwise
     * @return null error
     **/
    public function hasObjectID($table)
    {
        $c_schema = $this->_schema;
        $c_schema = $this->clean($c_schema);
        $table = $this->clean($table);

        $sql = "SELECT relhasoids FROM pg_catalog.pg_class WHERE relname='{$table}'
			AND relnamespace = (SELECT oid FROM pg_catalog.pg_namespace WHERE nspname='{$c_schema}')";

        $rs = $this->selectSet($sql);
        if ($rs->recordCount() != 1) {
            return null;
        } else {
            $rs->fields['relhasoids'] = $this->phpBool($rs->fields['relhasoids']);
            return $rs->fields['relhasoids'];
        }
    }

    // Capabilities
    public function hasServerOids()
    {
        return true;
    }
}
