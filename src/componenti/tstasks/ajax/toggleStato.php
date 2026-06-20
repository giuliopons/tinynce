<?php
//
// change status of a task
//

$root="../../../../";
include($root."src/_include/config.php");
include($root."src/_include/crudbase.class.php");
include("../_include/tasks.class.php");
$obj = new Tasks();
$id = (integer)$_GET['id'];
$op = $_GET['op'];
if(!in_array($op,array('to do', 'done', 'in progress'))) die("errore");
$out = $obj->toggleStato($id,$op);
$ar = $obj->getToggleOptions($id);

echo translateHtml($ar[$op]);
?>