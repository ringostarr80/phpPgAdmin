<?php

declare(strict_types=1);

use PhpPgAdmin\Api\Servers\Tree;

require_once __DIR__ . DIRECTORY_SEPARATOR . 'autoload.php';

$serverTree = new Tree();
$serverTree->outputXmlTree();
