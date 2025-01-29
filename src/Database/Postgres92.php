<?php

declare(strict_types=1);

namespace PhpPgAdmin\Database;

class Postgres92 extends Postgres93
{
    public float $major_version = 9.2;

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
        include_once('./help/PostgresDoc92.php');
        return $this->help_page;
    }
}
