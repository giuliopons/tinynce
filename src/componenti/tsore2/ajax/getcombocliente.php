<?php
//
// restituisce la combo clienti
//
$root="../../../../";
include($root."src/_include/config.php");
include("../_include/tsore.class.php");
$obj = new Ore();
echo translateHtml($obj->getComboCliente($_GET['data'],$_GET["utente"]));
?>