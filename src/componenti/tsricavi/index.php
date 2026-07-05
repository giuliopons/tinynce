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

//filtri elenco (GET > POST > SESSION > default), persistiti con base sessione "ricavi"
$keyword  = setVariabile("keyword","","ricavi");
$dal      = setVariabile("dal", date("Y")."-01-01", "ricavi");
$al       = setVariabile("al",  date("Y-m-d"),       "ricavi");
$cliente  = setVariabile("combocliente","","ricavi");
$job      = setVariabile("combojob","","ricavi");
$filtri   = array("keyword"=>$keyword,"dal"=>$dal,"al"=>$al,"combocliente"=>$cliente,"combojob"=>$job);

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
	$html = str_replace("##corpo##", $obj->elenco($_POST+$_GET+$filtri), $html);
	$html = str_replace("##bottoni1##","<a href=\"$obj->linkaggiungi\" title=\"".$obj->linkaggiungi_label."\" class='aggiungi'></a>", $html);
	$html = str_replace("##bottoni2##","<a href=\"$obj->linkeliminamarcate\" title=\"{Delete selected items}\" class='elimina'></a>", $html);

	$html = str_replace("##keyword##", $keyword, $html);
	$html = str_replace("##combocliente##", $obj->getHtmlComboClienti($cliente), $html);
	$html = str_replace("##combojob##",     $obj->getHtmlComboJob($job,$cliente), $html);
	$dalF = new data("dal",$dal,"aaaa-mm-gg","filtri");
	$alF  = new data("al", $al, "aaaa-mm-gg","filtri");
	$html = str_replace("##dal##", $dalF->gettag(), $html);
	$html = str_replace("##al##",  $alF->gettag(),  $html);

}


print translateHtml($html);

?>
