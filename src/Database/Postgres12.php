<?php

declare(strict_types=1);

namespace PhpPgAdmin\Database;

class Postgres12 extends Postgres13
{
    public float $major_version = 12;

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
        include_once('./help/PostgresDoc12.php');
        return $this->help_page;
    }


    // Capabilities
}
