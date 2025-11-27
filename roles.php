<?php

declare(strict_types=1);

use PhpPgAdmin\Website\Roles;

require_once __DIR__ . DIRECTORY_SEPARATOR . 'autoload.php';

$website = new Roles();
echo $website->buildHtmlString();
