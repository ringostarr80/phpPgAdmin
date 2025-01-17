<?php

use PhpPgAdmin\Website\Intro;

require_once __DIR__ . DIRECTORY_SEPARATOR . 'autoload.php';

$index = new Intro();
echo $index->buildHtmlString();
