<?php

use PhpPgAdmin\Website\Index;

require_once __DIR__ . DIRECTORY_SEPARATOR . 'autoload.php';

/*
$index = new Index();
echo $index->buildHtmlString();
die();
//*/

// Include application functions
$_no_db_connection = true;
include_once './libraries/lib.inc.php';

$misc->printHeader('', null, true);

print '    <body style="position: absolute; top: 0; bottom: 0; left: 0; right: 0;">' . PHP_EOL;
print '        <div style="display: flex; height: 100%;">' . PHP_EOL;

$rtl = (strcasecmp($lang['applangdir'], 'rtl') == 0);
$cols = $rtl ? '*,' . $conf['left_width'] : $conf['left_width'] . ',*';

$navFrame = '            <iframe src="browser.php" style="width: 200px;" title="browser" name="browser" id="browser" frameborder="0"></iframe>' . PHP_EOL;
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['_originalPath'])) {
    $newPath = basename($_POST['_originalPath']);
    $mainFrame = '            <iframe src="' . $newPath . '" style="width: 100%;" name="detail" id="detail" frameborder="0"></iframe>' . PHP_EOL;
} else {
    $mainFrame = '            <iframe src="intro.php" style="width: 100%;" name="detail" id="detail" frameborder="0"></iframe>' . PHP_EOL;
}

if ($rtl) {
    echo $mainFrame;
    print $navFrame;
} else {
    print $navFrame;
    echo $mainFrame;
}

print '        </div>' . PHP_EOL;
print '    <body>' . PHP_EOL;

$misc->printFooter(false);
