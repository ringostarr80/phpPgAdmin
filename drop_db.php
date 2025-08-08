<?php

declare(strict_types=1);

use PhpPgAdmin\Website\DropDb;

require_once __DIR__ . DIRECTORY_SEPARATOR . 'autoload.php';

$website = new DropDb();
echo $website->buildHtmlString();
