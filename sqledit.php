<?php

declare(strict_types=1);

use PhpPgAdmin\Website\SqlEdit;

require_once __DIR__ . DIRECTORY_SEPARATOR . 'autoload.php';

$website = new SqlEdit();
echo $website->buildHtmlString();
