<?php

declare(strict_types=1);

namespace PhpPgAdmin\Database;

use ADORecordSet;
use PhpPgAdmin\Config;
use PhpPgAdmin\DDD\Entities\ServerSession;

class Postgres80 extends Postgres81
{
    public float $majorVersion = 8.0;

    /**
     * Map of database encoding names to HTTP encoding names.  If a
     * database encoding does not appear in this list, then its HTTP
     * encoding name is the same as its database encoding name.
     *
     * @var array<string, string>
     */
    public array $codemap = [
        'ALT' => 'CP866',
        'EUC_CN' => 'GB2312',
        'EUC_JP' => 'EUC-JP',
        'EUC_KR' => 'EUC-KR',
        'EUC_TW' => 'EUC-TW',
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
        // The following encoding map is a known error in PostgreSQL < 7.2
        // See the constructor for Postgres72.
        'LATIN5' => 'ISO-8859-5',
        'LATIN6' => 'ISO-8859-10',
        'LATIN7' => 'ISO-8859-13',
        'LATIN8' => 'ISO-8859-14',
        'LATIN9' => 'ISO-8859-15',
        'LATIN10' => 'ISO-8859-16',
        'SQL_ASCII' => 'US-ASCII',
        'TCVN' => 'CP1258',
        'UNICODE' => 'UTF-8',
        'WIN' => 'CP1251',
        'WIN874' => 'CP874',
        'WIN1256' => 'CP1256'
    ];

    public function __construct(\ADOConnection $conn)
    {
        parent::__construct($conn);

        $this->helpPage['pg.column.add'][0] = 'ddl-alter.html#AEN2217';
        $this->helpPage['pg.column.drop'][0] = 'ddl-alter.html#AEN2226';

        $this->helpPage['pg.constraint.add'] = 'ddl-alter.html#AEN2217';
        $this->helpPage['pg.constraint.check'] = 'ddl-constraints.html#AEN1978';
        $this->helpPage['pg.constraint.drop'] = 'ddl-alter.html#AEN2226';
        $this->helpPage['pg.constraint.primary_key'] = 'ddl-constraints.html#AEN2055';
        $this->helpPage['pg.constraint.unique_key'] = 'ddl-constraints.html#AEN2033';

        $this->helpPage['pg.domain'] = 'extend-type-system.html#AEN27940';

        $this->helpPage['pg.function'][2] = 'sql-expressions.html#AEN1652';

        $this->helpPage['pg.operator'][2] = 'sql-expressions.html#AEN1623';
    }

    // Database functions

