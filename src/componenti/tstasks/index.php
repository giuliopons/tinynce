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

$command = getVar("op",["",["modifica","modificaStep2","modificaStep2reload","aggiungi","aggiungiStep2","aggiungiStep2reload","eliminaSelezionati","toggleSelezionatiArchive","fastAggiungi"]]);
$parameter = (int)getVar("id");
$combotipo = (int)getVar("combotipo", [ $obj->getDefaultListItem() , null, $obj->tbdb]);
$keyword = getVar("keyword", ["",null, $obj->tbdb]);
$title = getVar("title", [ "" , null, $obj->tbdb]);
$combofiltrofuturo = getVar("combofiltrofuturo", [ "1" , ["0","1"], $obj->tbdb]);

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
		$obj->getDettaglio(0, (int)$combotipo );
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
		$obj->elenco([
			'combotipo' => $combotipo,
			'keyword' => $keyword,
			'combofiltrofuturo' => $combofiltrofuturo
		]);
		break;

	case null:
	default:

		$obj->elenco( [
			'combotipo' => $combotipo . ( stristr( ($_GET['combotipo'] ?? ""), "_archive" ) ? "_archive" : "" ),
			'keyword' => $keyword,
			'combofiltrofuturo' => $combofiltrofuturo
		]);
}

print translateHtml( $ambiente->loadAndParse() );