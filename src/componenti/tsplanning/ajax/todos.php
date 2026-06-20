<?php

$root="../../../../";
include($root."src/_include/config.php");
include("../_include/planning.class.php");
include($root."src/_include/formcampi.class.php");

$obj = new Planning();

$op = postget("op","getform");

$html = "";
if ($op=="delete") { 
    // do the update/insert
    $html = $obj->deleteTodo( $_REQUEST ); 
}

if ($op=="add") { 
    // do the update/insert
    $html = $obj->updateAndInsertTodo( $_REQUEST ); 
}

if($op == "getform") {
    // get the form
    $html = $obj->getDettaglioAddTodo( $_REQUEST );
}

echo translateHtml ( $html);