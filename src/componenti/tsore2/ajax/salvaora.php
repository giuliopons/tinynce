<?php
//
// eliminazione di una persona da un job
//
$root="../../../../";
include($root."src/_include/config.php");
include("../_include/tsore.class.php");
$logger->addlog( str_replace("\n","",print_r($_GET,true)) );
$obj = new Ore();
$obj->salvaora((integer)$_GET['job'],(integer)$_GET['ute'],$_GET['data'],$_GET['value'],(integer)$_GET['tipoora']);
?>