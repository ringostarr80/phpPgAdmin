#!/usr/bin/env php
<?php

require_once '../../conf/config.inc.php';
require_once '../../lang/english.php';

require_once 'config.tests.php';

set_include_path($PHP_SIMPLETEST_HOME . ':' . './testcase' . ':' . get_include_path());

require_once 'testcase/testphpPgAdminMain.php';
