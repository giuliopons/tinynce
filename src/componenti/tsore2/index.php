<?php
/*
	manages user hours in the timesheet
*/


$root="../../../";
include($root."src/_include/config.php");
include("_include/tsore.class.php");


//::aggiorno posizione::
print $ambiente->setPosizione("{Timesheet}");

$obj = new Ore();

$html="";

$command = postget("op");
$parameter = postget("id");
$data = postget("data",date("Y-m-d"));

if($command =="" ) {
	$command = isset($_COOKIE['mode']) ? $_COOKIE['mode'] : "";
}
if($command=="calendar") {
	setcookie("mode","calendar",time()+3600,"/");
} 
if($command=="chat") {
	setcookie("mode","chat",time()+3600,"/");
}

//esegue eventuali comandi passati
if (isset($command)) {

	switch ($command) {
		case 'chat' : $html = $obj->chat($data);
			break;
	}

}
if ($html=="") {

	$html = loadTemplateAndParse ("template/addcal.html");
	if(OPENAI_API_KEY!="") {
		$html = str_replace("##OPENAI##", 'yes', $html);
	}
	$html = str_replace("##corpo##", $obj->getcal($data,$session->get("idutente")), $html);
	$html = str_replace("##settimana##", $obj->elenco($data,$session->get("idutente")), $html);
	$html = str_replace("##idutente##", $session->get("idutente"), $html);
	$html = str_replace("#ORE#", $obj->getDayOre(), $html);
}


print translateHtml($html);
?>