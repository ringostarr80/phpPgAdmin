<?php

declare(strict_types=1);

use PhpPgAdmin\Website\ChangePassword;

require_once __DIR__ . DIRECTORY_SEPARATOR . 'autoload.php';

$website = new ChangePassword();
echo $website->buildHtmlString();
