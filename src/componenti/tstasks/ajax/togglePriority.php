<?php
//
// change priority of a task
//

$root="../../../../";
include($root."src/_include/config.php");
include($root."src/_include/crudbase.class.php");
include("../_include/tasks.class.php");
$obj = new Tasks();
$id = (integer)$_GET['id'];
$op = (integer)$_GET['op'];
if(!in_array($op,array(0,1))) die("errore");
$out = $obj->togglePriority($id,$op);
$ar = $obj->getTogglePriorityOptions($id);

echo translateHtml($ar[$op]);
