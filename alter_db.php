<?php

declare(strict_types=1);

use PhpPgAdmin\Website\AlterDb;

require_once __DIR__ . DIRECTORY_SEPARATOR . 'autoload.php';

$website = new AlterDb();
echo $website->buildHtmlString();
