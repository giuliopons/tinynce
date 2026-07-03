<?php

$root="../../../";
include($root."src/_include/config.php");
include($root."src/_include/grid.class.php");
include($root."src/_include/formcampi.class.php");
include("_include/tsreport-costi-ricavi.class.php");


//::aggiorno posizione::
print $ambiente->setPosizione("{Reports}");

$obj = new ReportCostiRicavi();

$html="";

$command = postget("op");

//esegue eventuali comandi passati
if (isset($command)) {

	switch ($command) {
	case "cerca":
		$risultato = $obj->getPannello($_REQUEST);
		if ($risultato=="0") {
			$html = returnmsg("{You're not authorized.}","jsback");
		} else $html = $risultato;
		break;

	}

}
if ($html=="") {


	$html = $obj->getPannello($_REQUEST);

}


print translateHtml($html);

?>