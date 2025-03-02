<?php

declare(strict_types=1);

use PhpPgAdmin\Session;

require_once __DIR__ . DIRECTORY_SEPARATOR . 'autoload.php';

Session::destroy();

header('Location: ./');
