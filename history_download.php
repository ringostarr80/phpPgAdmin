<?php

declare(strict_types=1);

use PhpPgAdmin\Website\HistoryDownload;

require_once __DIR__ . DIRECTORY_SEPARATOR . 'autoload.php';

$website = new HistoryDownload();
echo $website->buildHtmlString();
