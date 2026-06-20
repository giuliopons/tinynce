<?php
//
// change archive flag
//

$root="../../../../";
include($root."src/_include/config.php");
include($root."src/_include/crudbase.class.php");
include("../_include/tasks.class.php");
$obj = new Tasks();
$ar = isset($_GET['ids']) ? explode("," , $_GET['ids']) : array();
$out = $obj->toggleArchive($ar);

echo $out;