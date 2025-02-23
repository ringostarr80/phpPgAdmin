<?php

declare(strict_types=1);

namespace PhpPgAdmin\Database;

use PhpPgAdmin\{ArrayRecordSet, Config};
use PhpPgAdmin\DDD\Entities\ServerSession;

class Postgres83 extends Postgres84
{
    public float $majorVersion = 8.3;

    // List of all legal privileges that can be applied to different types
    // of objects.
    public array $privlist = array(
        'table' => array('SELECT', 'INSERT', 'UPDATE', 'DELETE', 'REFERENCES', 'TRIGGER', 'ALL PRIVILEGES'),
        'view' => array('SELECT', 'INSERT', 'UPDATE', 'DELETE', 'REFERENCES', 'TRIGGER', 'ALL PRIVILEGES'),
        'sequence' => array('USAGE', 'SELECT', 'UPDATE', 'ALL PRIVILEGES'),
        'database' => array('CREATE', 'TEMPORARY', 'CONNECT', 'ALL PRIVILEGES'),
        'function' => array('EXECUTE', 'ALL PRIVILEGES'),
        'language' => array('USAGE', 'ALL PRIVILEGES'),
        'schema' => array('CREATE', 'USAGE', 'ALL PRIVILEGES'),
        'tablespace' => array('CREATE', 'ALL PRIVILEGES')
    );
    /**
     * List of characters in acl lists and the privileges they refer to.
     *
     * @var array<string, string>
     */
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
        'T' => 'TEMPORARY',
        'c' => 'CONNECT'
    ];

    public function __construct(\ADOConnection $conn)
    {
        parent::__construct($conn);

        $this->helpPage['pg.fts'] = 'textsearch.html';

        $this->helpPage['pg.ftscfg'] = 'textsearch-intro.html#TEXTSEARCH-INTRO-CONFIGURATIONS';
        $this->helpPage['pg.ftscfg.example'] = 'textsearch-configuration.html';
        $this->helpPage['pg.ftscfg.drop'] = 'sql-droptsconfig.html';
        $this->helpPage['pg.ftscfg.create'] = 'sql-createtsconfig.html';
        $this->helpPage['pg.ftscfg.alter'] = 'sql-altertsconfig.html';

        $this->helpPage['pg.ftsdict'] = 'textsearch-dictionaries.html';
        $this->helpPage['pg.ftsdict.drop'] = 'sql-droptsdictionary.html';
        $this->helpPage['pg.ftsdict.create'] = ['sql-createtsdictionary.html', 'sql-createtstemplate.html'];
        $this->helpPage['pg.ftsdict.alter'] = 'sql-altertsdictionary.html';

        $this->helpPage['pg.ftsparser'] = 'textsearch-parsers.html';
    }

    // Database functions

    /**
     * Return all database available on the server
     * @param $currentdatabase database name that should be on top of the resultset
     *
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

        $sql = "
			SELECT pdb.datname AS datname, pr.rolname AS datowner, pg_encoding_to_char(encoding) AS datencoding,
				(SELECT description FROM pg_catalog.pg_shdescription pd WHERE pdb.oid=pd.objoid AND pd.classoid='pg_database'::regclass) AS datcomment,
				(SELECT spcname FROM pg_catalog.pg_tablespace pt WHERE pt.oid=pdb.dattablespace) AS tablespace,
				pg_catalog.pg_database_size(pdb.oid) as dbsize
			FROM pg_catalog.pg_database pdb LEFT JOIN pg_catalog.pg_roles pr ON (pdb.datdba = pr.oid)
			WHERE true
				{$where}
				{$clause}
			{$orderby}";

        return $this->selectSet($sql);
    }

    // Administration functions

    /**
     * Returns all available autovacuum per table information.
     * @return \ADORecordSet|ArrayRecordSet|int A recordset
     */
    public function getTableAutovacuum(string $table = ''): \ADORecordSet|ArrayRecordSet|int
    {
        $sql = '';

        if ($table !== '') {
            $table = $this->clean($table);
            $c_schema = $this->_schema;
            $c_schema = $this->clean($c_schema);

            $sql = "
				SELECT vacrelid, nspname, relname, 
					CASE enabled 
						WHEN 't' THEN 'on' 
						ELSE 'off' 
					END AS autovacuum_enabled, vac_base_thresh AS autovacuum_vacuum_threshold,
					vac_scale_factor AS autovacuum_vacuum_scale_factor, anl_base_thresh AS autovacuum_analyze_threshold, 
					anl_scale_factor AS autovacuum_analyze_scale_factor, vac_cost_delay AS autovacuum_vacuum_cost_delay, 
					vac_cost_limit AS autovacuum_vacuum_cost_limit
				FROM pg_autovacuum AS a
					join pg_class AS c on (c.oid=a.vacrelid)
					join pg_namespace AS n on (n.oid=c.relnamespace)
				WHERE c.relname = '{$table}' AND n.nspname = '{$c_schema}'
				ORDER BY nspname, relname
			";
        } else {
            $sql = "
				SELECT vacrelid, nspname, relname, 
					CASE enabled 
						WHEN 't' THEN 'on' 
						ELSE 'off' 
					END AS autovacuum_enabled, vac_base_thresh AS autovacuum_vacuum_threshold,
					vac_scale_factor AS autovacuum_vacuum_scale_factor, anl_base_thresh AS autovacuum_analyze_threshold, 
					anl_scale_factor AS autovacuum_analyze_scale_factor, vac_cost_delay AS autovacuum_vacuum_cost_delay, 
					vac_cost_limit AS autovacuum_vacuum_cost_limit
				FROM pg_autovacuum AS a
					join pg_class AS c on (c.oid=a.vacrelid)
					join pg_namespace AS n on (n.oid=c.relnamespace)
				ORDER BY nspname, relname
			";
        }

        return $this->selectSet($sql);
    }

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
        if (!is_string($toid) && !is_numeric($toid) && !($toid instanceof \Stringable)) {
            $toid = '';
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

        if (empty($_POST['vacuum_freeze_min_age'])) {
            $_POST['vacuum_freeze_min_age'] = $defaults['vacuum_freeze_min_age'];
        }

        if (empty($_POST['autovacuum_freeze_max_age'])) {
            $_POST['autovacuum_freeze_max_age'] = $defaults['autovacuum_freeze_max_age'];
        }


        $rs = $this->selectSet("SELECT vacrelid 
			FROM \"pg_catalog\".\"pg_autovacuum\" 
			WHERE vacrelid = {$toid};");

        $status = -1; // ini
        if (
            $rs instanceof \ADORecordSet &&
            $rs->recordCount() &&
            is_array($rs->fields) &&
            $rs->fields['vacrelid'] == $toid
        ) {
            // table exists in pg_autovacuum, UPDATE
            $sql = sprintf(
                "UPDATE \"pg_catalog\".\"pg_autovacuum\" SET 
						enabled = '%s',
						vac_base_thresh = %s,
						vac_scale_factor = %s,
						anl_base_thresh = %s,
						anl_scale_factor = %s,
						vac_cost_delay = %s,
						vac_cost_limit = %s,
						freeze_min_age = %s,
						freeze_max_age = %s
					WHERE vacrelid = {$toid};
				",
                $_POST['autovacuum_enabled'] === 'on' ? 't' : 'f',
                is_string($_POST['autovacuum_vacuum_threshold']) ? $_POST['autovacuum_vacuum_threshold'] : '',
                is_string($_POST['autovacuum_vacuum_scale_factor']) ? $_POST['autovacuum_vacuum_scale_factor'] : '',
                is_string($_POST['autovacuum_analyze_threshold']) ? $_POST['autovacuum_analyze_threshold'] : '',
                is_string($_POST['autovacuum_analyze_scale_factor']) ? $_POST['autovacuum_analyze_scale_factor'] : '',
                is_string($_POST['autovacuum_vacuum_cost_delay']) ? $_POST['autovacuum_vacuum_cost_delay'] : '',
                is_string($_POST['autovacuum_vacuum_cost_limit']) ? $_POST['autovacuum_vacuum_cost_limit'] : '',
                is_string($_POST['vacuum_freeze_min_age']) ? $_POST['vacuum_freeze_min_age'] : '',
                is_string($_POST['autovacuum_freeze_max_age']) ? $_POST['autovacuum_freeze_max_age'] : ''
            );
            $status = $this->execute($sql);
        } else {
            // table doesn't exists in pg_autovacuum, INSERT
            $sql = sprintf(
                "INSERT INTO \"pg_catalog\".\"pg_autovacuum\" 
				VALUES (%s, '%s', %s, %s, %s, %s, %s, %s, %s, %s )",
                $toid,
                $_POST['autovacuum_enabled'] === 'on' ? 't' : 'f',
                is_string($_POST['autovacuum_vacuum_threshold']) ? $_POST['autovacuum_vacuum_threshold'] : '',
                is_string($_POST['autovacuum_vacuum_scale_factor']) ? $_POST['autovacuum_vacuum_scale_factor'] : '',
                is_string($_POST['autovacuum_analyze_threshold']) ? $_POST['autovacuum_analyze_threshold'] : '',
                is_string($_POST['autovacuum_analyze_scale_factor']) ? $_POST['autovacuum_analyze_scale_factor'] : '',
                is_string($_POST['autovacuum_vacuum_cost_delay']) ? $_POST['autovacuum_vacuum_cost_delay'] : '',
                is_string($_POST['autovacuum_vacuum_cost_limit']) ? $_POST['autovacuum_vacuum_cost_limit'] : '',
                is_string($_POST['vacuum_freeze_min_age']) ? $_POST['vacuum_freeze_min_age'] : '',
                is_string($_POST['autovacuum_freeze_max_age']) ? $_POST['autovacuum_freeze_max_age'] : ''
            );
            $status = $this->execute($sql);
        }

        return $status;
    }

    /**
     * @return int
     */
    public function dropAutovacuum(string $table): int
    {
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

        return $this->deleteRow(
            'pg_autovacuum',
            [
                'vacrelid' => $rs instanceof \ADORecordSet &&
                    is_array($rs->fields) &&
                    isset($rs->fields['oid']) &&
                    is_numeric($rs->fields['oid']) ?
                    (string)$rs->fields['oid'] : '0'
            ],
            'pg_catalog'
        );
    }

    // Sequence functions

    /**
     * Alter a sequence's properties
     * @param \ADORecordSet $seqrs The sequence RecordSet returned by getSequence()
     * @param $increment The sequence incremental value
     * @param $minvalue The sequence minimum value
     * @param $maxvalue The sequence maximum value
     * @param $restartvalue The sequence current value
     * @param $cachevalue The sequence cache value
     * @param $cycledvalue Sequence can cycle ?
     * @param $startvalue The sequence start value when issuing a restart (ignored)
     * @return int 0 success
     */
    public function alterSequenceProps(
        \ADORecordSet $seqrs,
        ?string $increment = null,
        ?string $minvalue = null,
        ?string $maxvalue = null,
        ?string $restartvalue = null,
        ?string $cachevalue = null,
        ?string $cycledvalue = null,
        ?string $startvalue = null
    ): int {

        $sql = '';
        /* vars are cleaned in alterSequenceInternal */
        if (is_array($seqrs->fields)) {
            if (!empty($increment) && $increment != $seqrs->fields['increment_by']) {
                $sql .= " INCREMENT {$increment}";
            }
            if (!empty($minvalue) && $minvalue != $seqrs->fields['min_value']) {
                $sql .= " MINVALUE {$minvalue}";
            }
            if (!empty($maxvalue) && $maxvalue != $seqrs->fields['max_value']) {
                $sql .= " MAXVALUE {$maxvalue}";
            }
            if (!empty($restartvalue) && $restartvalue != $seqrs->fields['last_value']) {
                $sql .= " RESTART {$restartvalue}";
            }
            if (!empty($cachevalue) && $cachevalue != $seqrs->fields['cache_value']) {
                $sql .= " CACHE {$cachevalue}";
            }
        }
        // toggle cycle yes/no
        if (!is_null($cycledvalue)) {
            $sql .= (!$cycledvalue ? ' NO ' : '') . " CYCLE";
        }
        if (
            $sql != '' &&
            is_array($seqrs->fields) &&
            isset($seqrs->fields['seqname']) &&
            (
                is_string($seqrs->fields['seqname']) ||
                is_numeric($seqrs->fields['seqname']) ||
                $seqrs->fields['seqname'] instanceof \Stringable
            )
        ) {
            $f_schema = $this->_schema;
            $f_schema = $this->fieldClean($f_schema);
            $sql = "ALTER SEQUENCE \"{$f_schema}\".\"{$seqrs->fields['seqname']}\" {$sql}";
            return $this->execute($sql);
        }
        return 0;
    }

    /**
     * Alter a sequence's owner
     * @param $seqrs The sequence RecordSet returned by getSequence()
     * @param $name The new owner for the sequence
     * @return int 0 success
     */
    public function alterSequenceOwner(\ADORecordSet $seqrs, ?string $owner): int
    {
        // If owner has been changed, then do the alteration.  We are
        // careful to avoid this generally as changing owner is a
        // superuser only function.
        /* vars are cleaned in alterSequenceInternal */
        if (
            !empty($owner) &&
            is_array($seqrs->fields) &&
            $seqrs->fields['seqowner'] != $owner &&
            isset($seqrs->fields['seqname']) &&
            (
                is_string($seqrs->fields['seqname']) ||
                is_numeric($seqrs->fields['seqname']) ||
                $seqrs->fields['seqname'] instanceof \Stringable
            )
        ) {
            $f_schema = $this->_schema;
            $f_schema = $this->fieldClean($f_schema);
            $sql = "ALTER TABLE \"{$f_schema}\".\"{$seqrs->fields['seqname']}\" OWNER TO \"{$owner}\"";
            return $this->execute($sql);
        }
        return 0;
    }

    // Function functions

    /**
     * Returns all details for a particular function
     * @param $func The name of the function to retrieve
     * @return \ADORecordSet|int Function info
     */
    public function getFunction(string $function_oid): \ADORecordSet|int
    {
        $function_oid = $this->clean($function_oid);

        $sql = "
			SELECT
				pc.oid AS prooid, proname, pg_catalog.pg_get_userbyid(proowner) AS proowner,
				nspname as proschema, lanname as prolanguage, procost, prorows,
				pg_catalog.format_type(prorettype, NULL) as proresult, prosrc,
				probin, proretset, proisstrict, provolatile, prosecdef,
				pg_catalog.oidvectortypes(pc.proargtypes) AS proarguments,
				proargnames AS proargnames,
				pg_catalog.obj_description(pc.oid, 'pg_proc') AS procomment,
				proconfig
			FROM
				pg_catalog.pg_proc pc, pg_catalog.pg_language pl,
				pg_catalog.pg_namespace pn
			WHERE
				pc.oid = '{$function_oid}'::oid AND pc.prolang = pl.oid
				AND pc.pronamespace = pn.oid
			";

        return $this->selectSet($sql);
    }


    // Capabilities
    public function hasQueryKill(): bool
    {
        return false;
    }

    public function hasDatabaseCollation(): bool
    {
        return false;
    }

    public function hasAlterSequenceStart(): bool
    {
        return false;
    }
}
