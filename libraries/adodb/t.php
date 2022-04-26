<?php
include 'adodb.inc.php';
//include 'adodb-exceptions.inc.php';

$driver = 'mysqli';
$host = 'localhost';
$user = 'root';
$password = 'C0yote71';
$database = 'bugtracker';

$db = NewADOConnection($driver);

$db->memCache = true;
$db->memCacheHost = array(
	array('host'=>'192.168.86.91','port'=>'11212','weight'=>70),
    array('host'=>'192.168.86.92','weight'=>30),
    );
//$db->memCacheHost = array('192.168.86.91', '192.168.86.92');
//$db->memCacheHost = [];

$db->memCachePort = 11211;
$db->memCacheCompress = false;
$db->Connect($host,$user,$password,$database);

$sql = 'SELECT code,description FROM xref_table';
$db->cacheExecute(2400,$sql);

