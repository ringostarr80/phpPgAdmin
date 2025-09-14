<?php

declare(strict_types=1);

use PhpPgAdmin\Website\HistoryDelete;

require_once __DIR__ . DIRECTORY_SEPARATOR . 'autoload.php';

$website = new HistoryDelete();
echo $website->buildHtmlString();
