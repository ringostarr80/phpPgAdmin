<?php

declare(strict_types=1);

use PhpPgAdmin\Website\AllDbExport;

require_once __DIR__ . DIRECTORY_SEPARATOR . 'autoload.php';

$website = new AllDbExport();
echo $website->buildHtmlString();
