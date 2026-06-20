<?php
//
// restituisce la tabella del calendario aggiornata
//

$root="../../../../";
include($root."src/_include/config.php");
include("../_include/tsore.class.php");

$obj = new Ore();
echo translateHtml($obj->getcal($_GET['data'],$_GET["utente"]));
?>