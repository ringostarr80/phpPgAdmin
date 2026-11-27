<?php

declare(strict_types=1);

use PhpPgAdmin\Website\Account;

require_once __DIR__ . DIRECTORY_SEPARATOR . 'autoload.php';

$website = new Account();
echo $website->buildHtmlString();
