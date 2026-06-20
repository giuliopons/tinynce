<?php

$root="../../../../";
include($root."src/_include/config.php");
include("../_include/planning.class.php");
include($root."src/_include/formcampi.class.php");

$obj = new Planning();

$op = postget("op","");

$html = "";
if ($op=="get") { 
    // do the update/insert
    $html = $obj->getComment( $_REQUEST ); 
}

if ($op=="set") { 
    // do the update/insert
    $html = $obj->setComment( $_REQUEST ); 
}

echo $html;