<?php

declare(strict_types=1);

use PhpPgAdmin\Website\CreateRole;

require_once __DIR__ . DIRECTORY_SEPARATOR . 'autoload.php';

$website = new CreateRole();
echo $website->buildHtmlString();
