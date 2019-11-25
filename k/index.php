<?php

header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Accept, Content-Type");
if($_SERVER['REQUEST_METHOD']=='OPTIONS') exit;
header('Content-Type: text/html; charset=UTF-8');
define('XMLCMS', true );
include "include/main.php"; 