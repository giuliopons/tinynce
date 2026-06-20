<?php
/*
	Handles the types of the hours
*/

$root="../../../";
include($root."src/_include/config.php");
include($root."src/_include/grid.class.php");
include($root."src/_include/formcampi.class.php");
include("_include/tstipiora.class.php");

//::aggiorno posizione::
print $ambiente->setPosizione("{Types of working time}");

$obj = new Tipiora();

$html="";

$command = postget("op");
$parameter = postget("id");
$keyword = postget("keyword");
$comboreparti = postget("comboreparti");

//esegue eventuali comandi passati
if (isset($command)) {

	switch ($command) {
	case "modifica":
		$risultato = $obj->getDettaglio( $parameter );
		if ($risultato=="0") {
			$html = returnmsg("{You're not authorized.}","jsback");
		} else $html = $risultato;
		break;
	case "modificaStep2":
		$risultato = $obj->updateAndInsert($_POST,$_FILES);
		if ($risultato=="0") {
			$html = returnmsg("{You're not authorized.}","jsback");
		} else $html = returnmsgok("{Done.}","load index.php");
		break;
	case "elimina":
		$risultato = $obj->deleteItem($parameter);
		if ($risultato=="0") {
			$html = returnmsg("{You're not authorized.}","jsback");
		} else $html = returnmsgok("{Done.}","load index.php");
		break;
	case "aggiungi":
		$risultato = $obj->getDettaglio();
		if ($risultato=="0") {
			$html = returnmsg("{You're not authorized.}","jsback");
		} else $html = $risultato;
		break;
	case "aggiungiStep2":
		$risultato = $obj->updateAndInsert($_POST);
		if ($risultato=="0") {
			$html = returnmsg("{You're not authorized.}","jsback");
		} else $html = returnmsgok("{Done.}","load index.php");
		break;
	case "eliminaSelezionati":
		$risultato = $obj->eliminaSelezionati($_POST);
		if ($risultato=="0") {
			$html = returnmsg("{You're not authorized.}","jsback");
		} else $html = returnmsgok("{Done.}","load index.php");
		break;

	}

}
if ($html=="") {

	$html = loadTemplateAndParse ("template/elenco.html");
	$html = str_replace("##corpo##", $obj->elenco($_POST+$_GET), $html);
	$html = str_replace("##bottoni1##","<a href=\"$obj->linkaggiungi\" class='aggiungi' title=\"".$obj->linkaggiungi_label."\"></a>", $html);
	$html = str_replace("##bottoni2##","<a href=\"$obj->linkeliminamarcate\" title=\"".$obj->linkeliminamarcate_label."\" class='elimina'></a>", $html);
	$html = str_replace("##bottoni3##","", $html);

	$html = str_replace("##combotipiora##", $obj->getHtmlComboReparti($comboreparti), $html);
	$html = str_replace("##keyword##", $keyword, $html);

}


print translateHtml($html);

?>