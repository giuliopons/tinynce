<?php
/*
	This component allow the user to see the list of his work
*/
$root="../../../";
include($root."src/_include/config.php");
include($root."src/_include/grid.class.php");
include($root."src/_include/formcampi.class.php");
include("_include/tschehofatto.class.php");

//::aggiorno posizione::
print $ambiente->setPosizione("{My log time}");

$obj = new myOwnTimesheet();

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


	$html = $obj->getPannello($_POST);

}


print translateHtml($html);

?>