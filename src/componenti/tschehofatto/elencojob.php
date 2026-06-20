<?php
//
// estrae le combo delle commesse
//
$root="../../../";
include($root."src/_include/config.php");

$html="";

if (isset($_GET["id"])) $id = $_GET["id"]; else $id="";
$id =preg_replace("/[^0-9]/","",$id);

$arCommesse[""]= "Tutti i job";

if($id) {

	//------------------------------------------------
	//options di combo commesse
	$sql = "select * from ".DB_PREFIX."ts_job where cd_cliente='{$id}' order by de_nomejob";
	$rs = $conn->query($sql) or trigger_error($conn->error);
	while($riga = $rs->fetch_array()) {
		$dalal = "";
		$d0 = strtotime(date("Y-m-d"));

		if($riga['dt_inizio'] != '0000-00-00') {
			$d1 = strtotime($riga['dt_inizio']);
			$dalal.="dal ".Todmy($riga['dt_inizio']);
			if($d0 < $d1) {
				$dalal .= " NON INIZIATA";
			}
		}
		if($riga['dt_fine'] != '0000-00-00') {
			$d2 = strtotime($riga['dt_fine']);
			if(!$dalal) { $dalal.="...";} else {$dalal .=" ";}
			$dalal.="al ".Todmy($riga['dt_fine']);
			if($d0 > $d2) {
				$dalal .= " SCADUTA";
			}
		}

		if($dalal!="") { $dalal = " ($dalal)";}

		$arCommesse[$riga['id_job']]=$riga['de_codice']." ".$riga['de_nomejob'].$dalal;
	}
	//------------------------------------------------
}
foreach ($arCommesse as $k => $v) {
	$html.="{$k},".htmlentities($v);
	$html.="|";
}

print translateHtml($html);

?>