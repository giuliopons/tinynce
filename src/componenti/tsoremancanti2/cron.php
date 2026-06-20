<?php
$root="../../";
$FORCELOGIN = true; // per usare script framework anche senza loggamento
include($root."_include/config.php");

if (!Connessione()) trigger_error($conn->error); else CollateConnessione();

$sql = "SELECT * 
	FROM  `".DB_PREFIX."frw_utenti` 
	LEFT OUTER JOIN ".DB_PREFIX."frw_extrauserdata ON cd_user = id
	WHERE
	fl_attivo=1
	";

$debug = true;	// se true manda mail a me, se no funziona davvero.

$data = date("Y-m-d");
$oggi = $data;
$ieri = date("Y-m-d", strtotime("-1 day",strtotime($data)) );
$days = array("domenica","lunedi","martedi","mercoledi","giovedi","venerdi","sabato");
$giorno = $days[date("w",strtotime($data))];

echo "<pre>";
echo "OGGI: ".$giorno." ".$oggi. ", IERI: ".$ieri."<hr>";

$rs = $conn->query($sql);
while($r = $rs->fetch_array()) {
	
	echo $r['nome']." ".$r['cognome']."<br>";
	echo "Ore settimanali: ".$r['nu_oresettimanali']."<br>";
	$ore_giornaliere = (((integer)$r['nu_oresettimanali']) / 5 );
	if( $ore_giornaliere == 8 || $ore_giornaliere == 5) {
		echo "Ore giornaliere: ". $ore_giornaliere."<br>";
		// cerco ore fatte ieri
		$q = (integer)execute_scalar("SELECT sum(nu_ore) FROM `".DB_PREFIX."ts_ore` WHERE cd_utente = '".$r['id']."' and dt_giorno='".$ieri."'");
		echo "Ore fatte ieri: ".$q." ";
		if($q < $ore_giornaliere && $giorno != "lunedi" && $giorno != "domenica") {

			echo " <span style='background-color:red;color:#fff'>EMAIL</span> ";
			if(trim($r['de_email'])!="") {
				echo $r['de_email'];
				
				if($debug) $to = "pons@rockit.it"; else $to = $r['de_email'];

				mail( $to,"Timesheet non aggiornato ".date("d/m/Y", strtotime($ieri)),
					"Ciao,\n".
					"sono Timy il tuo amico/nemico del timesheet. Ieri (".$days[date("w", strtotime($ieri))].") avresti dovuto compilare le tue $ore_giornaliere ore, ma ne ho trovate ".$q.".\n".
					"Clicca sul link qua sotto e compila le ore mancanti.\n".
					"http://".$_SERVER['HTTP_HOST'].PONSDIR."\n\nCiao!\nTimy","From:timy-robot@". preg_replace("/^\./","",stristr($_SERVER['HTTP_HOST'],".")));
				
				if($debug) die("<hr>Debug mode ON, mi fermo qui.");

			}
		}
		echo "<br>";

	} else {
		// cerco ore fatte nei sette giorni prima
		echo "Ore giornaliere: n.d.<br>";
		if($giorno == "lunedi") {
			$lunediscorso = date("Y-m-d", strtotime("-7 days",strtotime($oggi)));
			echo "Lunedi scorso: ".$lunediscorso."<br>";
			$q = (integer)execute_scalar("SELECT sum(nu_ore) FROM `".DB_PREFIX."ts_ore` WHERE cd_utente = '".$r['id']."' and dt_giorno<='".$ieri."' and dt_giorno>='".$lunediscorso."'");
			echo "Ore fatte nella settimana precedente: ".$q." ";
			if($q < (integer)$r['nu_oresettimanali']) {

				echo " <span style='background-color:red;color:#fff'>EMAIL</span> ";
				if(trim($r['de_email'])!="") {
					echo $r['de_email'];
					
					if($debug) $to = "pons@rockit.it"; else $to = $r['de_email'];
					
					mail(
						$to,
						"Timesheet non aggiornato dal ". date("d/m/Y", strtotime($lunediscorso)) . " al ".date("d/m/Y", strtotime($ieri)),
						"Ciao,\n".
						"sono Timy il tuo amico/nemico del timesheet. Ho notato che non hai inserito tutte le ore settimanali previste nella settimana che va da lunedì ".date("d/m/Y", strtotime($lunediscorso))." a ieri domenica ".date("d/m/Y", strtotime($ieri)).". Avresti dovuto inserire ".$r['nu_oresettimanali']." ore, ma ne ho trovate ".$q.".\n".
						"Clicca sul link qua sotto e compila le ore mancanti.\n".
						"http://".$_SERVER['HTTP_HOST'].PONSDIR."\n\nCiao!\nTimy",
						"From:timy-robot@". preg_replace("/^\./","",stristr($_SERVER['HTTP_HOST'],".")));

					if($debug) die("<hr>Debug mode ON, mi fermo qui.");

					
				}
			}
		}
	}

	echo "<hr>";

}
echo "</pre>";
?>