<?php
//
// delete of an hour record
//
$root="../../../../";
include($root."src/_include/config.php");
include("../_include/tsore.class.php");
$obj = new Ore();
$idOra = isset($_POST['idora']) ? (integer)$_POST['idora'] : 0;
$obj->deleteOra($idOra);
echo "ok";
?>