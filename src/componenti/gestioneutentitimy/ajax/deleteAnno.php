<?php
//
// delete a year cost

$root="../../../../";
include($root."src/_include/config.php");
include("../../gestioneutenti/_include/gestioneutenti.class.php");
include("../_include/TIMY.gestioneutenti.class.php");
$obj = new Timy_gestioneutenti("frw_utenti",40,"cognome","asc",0);
$anno = (integer)$_REQUEST['anno'];
$user = (integer)$_REQUEST['user'];
$out = $obj->deleteAnno($anno,$user);
echo $out;
?>