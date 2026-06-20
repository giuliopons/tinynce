<?php
//
// restituisce la tabella dei job aggiornata
//

$root="../../../../";
include($root."src/_include/config.php");

include("../_include/tsore.class.php");
$obj = new Ore();
// check if date is in format YYYY-mm-dd
$date = $_GET["data"];
if (!preg_match("/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/",$date)) $date = date("Y-m-d");
if(isset($_GET['ver'])) $ver = $_GET['ver']; else $ver = "";
$userid = (integer)$_GET["utente"];


if( $ver == "") {
    $form = $obj->getFormClienteJob($date,$userid);
} else {
    $form = $obj->getFormClienteJobCompact($date,$userid);
}

echo translateHtml( $form);
?>