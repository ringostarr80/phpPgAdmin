<?php

declare(strict_types=1);

namespace PhpPgAdmin\Database;

class Postgres13 extends Postgres
{
    public float $major_version = 13;

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
        include_once('./help/PostgresDoc13.php');
        return $this->help_page;
    }


    // Capabilities
}
