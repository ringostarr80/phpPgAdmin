<?php

declare(strict_types=1);

use PhpPgAdmin\Website\ServerLogout;

require_once __DIR__ . DIRECTORY_SEPARATOR . 'autoload.php';

$website = new ServerLogout();
echo $website->buildHtmlString();
