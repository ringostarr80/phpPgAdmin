<?php

declare(strict_types=1);

namespace PhpPgAdmin\Database;

class ADOdbBase
{
    public string $platform = 'UNKNOWN';

    public function __construct(public \ADOConnection $conn)
    {
    }

    /**
     * Turns on or off query debugging
     * @param $debug True to turn on debugging, false otherwise
     */
    public function setDebug(bool $debug): void
    {
        $this->conn->debug = $debug;
    }

    /**
     * Cleans (escapes) a string
     * @return ?string The cleaned string
     */
    public function clean(?string $str): ?string
    {
        return addslashes($str ?? '');
    }

    /**
     * Escapes a string for use as an identifier
     * @param $str The string to escape
     * @return string The escaped string
     */
    public function escapeIdentifier(string $str): string
    {
        return '`' . $str . '`';
    }


    /**
     * Cleans (escapes) an object name (eg. table, field)
     * @return ?string The cleaned string
     */
    public function fieldClean(?string $str): ?string
    {
        if (is_null($str)) {
            return null;
        }
        return str_replace('"', '""', $str);
    }

    /**
     * Cleans (escapes) an array
     * @param array<string> $arr The array to clean, by reference
     * @return array<string> The cleaned array
     */
    public function arrayClean(array $arr): array
    {
        return array_map('addslashes', $arr);
    }

    /**
     * Executes a query on the underlying connection
     * @param $sql The SQL query to execute
     * @return int A recordset
     */
    public function execute(string $sql): int
    {
        // Execute the statement
        $this->conn->Execute($sql);

        // If failure, return error value
        return $this->conn->ErrorNo();
    }

    /**
     * Closes the connection the database class
     * relies on.
     */
    public function close(): void
    {
        $this->conn->close();
    }

    /**
     * Retrieves a ResultSet from a query
     * @param $sql The SQL statement to be executed
     * @return \ADORecordSet|int A recordset or an error code
     */
    public function selectSet(string $sql): \ADORecordSet|int
    {
        // Execute the statement
        $rs = $this->conn->Execute($sql);

        if (!$rs) {
            return $this->conn->ErrorNo();
        }

        return $rs;
    }

    /**
     * Retrieves a single value from a query
     *
     * @@ assumes that the query will return only one row - returns field value in the first row
     *
     * @param $sql The SQL statement to be executed
     * @param $field The field name to be returned
     * @return string|int A single field value, -1 No rows were found
     */
    public function selectField(string $sql, string $field): string|int
    {
        // Execute the statement
        $rs = $this->conn->Execute($sql);

        // If failure, or no rows returned, return error value
        if ($rs === false) {
            return $this->conn->ErrorNo();
        }
        if ($rs->RecordCount() == 0) {
            return -1;
        }
        if (!is_array($rs->fields)) {
            return -2;
        }
        if (!isset($rs->fields[$field])) {
            return -3;
        }
        if (!is_string($rs->fields[$field])) {
            return -4;
        }

        return $rs->fields[$field];
    }

    /**
     * Delete from the database
     * @param $table The name of the table
     * @param array<string, string> $conditions (array) A map of field names to conditions
     * @param $schema (optional) The table's schema
     * @return int 0 success, -1 on referential integrity violation, -2 on no rows deleted
     */
    public function delete(string $table, array $conditions, string $schema = ''): int
    {
        $table = $this->fieldClean($table);

        if (!empty($schema)) {
            $schema = $this->fieldClean($schema);
            $schema = "\"{$schema}\".";
        }

        // Build clause
        $sql = '';
        foreach ($conditions as $key => $value) {
            $key = $this->clean($key);
            $value = $this->clean($value);
            if ($sql) {
                $sql .= " AND \"{$key}\"='{$value}'";
            } else {
                $sql = "DELETE FROM {$schema}\"{$table}\" WHERE \"{$key}\"='{$value}'";
            }
        }

        // Check for failures and Check for referential integrity failure
        if (!$this->conn->Execute($sql) && stristr($this->conn->ErrorMsg(), 'referential')) {
            return -1;
        }

        // Check for no rows modified
        if ($this->conn->Affected_Rows() == 0) {
            return -2;
        }

        return $this->conn->ErrorNo();
    }

    /**
     * Insert a set of values into the database
     * @param $table The table to insert into
     * @param array<string, string> $vars (array) A mapping of the field names to the values to be inserted
     * @return int 0 success, -1 if a unique constraint is violated, -2 if a referential constraint is violated
     */
    public function insert(string $table, array $vars): int
    {
        $table = $this->fieldClean($table);

        $sql = '';
        // Build clause
        if (!empty($vars)) {
            $fields = '';
            $values = '';
            foreach ($vars as $key => $value) {
                $key = $this->clean($key);
                $value = $this->clean($value);

                if ($fields) {
                    $fields .= ", \"{$key}\"";
                } else {
                    $fields = "INSERT INTO \"{$table}\" (\"{$key}\"";
                }

                if ($values) {
                    $values .= ", '{$value}'";
                } else {
                    $values = ") VALUES ('{$value}'";
                }
            }
            $sql = $fields . $values . ')';
        }

        // Check for failures
        if (!$this->conn->Execute($sql)) {
            // Check for unique constraint failure
            if (stristr($this->conn->ErrorMsg(), 'unique')) {
                return -1;
            } elseif (stristr($this->conn->ErrorMsg(), 'referential')) {
                return -2;
            }
        }

        return $this->conn->ErrorNo();
    }

