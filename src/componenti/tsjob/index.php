<?php

//gestione utenti component
$root="../../../";
include($root."src/_include/config.php");
include($root."src/_include/grid.class.php");
include($root."src/_include/formcampi.class.php");
include("_include/tsjob.class.php");
include("_include/grid_callbacks.php");


//::aggiorno posizione::
print $ambiente->setPosizione("{Jobs}");

$obj = new Job();

$html="";

$command = postget("op");
$parameter = postget("id");


$keyword =setVariabile("keyword","","jobs");
$combocliente =setVariabile("combocliente","","jobs");
$comboanno =setVariabile("comboanno",$obj->defaultcomboanno,"jobs");
$combostato =setVariabile("combostato",$obj->defaultcombostato,"jobs");

//esegue eventuali comandi passati
if (isset($command)) {

	switch ($command) {
	/*
	case "associa":
		$risultato = $obj->getDettaglioAssociazioniJobUtenti( $parameter );
		if ($risultato=="0") {
			$html = returnmsg("{You're not authorized.}","jsback");
		} else $html = $risultato;
		break;
	case "associaStep2":
		$risultato = $obj->insertAssociazione($_POST);
		if ($risultato=="0") {
			$html = returnmsg("{You're not authorized.}","jsback");
		} else $html = returnmsgok("Il record &egrave; stato inserito.","load index.php?op=associa&id=".$parameter);
		break;
	*/
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
	// case "elimina":
	// 	$risultato = $obj->deleteItem($parameter);
	// 	if ($risultato=="0") {
	// 		$html = returnmsg("{You're not authorized.}","jsback");
	// 	} else $html = returnmsgok("{Done.}","load index.php");
	// 	break;
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
	$html = str_replace("##bottoni2##","<a href=\"$obj->linkeliminamarcate\" class='elimina' title=\"".$obj->linkeliminamarcate_label."\"></a>", $html);


	$mostralabel = true;

	if(isset($_REQUEST['combocliente']) && $_REQUEST['combocliente'] !=-999) {
		$mostralabel = false;
	}
	$html = str_replace("##keyword##", $keyword, $html);
	$html = str_replace("##combocliente##", $obj->getHtmlComboClienti($combocliente, $mostralabel), $html);
	$html = str_replace("##comboanno##", $obj->getHtmlComboAnni($comboanno, $mostralabel), $html);
	$html = str_replace("##combostati##", $obj->getHtmlComboStati($combostato, $mostralabel), $html);
	$html = str_replace("##cercabox##", $obj->getHtmlCercaBox($keyword, $mostralabel), $html);

}


print translateHtml($html);

?>