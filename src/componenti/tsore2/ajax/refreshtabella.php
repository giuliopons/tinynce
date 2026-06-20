<?php
//
// restituisce la tabella dei job aggiornata
//
$root="../../../../";
include($root."src/_include/config.php");
include("../_include/tsjob.class.php");
$obj = new Job();
echo translateHtml($obj->getTabellaJob($_GET['idjob']));
?>