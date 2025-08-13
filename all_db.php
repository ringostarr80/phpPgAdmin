<?php

use PhpPgAdmin\Website\AllDb;

require_once __DIR__ . DIRECTORY_SEPARATOR . 'autoload.php';

$website = new AllDb();
echo $website->buildHtmlString();
