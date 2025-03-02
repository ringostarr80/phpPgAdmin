<?php

declare(strict_types=1);

namespace PhpPgAdmin\Database;

use ADORecordSet;

class Postgres11 extends Postgres
{
    public float $majorVersion = 11;

    /**
     * Returns the current default_with_oids setting
     * @return string|int|false default_with_oids setting
     */
    public function getDefaultWithOid(): string|int|false
    {
        return $this->selectField("SHOW default_with_oids", 'default_with_oids');
    }

    /**
     * Checks to see whether or not a table has a unique id column
     * @param $table The table name
     * @return ?bool True if it has a unique id, false otherwise. null error.
     **/
    public function hasObjectID(string $table): ?bool
    {
        $c_schema = $this->schema;
        $c_schema = $this->clean($c_schema);
        $table = $this->clean($table);

        $sql = "SELECT relhasoids FROM pg_catalog.pg_class WHERE relname='{$table}'
			AND relnamespace = (SELECT oid FROM pg_catalog.pg_namespace WHERE nspname='{$c_schema}')";

        $rs = $this->selectSet($sql);
        if ($rs instanceof ADORecordSet) {
            if ($rs->recordCount() != 1) {
                return null;
            } elseif (is_array($rs->fields)) {
                return $this->phpBool($rs->fields['relhasoids']);
            }
        }

        return null;
    }

    // Capabilities
    public function hasServerOids(): bool
    {
        return true;
    }
}
