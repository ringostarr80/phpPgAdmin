<?php

declare(strict_types=1);

namespace PhpPgAdmin\Database;

class Postgres96 extends Postgres10
{
    public float $major_version = 9.6;

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
        include_once('./help/PostgresDoc96.php');
        return $this->help_page;
    }

    // Sequence functions

    /**
     * Returns properties of a single sequence
     * @param $sequence Sequence name
     * @return A recordset
     */
    public function getSequence($sequence)
    {
        $c_schema = $this->_schema;
        $this->clean($c_schema);
        $c_sequence = $sequence;
        $this->fieldClean($sequence);
        $this->clean($c_sequence);

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
