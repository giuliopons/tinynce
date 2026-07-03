<?php
/*
	gestione reportistica timesheet
*/

class ReportCostiRicavi {

	var $gestore;

	var $arStati;

	function __construct() {
		global $session,$root,$conn;
		$this->gestore = $_SERVER["PHP_SELF"];

		checkAbilitazione("TSREPORTCR","TSREPORTCR");


	}

    function removeDoubleQuotesFromArray($r) {
        return array_map(function($value) {
            return is_string($value) ? str_replace('"', '', $value) : $value;
        }, $r);
    }

	function getPannello($dati) {

		if(!isset($dati["job"])) {
            $dati["job"] = "-2";
        }
		if(!isset($dati["gruppo"])) {
            $dati["gruppo"] = "";
        }
        if(!isset($dati["dal"])) {
            $dati["dal"] = date("Y")."-01-01"; 
            //execute_scalar("SELECT min(dt_giorno) FROM `".DB_PREFIX."ts_ore` WHERE 1", date("Y-m-d"));
        }
		
		global $session,$root,$conn;
	
		$html = "";
		
		if ($session->get("TSREPORTCR")) {

			if(!isset($dati["cliente"]) && isset($dati["job"]) && $dati["job"]!="") {
				$dati["cliente"] = execute_scalar("select cd_cliente from ".DB_PREFIX."ts_job where id_job='".$dati["job"]."'");
			}


			//costruzione form
			$objform = new form();
			
			$datainizio = "";
			if($datainizio == "") {
				$datainizio = date("Y-m-d");
			}
			$giorno = date("w",strtotime($datainizio));

			if($giorno!=1) {
				//cerco il lunedi prima
				$lunedi = todayadd(1-$giorno);
			} else {
				$lunedi = $datainizio;
			}

			$valore = (isset($dati["dal"])?$dati["dal"]:"0000-00-00");
			if ($valore=="") $valore = $lunedi;
			$dal = new data("dal",$valore,"aaaa-mm-gg",$objform->name);
			$dal->obbligatorio=1;
			$dal->label="'{From}'";
			$objform->addControllo($dal);

			$valore = (isset($dati["al"])?$dati["al"]:"");
			if ($valore=="") $valore = date("Y-m-d");
			$al = new data("al",$valore,"aaaa-mm-gg",$objform->name);
			$al->obbligatorio=1;
			$al->label="'{To}'";
			$objform->addControllo($al);

			//------------------------------------------------
			//combo clienti
			$sql = "select id_cliente,de_nomecliente from ".DB_PREFIX."ts_clienti order by de_nomecliente";
			$rs = $conn->query($sql) or trigger_error($conn->error." ".$sql);
			$arClienti[""]="--{All}--";
			while($riga = $rs->fetch_array()) {
				$arClienti[$riga['id_cliente']]=$riga['de_nomecliente'];
			}
			//------------------------------------------------
			$cliente = new optionlist("cliente",((isset($dati["cliente"])?$dati["cliente"]:"")),$arClienti);
			$cliente->obbligatorio=0;
			$cliente->label="'{Client}'";
			$cliente->attributes=" onchange=\"loadjobs(this)\" class='filter'";
			$objform->addControllo($cliente);



			//------------------------------------------------
			//combo job
			$sql = "select id_job,de_nomejob,de_codice from ".DB_PREFIX."ts_job where cd_cliente='".(isset($dati["cliente"])?$dati["cliente"]:"")."' order by de_nomejob";
			$rs = $conn->query($sql) or trigger_error($conn->error." ".$sql);
			$arJob[""]="--{All}--";
			$arJob["-1"]="{All jobs OFF}";
			$arJob["-2"]="{All jobs ON}";
			while($riga = $rs->fetch_array()) {
				$arJob[$riga['id_job']]=$riga['de_codice']." ".$riga['de_nomejob'];
			}
			//------------------------------------------------
			$job = new optionlist("job",(isset($dati["job"])?$dati["job"]:""),$arJob);
			$job->obbligatorio=0;
            $job->attributes=" class='filter'";
			$job->label="'{Job}'";
			$objform->addControllo($job);

			$gruppo = new optionlist("gruppo",isset($dati["gruppo"])?$dati["gruppo"]:"",array(
				"std"=>"{Standard} (x mesi)",
				"cd_cliente"=>"{By client}",
				"cd_job"=>"{By job}",

			));
			$gruppo->obbligatorio=0;
			$gruppo->label="'Tipo di report'";
            $gruppo->attributes=" class='filter'";
			$objform->addControllo($gruppo);


			$op = new hidden("op","cerca");

			$html = loadTemplateAndParse ("template/elenco.html");

			$html = str_replace("##STARTFORM##", $objform->startform(), $html);
			$html = str_replace("##op##", $op->gettag(), $html);
			$html = str_replace("##cliente##", $cliente->gettag(), $html);
			$html = str_replace("##job##", $job->gettag(), $html);
			$html = str_replace("##gruppo##", $gruppo->gettag(), $html);
			$html = str_replace("##dal##", $dal->gettag(), $html);
			$html = str_replace("##al##", $al->gettag(), $html);
			$html = str_replace("##gestore##", $this->gestore, $html);
			$html = str_replace("##ENDFORM##", $objform->endform(), $html);

            

			if(isset($dati["op"]) && $dati["op"]=='cerca') {
				$html = str_replace("##corpo##", $this->eseguiRicerca($dati, array("download_csv"=>true)), $html);
			} else {
				$html = str_replace("##corpo##", "", $html);
			}


		} else {
			$html = "0";
		}
		return $html;
	}

