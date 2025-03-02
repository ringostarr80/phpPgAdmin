<?php

declare(strict_types=1);

namespace PhpPgAdmin\Database;

class Postgres96 extends Postgres10
{
    public float $majorVersion = 9.6;

    // Sequence functions

    /**
     * Returns properties of a single sequence
     * @return \ADORecordSet|int A recordset
     */
    public function getSequence(string $sequence): \ADORecordSet|int
    {
        $c_schema = $this->schema;
        $c_schema = $this->clean($c_schema);
        $c_sequence = $sequence;
        $sequence = $this->fieldClean($sequence);
        $c_sequence = $this->clean($c_sequence);

        $sql = "
			SELECT c.relname AS seqname, s.*,
				pg_catalog.obj_description(s.tableoid, 'pg_class') AS seqcomment,
				u.usename AS seqowner, n.nspname
			FROM \"{$sequence}\" AS s, pg_catalog.pg_class c, pg_catalog.pg_user u, pg_catalog.pg_namespace n
			WHERE c.relowner=u.usesysid AND c.relnamespace=n.oid
				AND c.relname = '{$c_sequence}' AND c.relkind = 'S' AND n.nspname='{$c_schema}'
				AND n.oid = c.relnamespace";

        return $this->selectSet($sql);
    }
}
