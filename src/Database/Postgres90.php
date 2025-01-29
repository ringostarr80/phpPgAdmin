<?php

declare(strict_types=1);

namespace PhpPgAdmin\Database;

class Postgres90 extends Postgres91
{
    public float $major_version = 9.0;

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
        include_once('./help/PostgresDoc90.php');
        return $this->help_page;
    }

    // Capabilities
}
