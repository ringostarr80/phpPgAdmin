<?php

declare(strict_types=1);

use PhpPgAdmin\Website\AlterTablespace;

require_once __DIR__ . DIRECTORY_SEPARATOR . 'autoload.php';

$website = new AlterTablespace();
echo $website->buildHtmlString();
