<?php

//gestione ricavi component
$root="../../../";
include($root."src/_include/config.php");
include($root."src/_include/grid.class.php");
include($root."src/_include/formcampi.class.php");
include("_include/tsricavi.class.php");

function numero($s) {
	return number_format((float)$s,2,',','.').MONEY;
}

//::aggiorno posizione::
print $ambiente->setPosizione("{Revenues}");

$obj = new Ricavi();

$html="";

$command = postget("op");
$parameter = postget("id");
$keyword = postget("keyword");

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
		} elseif($risultato=="-2") {
			$html = returnmsg("{Something went wrong.}","jsback");
		} else $html = returnmsgok("{Deleted.}","load ".$_SERVER['SCRIPT_NAME']."");
		break;

	}

}
if ($html=="") {

	$html = loadTemplateAndParse ("template/elenco.html");
	$html = str_replace("##corpo##", $obj->elenco($_POST+$_GET), $html);
	$html = str_replace("##bottoni1##","<a href=\"$obj->linkaggiungi\" title=\"".$obj->linkaggiungi_label."\" class='aggiungi'></a>", $html);
	$html = str_replace("##bottoni2##","<a href=\"$obj->linkeliminamarcate\" title=\"{Delete selected items}\" class='elimina'></a>", $html);

	$html = str_replace("##keyword##", $keyword, $html);

}


print translateHtml($html);

?>
