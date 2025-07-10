<?php

declare(strict_types=1);

use PhpPgAdmin\Website\CreateDb;

require_once __DIR__ . DIRECTORY_SEPARATOR . 'autoload.php';

$website = new CreateDb();
echo $website->buildHtmlString();
