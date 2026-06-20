<?php

$root="../../../";
include($root."src/_include/config.php");
include($root."src/_include/grid.class.php");
include($root."src/_include/formcampi.class.php");
include("_include/tsoremancanti2.class.php");


//::aggiorno posizione::
print $ambiente->setPosizione("{Missing log time}");

$obj = new Oremancanti();

$html="";

$command = postget("op");

//esegue eventuali comandi passati
if (isset($command)) {

	switch ($command) {
	case "cerca":
		$risultato = $obj->getPannello($_POST);
		if ($risultato=="0") {
			$html = returnmsg("Non sei autorizzato.","jsback");
		} else $html = $risultato;
		break;

	}

}
if ($html=="") {


	$html = $obj->getPannello($_POST);

}


print translateHtml($html);

?>