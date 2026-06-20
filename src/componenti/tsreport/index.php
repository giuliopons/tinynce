<?php
if(isset($_REQUEST['gruppo']) && $_REQUEST['gruppo']=='worked' && isset($_REQUEST['share'])) $public = true;
if(isset($_REQUEST['report'])) $public = true;

$root="../../../";
include($root."src/_include/config.php");
include($root."src/_include/grid.class.php");
include($root."src/_include/formcampi.class.php");
include("_include/tsreport.class.php");

//
// Handle worked report link redirect
//
if(isset($_REQUEST['report']) && strstr($_REQUEST['report'],".")) {
	$id_dt = (integer)explode(".",$_REQUEST['report'])[0];
	$dt = explode(".",$_REQUEST['report'])[1];
	$report = execute_row($sql = "select * from ".DB_PREFIX."ts_worked_reports where id_report = ".$id_dt." AND dt_saved = '".addslashes($dt)."'");
	if(isset($report['de_link'])) {
		header("Location: ".$report['de_link']);
		die;
	}
	header("Location: ".PONSDIR);
	die;
}


//::aggiorno posizione::
print $ambiente->setPosizione("{Reports}");

$obj = new Report();

$html="";

$command = postget("op");


if($public) {
	//
	// show shared report
	//
	if(isset($_REQUEST['check']) && isset($_REQUEST['dal']) && isset($_REQUEST['al'])
		&& isset($_REQUEST['cliente']) && isset($_REQUEST['job']) && isset($_REQUEST['persona'])
		&& isset($_REQUEST['gruppo'])
		&& isset($_REQUEST['reparto'])
	) {
		$check = md5(ENCRYPTIONKEY.$_REQUEST['dal'].$_REQUEST['al'].$_REQUEST['cliente'].$_REQUEST['job'].$_REQUEST['persona'].$_REQUEST['gruppo'].$_REQUEST['reparto']);
		if($check==$_REQUEST['check']) {
			$html = loadTemplateAndParse ("template/share.html");
			$html = str_replace("##corpo##", $obj->eseguiRicerca($_REQUEST, array("download_csv"=>false)) , $html); 
			echo translateHtml(  $html);
			die;
		}
	}
}

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