<?php

use PhpPgAdmin\Website\Browser;

require_once __DIR__ . DIRECTORY_SEPARATOR . 'autoload.php';

$index = new Browser();
echo $index->buildHtmlString();
