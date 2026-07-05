<?php
//
// dettaglio di una cella del report std (costi/ricavi/personale):
// elenca le voci che compongono l'importo, per il popup lato client.
//
$root="../../../";
include($root."src/_include/config.php");
include($root."src/_include/grid.class.php");
include($root."src/_include/formcampi.class.php");
include("_include/tsreport-costi-ricavi.class.php");

// il costruttore chiama checkAbilitazione("TSREPORTCR"): auth garantita
$obj = new ReportCostiRicavi();

$metric = isset($_GET["metric"]) ? $_GET["metric"] : "";
$job    = isset($_GET["job"])    ? $_GET["job"]    : "";
$ym     = isset($_GET["ym"])     ? $_GET["ym"]     : "";
$stato  = isset($_GET["stato"])  ? $_GET["stato"]  : "all";

$html = "";
switch($metric) {
	case "ric":
		$html = $obj->dettaglioImporti("ts_ricavi", $job, $ym, $stato);
		break;
	case "forn":
		$html = $obj->dettaglioImporti("ts_costi", $job, $ym, $stato);
		break;
	case "pers":
		$html = $obj->dettaglioPersonale($job, $ym);
		break;
}

print translateHtml($html);

?>