<?php
//
// router AJAX per la modifica/inserimento/eliminazione di ricavi e costi dai popup del report.
//   op=getform  -> ritorna il frammento-form (dialog)          [GET]
//   op=save     -> insert/update del record (con storico)      [POST]
//   op=delete   -> eliminazione del record                     [POST]
// La persistenza riusa le classi Ricavi/Costi (che gestiscono anche lo storico).
//
$root="../../../";
include($root."src/_include/config.php");
include($root."src/_include/grid.class.php");
include($root."src/_include/formcampi.class.php");
include("_include/tsreport-costi-ricavi.class.php");

$op     = postget("op","getform");
$metric = postget("metric");

if($op=="getform") {
	// il costruttore controlla TSREPORTCR; getFormImporto controlla il profilo admin
	$obj = new ReportCostiRicavi();
	print translateHtml($obj->getFormImporto($metric, postget("id",""), postget("job",""), postget("ym","")));
	return;
}

// save / delete: consentito solo ad admin/superadmin
if(!in_array($session->get("idprofilo"), array(20,999999))) {
	print "ko|".translateHtml("{You're not authorized.}");
	return;
}

// istanzio la classe giusta per la metrica (riuso della persistenza + storico)
if($metric=="forn") {
	include($root."src/componenti/tscosti/_include/tscosti.class.php");
	$m = new Costi();
} else {
	include($root."src/componenti/tsricavi/_include/tsricavi.class.php");
	$m = new Ricavi();
}

if($op=="delete") {
	$res = $m->deleteItem(postget("id"));
} else {
	// insert vs update deciso internamente dalla presenza di $_POST["id"]; lo storico e' automatico
	$res = $m->updateAndInsert($_POST);
}

print ($res==="") ? "ok" : "ko|".translateHtml("{Operation failed}");

?>
