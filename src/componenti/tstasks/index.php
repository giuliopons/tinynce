<?php
/*

	manage lists of to dos

*/


$root="../../../";
include($root."src/_include/config.php");
include($root."src/_include/grid.class.php");
include($root."src/_include/formcampi.class.php");
include($root."src/_include/crudbase.class.php");
include("_include/tasks.class.php");
include("../tslists/_include/lists.class.php");
include("_include/grid_callbacks.php");



// update position in html
$ambiente->setPosizione( "{Tasks}" );

$obj = new Tasks();
$obj->setAmbiente( $ambiente );	// bind the ambiente

$command = getpost("op", null);
$parameter = (int)getpost("id", 0);
$combotipo = setVariabile("combotipo",$obj->getDefaultListItem(),"ts_tasks");
$combotiporeset = get("combotiporeset","");
if ($combotiporeset == "reset") {
	setcookie("list_tasks", $combotipo, time() + 3600, "/");
}
$keyword = get("keyword","");
$title = getpost("title","");
if($title !="") {
	$command = "fastAggiungi";
}

switch ($command) {

    case "modifica":
		$obj->getDettaglio( $parameter, 0 );
		break;
	case "modificaStep2reload" :
	case "modificaStep2" :
		$obj->updateAndInsert($_POST,$_FILES);
		break;

	case "eliminaSelezionati":
		$obj->eliminaSelezionati($_POST);
		break;

	case "aggiungi":
		$obj->getDettaglio(0, $combotipo );
		break;
	case "aggiungiStep2reload":
	case "aggiungiStep2":
		$obj->updateAndInsert($_POST,$_FILES);
		break;
    case "toggleSelezionatiArchive":
        $obj->toggleArchive($_POST);
        break;		
    case "fastAggiungi" : 
		$obj->fastInsert($_GET);
		$obj->elenco($combotipo,$combotiporeset,$keyword);
		break;

	case null:
	default:
		$obj->elenco($combotipo,$combotiporeset,$keyword);
}

print translateHtml( $ambiente->loadAndParse() );