    /**
     * Update a row in the database
     * @param $table The table that is to be updated
     * @param array<string, string> $vars (array) A mapping of the field names to the values to be updated
     * @param array<string, string> $where (array) A mapping of field names to values for the where clause
     * @param array<?string> $nulls (array, optional) An array of fields to be set null
     * @return int 0 success
     * -1 if a unique constraint is violated
     * -2 if a referential constraint is violated
     * -3 on no rows deleted
     */
    public function update(string $table, array $vars, array $where, array $nulls = []): int
    {
        $table = $this->fieldClean($table);

        $setClause = '';
        $whereClause = '';

        // Populate the syntax arrays
        foreach ($vars as $key => $value) {
            $key = $this->fieldClean($key);
            $value = $this->clean($value);
            if ($setClause) {
                $setClause .= ", \"{$key}\"='{$value}'";
            } else {
                $setClause = "UPDATE \"{$table}\" SET \"{$key}\"='{$value}'";
            }
        }

        foreach ($nulls as $value) {
            $value = $this->fieldClean($value);
            if ($setClause) {
                $setClause .= ", \"{$value}\"=NULL";
            } else {
                $setClause = "UPDATE \"{$table}\" SET \"{$value}\"=NULL";
            }
        }

        foreach ($where as $key => $value) {
            $key = $this->fieldClean($key);
            $value = $this->clean($value);
            if ($whereClause) {
                $whereClause .= " AND \"{$key}\"='{$value}'";
            } else {
                $whereClause = " WHERE \"{$key}\"='{$value}'";
            }
        }

        // Check for failures
        if (!$this->conn->Execute($setClause . $whereClause)) {
            // Check for unique constraint failure
            if (stristr($this->conn->ErrorMsg(), 'unique')) {
                return -1;
            } elseif (stristr($this->conn->ErrorMsg(), 'referential')) {
                return -2;
            }
        }

        // Check for no rows modified
        if ($this->conn->Affected_Rows() == 0) {
            return -3;
        }

        return $this->conn->ErrorNo();
    }

    /**
     * Begin a transaction
     * @return bool true success
     */
    public function beginTransaction(): bool
    {
        return $this->conn->BeginTrans();
    }

    public function endTransaction(): bool
    {
        return $this->conn->CommitTrans();
    }

    /**
     * Roll back a transaction
     */
    public function rollbackTransaction(): bool
    {
        return $this->conn->RollbackTrans();
    }

    /**
     * Get the backend platform
     * @return string The backend platform
     */
    public function getPlatform(): string
    {
        return "UNKNOWN";
    }

    // Type conversion routines

    /**
     * Change a parameter from database representation to a boolean, (others evaluate to false)
     * @param $parameter the parameter
     */
    public function phpBool(mixed $parameter): bool
    {
        if ($parameter) {
            return true;
        }

        return false;
    }

    /**
     * Change a db array into a PHP array
     * @param $arr String representing the DB array
     * @return array<string> A PHP array
     */
    public function phpArray(string $dbarr): array
    {
        // Take off the first and last characters (the braces)
        $arr = substr($dbarr, 1, strlen($dbarr) - 2);

        // Pick out array entries by carefully parsing.  This is necessary in order
        // to cope with double quotes and commas, etc.
        $elements = array();
        $i = $j = 0;
        $in_quotes = false;
        while ($i < strlen($arr)) {
            // If current char is a double quote and it's not escaped, then
            // enter quoted bit
            $char = substr($arr, $i, 1);
            if ($char == '"' && ($i == 0 || substr($arr, $i - 1, 1) != '\\')) {
                $in_quotes = !$in_quotes;
            } elseif ($char == ',' && !$in_quotes) {
                // Add text so far to the array
                $elements[] = substr($arr, $j, $i - $j);
                $j = $i + 1;
            }
            $i++;
        }
        // Add final text to the array
        $elements[] = substr($arr, $j);

        // Do one further loop over the elements array to remote double quoting
        // and escaping of double quotes and backslashes
        for ($i = 0; $i < sizeof($elements); $i++) {
            $v = $elements[$i];
            if (strpos($v, '"') === 0) {
                $v = substr($v, 1, strlen($v) - 2);
                $v = str_replace('\\"', '"', $v);
                $v = str_replace('\\\\', '\\', $v);
                $elements[$i] = $v;
            }
        }

        return $elements;
    }
}
