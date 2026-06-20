<?php
//
// salva la nota editata
//
$root="../../../../";
include($root."src/_include/config.php");
include("../_include/tsore.class.php");
// print_r($_GET);
// die;
// $logger->addlog( str_replace("\n","",print_r($_GET,true)) );
$obj = new Ore();
$obj->salvanota($_GET['ute'],$_GET['id'],$_GET['testo'],$_GET['tipoora']);
die("ok");
?>