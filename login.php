<?php

declare(strict_types=1);

use PhpPgAdmin\Website\Login;

require_once __DIR__ . DIRECTORY_SEPARATOR . 'autoload.php';

$website = new Login();
echo $website->buildHtmlString();
