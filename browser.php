<?php

use PhpPgAdmin\Website\Browser;

require_once __DIR__ . DIRECTORY_SEPARATOR . 'autoload.php';

$website = new Browser();
echo $website->buildHtmlString();
