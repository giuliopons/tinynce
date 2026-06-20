<?php
//
// restituisce la combo dei job dato un cliente
//
$root="../../../../";
include($root."src/_include/config.php");
include("../_include/tsore.class.php");
$obj = new Ore();
echo translateHtml($obj->getComboJob($_GET['data'],(integer)$_GET["utente"],(integer)$_GET["cliente"]));
?>