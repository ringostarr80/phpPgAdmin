<?php
include 'adodb.inc.php';
include 'adodb-exceptions.inc.php';

$u = 'root';
$p = 'C0yote71';

$db = ADONewConnection('PDO');
var_dump( $db->connect('mysql:dbname=bugtracker', $u, $p) );
$db->debug = true;

