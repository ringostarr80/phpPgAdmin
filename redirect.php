<?php

declare(strict_types=1);

use PhpPgAdmin\Website\Redirect;

require_once __DIR__ . DIRECTORY_SEPARATOR . 'autoload.php';

$redirect = new Redirect();
$redirect->tryRedirect();

$subject = isset($_REQUEST['subject']) ? $_REQUEST['subject'] : 'root';

if ($subject == 'root') {
    $_no_db_connection = true;
}

include_once('./libraries/lib.inc.php');

$url = $misc->getLastTabURL($subject);

// Load query vars into superglobal arrays
if (isset($url['urlvars'])) {
    $urlvars = array();

    foreach ($url['urlvars'] as $k => $urlvar) {
        $urlvars[$k] = value($urlvar, $_REQUEST);
    }

    $_REQUEST = array_merge($_REQUEST, $urlvars);
    $_GET = array_merge($_GET, $urlvars);
}

require $url['url'];
