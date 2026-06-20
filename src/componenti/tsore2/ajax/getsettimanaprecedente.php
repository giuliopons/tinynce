<?php
//
// restituisce la tabella della settimana coi job
//
$root="../../../../";
include($root."src/_include/config.php");
include("../_include/tsore.class.php");
$obj = new Ore();
echo translateHtml($obj->caricasettimanaprecedente($_GET['data'],$_GET['utente']));
?>