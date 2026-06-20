<?php
//
// salva ore e note
//
$root="../../../../";
include($root."src/_include/config.php");
include("../_include/tsore.class.php");
$logger->addlog( str_replace("\n","",print_r($_GET,true)) );

$force = isset($_GET['forceinsert']) ? true : false;

$date = $_GET["data"];
if (!preg_match("/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/",$date)) $date = date("Y-m-d");

$ute = (integer)$_GET['utente'];

$obj = new Ore();
$obj->salvaoranote((integer)$_GET['job'],$ute,$date,$_GET['ore'],$_GET['note'],(integer)$_GET['tipoora'],$force);
// echo translateHtml($obj->getCompiledHours($date,$ute))
echo "ok";
?>