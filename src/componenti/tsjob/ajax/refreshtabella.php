<?
//
// restituisce la tabella dei job aggiornata
//
$root="../../../";
include($root."_include/config.php");
include("../_include/tsjob.class.php");
if (!Connessione()) trigger_error($conn->error);
$obj = new Job();
echo $obj->getTabellaJob($_GET['idjob']);
?>