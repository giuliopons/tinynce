<?php
/*

	manage lists of to dos

*/


$root="../../../";
include($root."src/_include/config.php");
include($root."src/_include/grid.class.php");
include($root."src/_include/formcampi.class.php");
include("_include/lists.class.php");
include("_include/grid_callbacks.php");


function valore_cliente($v) {
	return ($v > 0 ? number_format($v,2).MONEY : "");
}

// update position in html
print $ambiente->setPosizione( "{Lists}" );

$obj = new Lists();

$html="";

// if (isset($_GET["op"])) {
// 	$command = $_GET["op"];
// 	if (isset($_GET["id"])) $parameter = $_GET["id"]; else $parameter="";
// } else if (isset($_POST["op"])) {
// 	$command = $_POST["op"];
// 	if (isset($_POST["id"]))	$parameter = $_POST["id"]; else $parameter="";
// }

// $combotipo = setVariabile("combotipo","","ts_lists");

// if (isset($_GET["combotiporeset"])) {
// 	$combotiporeset = $_GET["combotiporeset"];
// } else $combotiporeset="";

// if (isset($_GET["keyword"])) {
// 	$keyword= $_GET["keyword"];
// } else $keyword="";

$command = getVar("op",["",["modifica","modificaStep2","modificaStep2reload","aggiungi","aggiungiStep2","aggiungiStep2reload","eliminaSelezionati"]]);
$parameter = (int)getVar("id");
$combotipo = getVar("combotipo", [ "" ,["1","0","-999"], "ts_lists"]);
$combotiporeset = getVar("combotiporeset", [ "" , null, "ts_lists"]);
$keyword = getVar("keyword", ["",null, "ts_lists"]);
$title = getVar("title", [ "" , null, "ts_lists"]);



if (isset($command)) {

	switch ($command) {
	case "modifica":
		$risultato = $obj->getDettaglio( $parameter, $command );
		if ($risultato=="0") {
			$html = returnmsg("{You're not authorized.}","jsback");
		} else $html = $risultato;
		break;
	case "modificaStep2reload" :
	case "modificaStep2" :
		$risultato = $obj->updateAndInsert($_POST,$_FILES);
		if ($risultato=="0") {
			$html = returnmsg("{You're not authorized.}","jsback");
		} elseif(str_replace(strstr($risultato,"|"),"",$risultato)=="-1") {
			$html = returnmsg(str_replace("|","",strstr($risultato,"|")),"jsback");
		} else {
			if ($command != "modificaStep2reload") $html = returnmsgok("{Done.}","reload");
				else $html = returnmsgok("{Done.}","load ".$_SERVER['SCRIPT_NAME']."?op=modifica&id={$parameter}");
		}
		break;
	case "eliminaSelezionati":
		$risultato = $obj->eliminaSelezionati($_POST);
		if ($risultato=="0") {
			$html = returnmsg("{You're not authorized.}","jsback");
		} elseif($risultato=="-2") {
			$html = returnmsg("{You can't delete a client with banners.}","jsback");
		} else $html = returnmsgok("{Deleted.}","load ".$_SERVER['SCRIPT_NAME']."");
		break;
	case "aggiungi":
		$risultato = $obj->getDettaglio();
		if ($risultato=="0") {
			$html = returnmsg("{You're not authorized.}","jsback");
		} else $html = $risultato;
		break;
	case "aggiungiStep2reload":
	case "aggiungiStep2":
		$risultato = $obj->updateAndInsert($_POST,$_FILES);
		if ($risultato=="0") {
			$html = returnmsg("{You're not authorized.}","jsback");
		} elseif(str_replace(strstr($risultato,"|"),"",$risultato)=="-1") {
			$html = returnmsg(str_replace("|","",strstr($risultato,"|")),"jsback");
		} else {
			$id = str_replace( "|","",stristr( $risultato, "|")) ; 
			if ($command != "aggiungiStep2reload") $html = returnmsgok("{Done.}","reload");
				else $html = returnmsgok("{Done.}","load ".$_SERVER['SCRIPT_NAME']."?op=modifica&id=".$id."");
		}
	}

}

if ($html=="") {
	$html = loadTemplateAndParse ("template/elenco.html");
	$html = str_replace("##corpo##", ($obj->elenco($combotipo,$combotiporeset,$keyword)), $html);
	$html = str_replace("##keyword##", $keyword, $html);
	$html = str_replace("##bottoni1##","<a href=\"$obj->linkaggiungi\" title=\"{Add new item}\" class='aggiungi'></a>", $html);
	$html = str_replace("##bottoni2##","<a href=\"$obj->linkeliminamarcate\" title=\"{Delete selected items}\" class='elimina'></a>", $html);
	$html = str_replace("##combotipo##", $obj->getHtmlcombotipo($combotipo), $html);
}


print translateHtml($html);

?>