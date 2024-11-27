<?php

global  $webUrl,
        $SERVER,
        $SUPER_USER_NAME,
        $SUPER_USER_PASSWORD,
        $POWER_USER_NAME,
        $POWER_USER_PASSWORD,
        $NORMAL_USER_NAME,
        $NORMAL_USER_PASSWORD;

$webUrl = "http://{$_SERVER['HTTP_HOST']}/" . dirname($_SERVER['PHP_SELF']) . "/../..";
$SERVER = "{$conf['servers'][0]['host']}:{$conf['servers'][0]['port']}:{$conf['servers'][0]['sslmode']}";
$DATABASE = "ppatests";
$PHP_SIMPLETEST_HOME = dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'simpletest' . DIRECTORY_SEPARATOR . 'simpletest';

$SUPER_USER_NAME = 'ppatests_super';
$SUPER_USER_PASSWORD = 'super';

$POWER_USER_NAME = 'ppatests_power';
$POWER_USER_PASSWORD = 'power';

$NORMAL_USER_NAME = 'ppatests_guest';
$NORMAL_USER_PASSWORD = 'guest';
