<?php

declare(strict_types=1);

use PhpPgAdmin\Website\CreateTablespace;

require_once __DIR__ . DIRECTORY_SEPARATOR . 'autoload.php';

$website = new CreateTablespace();
echo $website->buildHtmlString();
