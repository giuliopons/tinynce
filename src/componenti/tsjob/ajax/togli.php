<?
//
// eliminazione di una persona da un job
//
$root="../../../";
include($root."_include/config.php");
include("../_include/tsjob.class.php");
if (!Connessione()) trigger_error($conn->error);
$obj = new Job();
$obj->deleteAssociazione($_GET['idjob'],$_GET['idutente']);
echo "ok";
?>