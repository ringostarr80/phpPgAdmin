<?php

declare(strict_types=1);

use PhpPgAdmin\Website\AlterRole;

require_once __DIR__ . DIRECTORY_SEPARATOR . 'autoload.php';

$website = new AlterRole();
echo $website->buildHtmlString();
