<?php

declare(strict_types=1);

use PhpPgAdmin\Website\DbExport;

require_once __DIR__ . DIRECTORY_SEPARATOR . 'autoload.php';

$website = new DbExport();
echo $website->buildHtmlString();