	function eseguiRicerca($dati, $params = array()) {

		global $session,$conn;
		
		// $job= getVarSetting('JOB_NON_ATTRIBUIBILE');

		$report_print = "";

		$nomegruppo = "";
		if($dati['gruppo']=="cd_cliente") {
			//
			// visualization by client
			//
			$nomegruppo = "cliente";
			$sql="SELECT SUM(c.nu_ore) as ore, SUM(c.nu_ore/8) as giornate, e.de_nomecliente as cliente ,
				SUM(CASE WHEN AC.nu_cost IS NOT NULL 
					THEN AC.nu_cost*c.nu_ore
					ELSE h.nu_costo*c.nu_ore
				END) AS costo_personale
			FROM ".DB_PREFIX."frw_utenti b,".DB_PREFIX."ts_job d, ".DB_PREFIX."ts_clienti e,".DB_PREFIX."frw_extrauserdata h,".DB_PREFIX."ts_ore c
			LEFT OUTER JOIN ".DB_PREFIX."ts_users_annual_cost AC on AC.cd_user=c.cd_utente and AC.nu_anno=YEAR(c.dt_giorno)
			where c.cd_utente=b.id 
			and d.id_job=c.cd_job
			and d.cd_cliente=e.id_cliente #altriwhere# 
			and h.cd_user=b.id group by cd_cliente,de_nomecliente order by de_nomecliente";

			$altriwhere = "";

			if($dati['cliente']!='') {
				$altriwhere.=" and e.id_cliente = '{$dati['cliente']}'";
			}
			if($dati['job']=="-1") {
				$altriwhere.=" and d.fl_attivo='0' "; // JOB OFF
			} elseif($dati['job']=="-2") { 
				$altriwhere.=" and d.fl_attivo='1' "; // JOB ON
			} elseif($dati['job']=="") { 
				// ALL JOBS
			} else $altriwhere.=" and d.id_job='".$dati['job']."' "; // SPECIFIC JOB
			if($dati['dal']) {
				$altriwhere.=" and c.dt_giorno>='".$dati['dal']."' ";
			}
			if($dati['al']) {
				$altriwhere.=" and c.dt_giorno<='".$dati['al']."' ";
			}
			$sql = str_replace("#altriwhere#",$altriwhere,$sql);

			$rs = $conn->query($sql) or die($conn->error." SQL = ".$sql);
			$out = "";
			//id_cliente 	de_nomecliente 	id_job 	de_codice 	de_nomejob 	dt_inizio 	dt_fine 	cd_cliente 	id_ora 	cd_utente 	de_nota 	cd_job 	nu_ore 	dt_giorno 	id 	username 	password 	nome 	cognome 	fl_attivo 	cd_profilo

			//$job = "";
			$sommaore = 0;
			$sommacosto_personale = 0;
			$sommagiornate = 0;
			

			$header = "";
			$c = 0;
			$sommatutteore = 0;
				$out="<tr>";
				$out.="<th>{Client}</th>";
				$out.="<th class='n'>{Hours}</th>";
				$out.="<th class='n'>{Days}</th>";
				$out.="<th class='n'>{Cost}</th>";
				$out.="</tr>";

				$csv="";
				$csv.='"'."{Client}".'"'.";";
				$csv.='"'."{Hours}".'"'.";";
				$csv.='"'."{Days}".'"'.";";
				$csv.='"'."{Cost}".'"'.";";
				$csv.="\n";
				$csv = translateHtml($csv);
			while($r=$rs->fetch_array()) {

				$r = $this->removeDoubleQuotesFromArray($r);

				$out.="<tr>";
				$out.="<td>".$r['cliente']."</td>";
				$out.="<td class='n'>".numberf($r['ore'],1)."</td>";
				$out.="<td class='n'>".numberf($r['giornate'])."</td>";
				$out.="<td class='n'>".numberf($r['costo_personale'],0).MONEY."</td>";
				$out.="</tr>";

				$csv.='"'.$r['cliente'].'"'.";";
				$csv.='"'.numberf($r['ore'],1).'"'.";";
				$csv.='"'.numberf($r['giornate'],1).'"'.";";
				$csv.='"'.numberf($r['costo_personale'],2).'"'.";";
				$csv.="\n";
				
				
				$sommaore += $r['ore'];
				$sommagiornate += $r['giornate'];				
				$sommacosto_personale+= $r['costo_personale'];
				$c++;
			}
			if($c>0) {
				$out.="<tr>";
			
				$out.="<th class='n'>&nbsp;</th>";
				$out.="<th class='n'>".numberf($sommaore,1)."h "."</th>";
				$out.="<th class='n'>".numberf($sommagiornate,1)."g "."</th>";
				$out.="<th class='n'>".numberf($sommacosto_personale,0).MONEY."</th>";
				$out.="</tr>";

				$csv.=";";
				$csv.='"'.numberf($sommaore,1).'"'.";";
				$csv.='"'.numberf($sommagiornate,1).'"'.";";
				$csv.='"'.numberf($sommacosto_personale,0).'"'.";";
				$csv.="\n";
				$sommaore = 0;
				$sommagiorni = 0;
				$sommaeuri = 0;
			}

		} elseif($dati['gruppo']=="cd_job") {

			//
			// visualization by job
			//
			$nomegruppo = "commesse";
			$sql="SELECT id_job,sum(c.nu_ore) as ore,sum(c.nu_ore/8) as giornate, e.de_nomecliente as cliente  ,d.de_nomejob as commessa , d.de_codice,

				SUM(CASE WHEN AC.nu_cost IS NOT NULL 
					THEN AC.nu_cost*c.nu_ore
					ELSE h.nu_costo*c.nu_ore
				END) AS costo_personale

			FROM ".DB_PREFIX."frw_utenti b,".DB_PREFIX."ts_job d, ".DB_PREFIX."ts_clienti e,".DB_PREFIX."frw_extrauserdata h,".DB_PREFIX."ts_ore c
			LEFT OUTER JOIN ts_users_annual_cost AC on AC.cd_user=c.cd_utente and AC.nu_anno=YEAR(c.dt_giorno)
			where c.cd_utente=b.id 
			and d.id_job=c.cd_job
			and d.cd_cliente=e.id_cliente #altriwhere# 
			and h.cd_user=b.id group by cd_job,de_nomejob,de_nomecliente order by de_codice";
			$altriwhere = "";

			if($dati['cliente']!='') {
				$altriwhere.=" and e.id_cliente = '{$dati['cliente']}'";
			}
			if($dati['job']=="-1") {
				$altriwhere.=" and d.fl_attivo='0' "; // JOB OFF
			} elseif($dati['job']=="-2") { 
				$altriwhere.=" and d.fl_attivo='1' "; // JOB ON
			} elseif($dati['job']=="") { 
				// ALL JOBS
			} else $altriwhere.=" and d.id_job='".$dati['job']."' "; // SPECIFIC JOB
			

			if($dati['dal']) {
				$altriwhere.=" and c.dt_giorno>='".$dati['dal']."' ";
			}
			if($dati['al']) {
				$altriwhere.=" and c.dt_giorno<='".$dati['al']."' ";
			}
			$sql = str_replace("#altriwhere#",$altriwhere,$sql);

			$rs = $conn->query($sql) or trigger_error($conn->error." ".$sql);
			$out = "";
			//id_cliente 	de_nomecliente 	id_job 	de_codice 	de_nomejob 	dt_inizio 	dt_fine 	cd_cliente 	id_ora 	cd_utente 	de_nota 	cd_job 	nu_ore 	dt_giorno 	id 	username 	password 	nome 	cognome 	fl_attivo 	cd_profilo

			//$job = "";
			$sommaore = 0;
			$sommacosto_personale = 0;
			$sommagiornate = 0;

			$header = "";
			$c = 0;
			$sommatutteore = 0;
				$out="<tr>";
				$out.="<th>{Code}</th>";
				$out.="<th>{Client}</th>";
				$out.="<th>{Job}</th>";
				$out.="<th class='n'>{Hours}</th>";
				$out.="<th class='n'>{Days}</th>";
				$out.="<th class='n'>{Cost}</th>";
				$out.="</tr>";

				$csv="";
				$csv.='"'."{Code}".'"'.";";
				$csv.='"'."{Client}".'"'.";";
				$csv.='"'."{Job}".'"'.";";
				$csv.='"'."{Hours}".'"'.";";
				$csv.='"'."{Days}".'"'.";";
				$csv.='"'."{Cost}".'"'.";";
				$csv.="\n";
				$csv = translateHtml($csv);
			while($r=$rs->fetch_array()) {

				$r = $this->removeDoubleQuotesFromArray($r);

				$out.="<tr>";
				$out.="<td style='white-space:nowrap'>".$r['de_codice']."</td>";
				$out.="<td>".$r['cliente']."</td>";
				$out.="<td>".$r['commessa']."</td>";
				$out.="<td class='n'>".numberf($r['ore'],1)."</td>";
				$out.="<td class='n'>".numberf($r['giornate'],1)."</td>";
				$out.="<td class='n'>".numberf($r['costo_personale'],2).MONEY."</td>";
				$out.="</tr>";

				
				$csv.='"'.$r['de_codice'].'"'.";";
				$csv.='"'.$r['cliente'].'"'.";";
				$csv.='"'.$r['commessa'].'"'.";";
				$csv.='"'.numberf($r['ore'],1).'"'.";";
				$csv.='"'.numberf($r['giornate'],1).'"'.";";
				$csv.='"'.numberf($r['costo_personale'],2).'"'.";";
				$csv.="\n";
				
				

				$sommaore += $r['ore'];
				$sommagiornate += $r['giornate'];
				$sommacosto_personale+= $r['costo_personale'];
				$c++;
			}
			if($c>0) {
				$out.="<tr>";
			
				$out.="<th class='n' >&nbsp;</th>";
				$out.="<th class='n' >&nbsp;</th>";
				$out.="<th class='n' >&nbsp;</th>";
				$out.="<th class='n' >".numberf($sommaore,1)."h "."</th>";
				$out.="<th class='n' >".numberf($sommagiornate,1)."g "."</th>";
				$out.="<th class='n' >".numberf($sommacosto_personale,0).MONEY."</th>";
				$out.="</tr>";

				$csv.=";";
				$csv.=";";
				$csv.=";";
				$csv.='"'.numberf($sommaore,1).'"'.";";
				$csv.='"'.numberf($sommagiornate,1).'"'.";";
				$csv.='"'.numberf($sommacosto_personale,0).'"'.";";
				$csv.="\n";
				$sommaore = 0;
				$sommagiorni = 0;
				$sommaeuri = 0;
			}


		} elseif($dati['gruppo']=="std") {
			//
			// standard visualization
			//
			$nomegruppo = "std";
			$sql="SELECT DISTINCT d.id_job,e.de_nomecliente AS cliente, d.de_nomejob AS commessa, d.de_codice
			FROM ".DB_PREFIX."ts_job d
            INNER JOIN ".DB_PREFIX."ts_clienti e ON e.id_cliente=d.cd_cliente
            WHERE 1=1
			#altriwhere# 
			GROUP BY 
				d.id_job, 
				e.de_nomecliente, 
				d.de_nomejob, 
				d.de_codice 
			ORDER BY de_codice";
			$altriwhere = "";

			
			if($dati['cliente']!='') {
				$altriwhere.=" and d.cd_cliente = '{$dati['cliente']}'";
			}
			if($dati['job']=="-1") {
				$altriwhere.=" and d.fl_attivo='0' "; // JOB OFF
			} elseif($dati['job']=="-2") { 
				$altriwhere.=" and d.fl_attivo='1' "; // JOB ON
			} elseif($dati['job']=="") { 
				// ALL JOBS
			} else $altriwhere.=" and d.id_job='".$dati['job']."' "; // SPECIFIC JOB
			
			
			$sql = str_replace("#altriwhere#",$altriwhere,$sql);

            //echo $sql;//die;

			$rs = $conn->query($sql) or trigger_error($conn->error." ".$sql);
			$out = "";
			//id_cliente 	de_nomecliente 	id_job 	de_codice 	de_nomejob 	dt_inizio 	dt_fine 	cd_cliente 	id_ora 	cd_utente 	de_nota 	cd_job 	nu_ore 	dt_giorno 	id 	username 	password 	nome 	cognome 	fl_attivo 	cd_profilo

			//$job = "";
			// $sommaore = 0;
			$sommacosto_personale = 0;
			// $sommagiornate = 0;

			$header = "";
			$c = 0;
			// $sommatutteore = 0;

			$d2 = date_create( $dati['al']);
			$d1 = date_create( $dati['dal']);
			$totalMonths = date_diff($d1, $d2);
			$totalMonths = $totalMonths->format("%m");
			$monthCols = ""; $monthColsCSV="";
			$d0 = strtotime($dati['dal']);
			$year = date("Y", $d0);

			$fieldsAr = array();

			for ($i = 0; $i <= $totalMonths; $i++) {
				$dn  = 24*60*60* 31*$i + $d0;
				$label = date("F", $dn);
				if(date("m", $dn) == 1) $year = date("Y", $dn);
				$monthCols.="<th class='n'>{".$label."} ".$year."</th>";
				$monthColsCSV.='"'."{".$label."} ".$year.'"'.";";
				$year = "";
				$fieldsAr[date("Y", $dn)."-".date("m", $dn)] = "0";
			}

				$out="<tr>";
				$out.="<th>{Code}</th>";
				$out.="<th>{Client}</th>";
				$out.="<th>{Job}</th>";
				$out.=$monthCols;
				$out.="</tr>";

				$csv="";
				$csv.='"'."{Code}".'"'.";";
				$csv.='"'."{Client}".'"'.";";
				$csv.='"'."{Job}".'"'.";";
				$csv.=$monthColsCSV;
				$csv.="\n";
			while($r=$rs->fetch_array()) {

                // fare sub query per mese
                $r['ore'] = 0;
                $r['giornate'] = 0;
                $r['costo_personale'] = 0;

                for($i=1;$i<=1;$i++) {
                    $altriwhere = "";
                    $altrijoin = "";

                    if($dati['dal']) {
                        $altriwhere.=" and c.dt_giorno>='".$dati['dal']."' ";
                    }
                    if($dati['al']) {
                        $altriwhere.=" and c.dt_giorno<='".$dati['al']."' ";
                    }

                    $sql = "SELECT CONCAT(YEAR(c.dt_giorno),'-',LPAD(MONTH(c.dt_giorno),2,'00')) as m,
					SUM(CASE WHEN AC.nu_cost IS NOT NULL 
						THEN AC.nu_cost*c.nu_ore
						ELSE u.nu_costo*c.nu_ore
					END) AS costo_personale

                        FROM ".DB_PREFIX."ts_ore c 
                        inner join ".DB_PREFIX."frw_extrauserdata u on u.cd_user = c.cd_utente
                        ".
                        $altrijoin.
                        " 
						LEFT OUTER JOIN ts_users_annual_cost AC on AC.cd_user=c.cd_utente and AC.nu_anno=YEAR(c.dt_giorno)

						WHERE cd_job = '{$r['id_job']}' " . 
                        $altriwhere.
                        " group by m";

                    $rsm = $conn->query($sql) or die($conn->error);
					foreach ($fieldsAr as $k=>$v) $fieldsAr[$k] = 0;
                    while($rm = $rsm->fetch_array()) {
                        $r['costo_personale'] += $rm['costo_personale'];
						$fieldsAr[$rm['m']] = $rm['costo_personale'];
						
                    }

                }

                

				$r = $this->removeDoubleQuotesFromArray($r);

				$out.="<tr>";
				$out.="<td style='white-space:nowrap'>".$r['de_codice']."</td>";
				$out.="<td>".$r['cliente']."</td>";
				$out.="<td>".$r['commessa']."</td>";
				foreach($fieldsAr as $k=>$v) $out.="<td class='n'>".numberf($v,0).MONEY."</td>";
				$out.="</tr>";

				
				$csv.='"'.$r['de_codice'].'"'.";";
				$csv.='"'.$r['cliente'].'"'.";";
				$csv.='"'.$r['commessa'].'"'.";";
				foreach($fieldsAr as $k=>$v) $csv.='"'.numberf($v,2).'"'.";";
				$csv.="\n";
				
				

				 $sommacosto_personale+= $r['costo_personale'];
				$c++;
			}

		} else {

			die("Selezionare un tipo di report");
		}

	
		if (isset($params["download_csv"]) && $params["download_csv"]==true) {
			$csv_converted = base64_encode(  mb_convert_encoding($csv, 'ISO-8859-1', 'UTF-8') );
			$csv="<br><a download='report-".$nomegruppo."-".date("Y-m-d").".csv' href=\"data:application/octet-stream;charset=utf-16le;base64,".$csv_converted."\" class=\"btn\">{Download CSV}</a>";
		} else {
			$csv = "";
		}

	
		return $report_print."<div class=\"grigliacontainer\"><table id='report' class='griglia'>".$header.$out."</table></div>".$csv;
	}



	function getHtmlCercaBox($def="") {
		//------------------------------------------------
		return "<input type='text' name='keyword' id='keyword' value=\"{$def}\"/>";
	}

}

?>