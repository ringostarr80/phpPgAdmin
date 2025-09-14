<?php

declare(strict_types=1);

use PhpPgAdmin\Website\HistoryClear;

require_once __DIR__ . DIRECTORY_SEPARATOR . 'autoload.php';

$website = new HistoryClear();
echo $website->buildHtmlString();
