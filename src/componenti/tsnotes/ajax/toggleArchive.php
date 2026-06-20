<?php
//
// change archive flag on a note
//

$root="../../../../";
include($root."src/_include/config.php");
include($root."src/_include/crudbase.class.php");
include("../_include/notes.class.php");
$obj = new Notes();
$ar = isset($_GET['ids']) ? explode(",", $_GET['ids']) : array();
$out = $obj->toggleArchive($ar);

echo $out;
