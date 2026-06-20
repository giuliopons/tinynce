<?php
header('Content-Type: application/json');
$root="../../../../";
include($root."src/_include/config.php");
include("../_include/planning.class.php");
include($root."src/_include/formcampi.class.php");

if(!isset($_REQUEST["id_cliente"])) { $_REQUEST["id_cliente"] = "0"; }

$_REQUEST["id_cliente"] = intval($_REQUEST["id_cliente"]);

$obj = new Planning();

echo translateHtml ($obj->getJobList($_REQUEST["id_cliente"]));