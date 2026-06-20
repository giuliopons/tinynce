<?php

//gestione utenti component
$root="../../../";
include($root."src/_include/config.php");
include($root."src/_include/grid.class.php");
include($root."src/_include/formcampi.class.php");

include("_include/tsreparti.class.php");

//::aggiorno posizione::
print $ambiente->setPosizione("{Departments}");

$obj = new Reparti();

$html="";

$command = postget("op");
$parameter = postget("id");
$keyword = postget("keyword");
$combocliente = postget("comboreparti");

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
	$html = str_replace("##bottoni2##","", $html);
	$html = str_replace("##bottoni3##","", $html);

	// $html = str_replace("##comboreparti##", $obj->getHtmlComboReparti($combocliente), $html);
	// $html = str_replace("##cercabox##", $obj->getHtmlCercaBox($keyword), $html);

}


print translateHtml($html);

?>