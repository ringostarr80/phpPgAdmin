<?php

declare(strict_types=1);

use PhpPgAdmin\Website\Servers;

require_once __DIR__ . DIRECTORY_SEPARATOR . 'autoload.php';

$website = new Servers();
echo $website->buildHtmlString();
