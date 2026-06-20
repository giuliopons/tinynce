<?php
//
// keep session alive
//
$root="../../../../";
include($root."src/_include/config.php");
include("../_include/tsore.class.php");
echo $session->get("idutente");
?>