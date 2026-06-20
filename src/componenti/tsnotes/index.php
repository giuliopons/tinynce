<?php
/*
	manage notes
*/

$root="../../../";
include($root."src/_include/config.php");
include($root."src/_include/grid.class.php");
include($root."src/_include/formcampi.class.php");
include($root."src/_include/crudbase.class.php");
include("_include/notes.class.php");
include("_include/grid_callbacks.php");

// update position in html
$ambiente->setPosizione( "{Notes}" );

$obj = new Notes();
$obj->setAmbiente( $ambiente );	// bind the ambiente

$command = getpost("op", null);
$parameter = (int)getpost("id", 0);
$keyword = get("keyword","");
$filtro = get("filtro","");

switch ($command) {

	case "modifica":
		$obj->getDettaglio( $parameter );
		break;
	case "modificaStep2":
		$obj->updateAndInsert($_POST);
		break;

	case "eliminaSelezionati":
		$obj->eliminaSelezionati($_POST);
		break;

	case "aggiungi":
		$obj->getDettaglio(0);
		break;
	case "aggiungiStep2":
		$obj->updateAndInsert($_POST);
		break;

	case "toggleSelezionatiArchive":
		$obj->toggleArchive($_POST['gridcheck'] ?? array());
		$obj->elenco($keyword, $filtro);
		break;

	case null:
	default:
		$obj->elenco($keyword, $filtro);
}

print translateHtml( $ambiente->loadAndParse() );
