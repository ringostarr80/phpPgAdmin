<?php

declare(strict_types=1);

use PhpPgAdmin\Website\DropRole;

require_once __DIR__ . DIRECTORY_SEPARATOR . 'autoload.php';

$website = new DropRole();
echo $website->buildHtmlString();