    /**
     * Return all database available on the server
     * @return mixed A list of databases, sorted alphabetically
     */
    public function getDatabases(?string $currentdatabase = null)
    {
        $serverSession = ServerSession::fromRequestParameter();

        if (Config::ownedOnly() && !$this->isSuperUser() && !is_null($serverSession)) {
            $username = $this->clean((string)$serverSession->Username);
            $clause = " AND pu.usename='{$username}'";
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

        $sql = "SELECT pdb.datname AS datname, pu.usename AS datowner, pg_encoding_to_char(encoding) AS datencoding,
            (SELECT description FROM pg_description pd WHERE pdb.oid=pd.objoid) AS datcomment,
            (SELECT spcname FROM pg_catalog.pg_tablespace pt WHERE pt.oid=pdb.dattablespace) AS tablespace
            FROM pg_database pdb, pg_user pu
			WHERE pdb.datdba = pu.usesysid
			{$where}
			{$clause}
			{$orderby}";

        return $this->selectSet($sql);
    }

    // Schema functions

    /**
     * Return all schemas in the current database.
     * @return \ADORecordSet|int All schemas, sorted alphabetically
     */
    public function getSchemas(): \ADORecordSet|int
    {
        if (!Config::showSystem()) {
            $where = "WHERE nspname NOT LIKE 'pg@_%' ESCAPE '@' AND nspname != 'information_schema'";
        } else {
            $where = "WHERE nspname !~ '^pg_t(emp_[0-9]+|oast)$'";
        }
        $sql = "
			SELECT pn.nspname, pu.usename AS nspowner,
				pg_catalog.obj_description(pn.oid, 'pg_namespace') AS nspcomment
			FROM pg_catalog.pg_namespace pn
				LEFT JOIN pg_catalog.pg_user pu ON (pn.nspowner = pu.usesysid)
			{$where}
			ORDER BY nspname";

        return $this->selectSet($sql);
    }

    /**
     * Return all information relating to a schema
     * @param string $schema The name of the schema
     * @return \ADORecordSet|int Schema information
     */
    public function getSchemaByName(string $schema): \ADORecordSet|int
    {
        $schema = $this->clean($schema);
        $sql = "
			SELECT nspname, nspowner, u.usename AS ownername, nspacl,
				pg_catalog.obj_description(pn.oid, 'pg_namespace') as nspcomment
            FROM pg_catalog.pg_namespace pn
            	LEFT JOIN pg_shadow as u ON pn.nspowner = u.usesysid
			WHERE nspname='{$schema}'";
        return $this->selectSet($sql);
    }

    // Table functions

    /**
     * Protected method which alter a table
     * SHOULDN'T BE CALLED OUTSIDE OF A TRANSACTION
     * @param $tblrs The table recordSet returned by getTable()
     * @param $name The new name for the table
     * @param $owner The new owner for the table
     * @param $schema The new schema for the table
     * @param $comment The comment on the table
     * @param $tablespace The new tablespace for the table ('' means leave as is)
     * @return int 0 success
     * -3 rename error
     * -4 comment error
     * -5 owner error
     * -6 tablespace error
     */
    protected function alterTableInternal(
        \ADORecordSet $tblrs,
        string $name,
        string $owner,
        string $schema,
        string $comment,
        string $tablespace
    ): int {
        /* $schema not supported in pg80- */

        // Comment
        if (is_array($tblrs->fields)) {
            $status = $this->setComment('TABLE', '', $tblrs->fields['relname'], $comment);
            if ($status != 0) {
                return -4;
            }
        }

        // Owner
        $owner = $this->fieldClean($owner);
        $status = $this->alterTableOwner($tblrs, $owner);
        if ($status != 0) {
            return -5;
        }

        // Tablespace
        $tablespace = $this->fieldClean($tablespace);
        $status = $this->alterTableTablespace($tblrs, $tablespace);
        if ($status != 0) {
            return -6;
        }

        // Rename
        $name = $this->fieldClean($name);
        $status = $this->alterTableName($tblrs, $name);
        if ($status != 0) {
            return -3;
        }

        return 0;
    }

    // View functions

    /**
     * Protected method which alter a view
     * SHOULDN'T BE CALLED OUTSIDE OF A TRANSACTION
     * @param $vwrs The view recordSet returned by getView()
     * @param $name The new name for the view
     * @param $owner The new owner for the view
     * @param $comment The comment on the view
     * @return int 0 success, -3 rename error, -4 comment error, -5 owner error
     */
    protected function alterViewInternal(
        \ADORecordSet $vwrs,
        string $name,
        string $owner,
        string $schema,
        string $comment
    ): int {
        /* $schema not supported in pg80- */
        if (is_array($vwrs->fields)) {
            $vwrs->fields = $this->fieldArrayClean($vwrs->fields);

            // Comment
            if ($this->setComment('VIEW', $vwrs->fields['relname'], '', $comment) != 0) {
                return -4;
            }
        }

        // Owner
        $owner = $this->fieldClean($owner);
        $status = $this->alterViewOwner($vwrs, $owner);
        if ($status != 0) {
            return -5;
        }

        // Rename
        $name = $this->fieldClean($name);
        $status = $this->alterViewName($vwrs, $name);
        if ($status != 0) {
            return -3;
        }

        return 0;
    }

    // Sequence functions

    /**
     * Protected method which alter a sequence
     * SHOULDN'T BE CALLED OUTSIDE OF A TRANSACTION
     * @param \ADORecordSet $seqrs The sequence recordSet returned by getSequence()
     * @param $name The new name for the sequence
     * @param $comment The comment on the sequence
     * @param $owner The new owner for the sequence
     * @param $schema The new schema for the sequence
     * @param $increment The increment
     * @param $minvalue The min value
     * @param $maxvalue The max value
     * @param $restartvalue The starting value
     * @param $cachevalue The cache value
     * @param $cycledvalue True if cycled, false otherwise
     * @param $startvalue The sequence start value when issuing a restart
     * @return int 0 success, -3 rename error, -4 comment error, -5 owner error, -6 get sequence props error, -7 schema error
     */
    protected function alterSequenceInternal(
        \ADORecordSet $seqrs,
        ?string $name = null,
        ?string $comment = null,
        ?string $owner = null,
        ?string $schema = null,
        ?string $increment = null,
        ?string $minvalue = null,
        ?string $maxvalue = null,
        ?string $restartvalue = null,
        ?string $cachevalue = null,
        ?string $cycledvalue = null,
        ?string $startvalue = null
    ): int {
        /* $schema not supported in pg80- */
        if (is_array($seqrs->fields)) {
            $seqrs->fields = $this->fieldArrayClean($seqrs->fields);

            // Comment
            $status = $this->setComment('SEQUENCE', $seqrs->fields['seqname'], '', $comment);
            if ($status != 0) {
                return -4;
            }
        }

        // Owner
        $owner = $this->fieldClean($owner);
        $status = $this->alterSequenceOwner($seqrs, $owner);
        if ($status != 0) {
            return -5;
        }

        // Props
        $increment = $this->clean($increment);
        $minvalue = $this->clean($minvalue);
        $maxvalue = $this->clean($maxvalue);
        $restartvalue = $this->clean($restartvalue);
        $cachevalue = $this->clean($cachevalue);
        $cycledvalue = $this->clean($cycledvalue);
        $startvalue = $this->clean($startvalue);
        $status = $this->alterSequenceProps(
            $seqrs,
            $increment,
            $minvalue,
            $maxvalue,
            $restartvalue,
            $cachevalue,
            $cycledvalue,
            null
        );
        if ($status != 0) {
            return -6;
        }

        // Rename
        $name = $this->fieldClean($name);
        $status = $this->alterSequenceName($seqrs, $name);
        if ($status != 0) {
            return -3;
        }

        return 0;
    }

    // Role, User/group functions

    /**
     * Changes a user's password
     * @param string $username The username
     * @param string $password The new password
     * @return int 0 success
     */
    public function changePassword(string $username, string $password): int
    {
        $enc = $this->encryptPasswordInternal($username, $password);
        $username = $this->fieldClean($username);
        $enc = $this->clean($enc);

        $sql = "ALTER USER \"{$username}\" WITH ENCRYPTED PASSWORD '{$enc}'";

        return $this->execute($sql);
    }

    // Aggregate functions

    /**
     * Gets all information for an aggregate
     * @param string $name The name of the aggregate
     * @param string $basetype The input data type of the aggregate
     * @return \ADORecordSet|int A recordset
     */
    public function getAggregate(string $name, string $basetype): \ADORecordSet|int
    {
        $c_schema = $this->_schema;
        $c_schema = $this->clean($c_schema);
        $name = $this->clean($name);
        $basetype = $this->clean($basetype);

        $sql = "
			SELECT p.proname,
				CASE p.proargtypes[0]
					WHEN 'pg_catalog.\"any\"'::pg_catalog.regtype THEN NULL
					ELSE pg_catalog.format_type(p.proargtypes[0], NULL)
				END AS proargtypes, a.aggtransfn, format_type(a.aggtranstype, NULL) AS aggstype,
				a.aggfinalfn, a.agginitval, u.usename, pg_catalog.obj_description(p.oid, 'pg_proc') AS aggrcomment
			FROM pg_catalog.pg_proc p, pg_catalog.pg_namespace n, pg_catalog.pg_user u, pg_catalog.pg_aggregate a
			WHERE n.oid = p.pronamespace AND p.proowner=u.usesysid AND p.oid=a.aggfnoid
				AND p.proisagg AND n.nspname='{$c_schema}'
				AND p.proname='{$name}'
				AND CASE p.proargtypes[0]
					WHEN 'pg_catalog.\"any\"'::pg_catalog.regtype THEN ''
					ELSE pg_catalog.format_type(p.proargtypes[0], NULL)
				END ='{$basetype}'";

        return $this->selectSet($sql);
    }

    // Capabilities
    public function hasAggregateSortOp(): bool
    {
        return false;
    }

    public function hasAlterTableSchema(): bool
    {
        return false;
    }

    public function hasAutovacuum(): bool
    {
        return false;
    }

    public function hasDisableTriggers(): bool
    {
        return false;
    }

    public function hasFunctionAlterSchema(): bool
    {
        return false;
    }

    public function hasPreparedXacts(): bool
    {
        return false;
    }

    public function hasRoles(): bool
    {
        return false;
    }

    public function hasAlterSequenceSchema(): bool
    {
        return false;
    }

    public function hasServerAdminFuncs(): bool
    {
        return false;
    }
}
