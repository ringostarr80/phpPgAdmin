<?php

declare(strict_types=1);

use PhpPgAdmin\Website\Index;

require_once __DIR__ . DIRECTORY_SEPARATOR . 'autoload.php';

$index = new Index();
echo $index->buildHtmlString();
