<?php

declare(strict_types=1);

use PhpPgAdmin\Website\DropTablespace;

require_once __DIR__ . DIRECTORY_SEPARATOR . 'autoload.php';

$website = new DropTablespace();
echo $website->buildHtmlString();
