<?php
/*
	gestione reportistica timesheet
*/

class Report {

	var $gestore;

	var $arStati;

	function __construct() {
		global $session,$root,$conn;
		$this->gestore = $_SERVER["PHP_SELF"];

		checkAbilitazione("TSREPORT","TSREPORT");


	}

    function removeDoubleQuotesFromArray($r) {
        return array_map(function($value) {
            return is_string($value) ? str_replace('"', '', $value) : $value;
        }, $r);
    }

	function getPannello($dati) {


        if(!isset($dati["persona"])) {
            $dati["persona"] = "-2";
        }
		if(!isset($dati["job"])) {
            $dati["job"] = "-2";
        }
		if(!isset($dati["gruppo"])) {
            $dati["gruppo"] = "";
        }
		if($dati["gruppo"] =="worked" && !isset($dati["dal"]) && (integer)$dati["job"]>0) {
			$dati["dal"] = 
				execute_scalar("SELECT dt_inizio FROM `".DB_PREFIX."ts_job` WHERE id_job='".(integer)$dati["job"]."'");
			$dati["al"] = 
				execute_scalar("SELECT dt_fine FROM `".DB_PREFIX."ts_job` WHERE id_job='".(integer)$dati["job"]."'");
		}
        if(!isset($dati["dal"])) {
            $dati["dal"] = date("Y")."-01-01"; 
            //execute_scalar("SELECT min(dt_giorno) FROM `".DB_PREFIX."ts_ore` WHERE 1", date("Y-m-d"));
        }
		
		global $session,$root,$conn;
	
		$html = "";
		
		if ($session->get("TSREPORT")) {

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

			//------------------------------------------------
			//combo persone
				
			$sql = "select id,CONCAT(de_sigla,' ',nome,' ',cognome) as none from ".DB_PREFIX."frw_utenti left outer join ".DB_PREFIX."frw_extrauserdata on cd_user=id order by cognome,nome";
			$id_utente =$session->get("idutente");
			$rs = $conn->query("select cd_reparto from ".DB_PREFIX."frw_utenti,".DB_PREFIX."frw_extrauserdata where id=".$id_utente." and cd_profilo in (15,16) and cd_user=id") or die($conn->error);
			if($riga = $rs->fetch_array()) {
				$sql = "select id,CONCAT(de_sigla,' ',nome,' ',cognome) as none from ".DB_PREFIX."frw_utenti left outer join ".DB_PREFIX."frw_extrauserdata on cd_user=id where exists(select 0 from ".DB_PREFIX."frw_extrauserdata where cd_reparto=".$riga['cd_reparto']." and cd_user=id) order by cognome";
				$sqlreparto="select de_nomereparto,id_reparto from ".DB_PREFIX."ts_reparti where id_reparto=".$riga['cd_reparto'];
				
			}else  {
				$sqlreparto="select de_nomereparto,id_reparto from ".DB_PREFIX."ts_reparti order by de_nomereparto";
				$arReparto[""]="--{All}--";
			}

			$rs = $conn->query($sql) or trigger_error($conn->error." ".$sql);
			$arUtenti[""]="--{All}--";
			$arUtenti["-1"]="{All people OFF}";
			$arUtenti["-2"]="{All people ON}";
			while($riga = $rs->fetch_array()) {
				$arUtenti[$riga['id']]=$riga['none'];
			}
			//------------------------------------------------
			$persona = new optionlist("persona",isset($dati["persona"])?$dati["persona"]:"",$arUtenti);
			$persona->obbligatorio=0;
			$persona->label="'{Person}'";
            $persona->attributes=" class='filter'";
			$objform->addControllo($persona);

			$rs = $conn->query($sqlreparto) or trigger_error($conn->error." ".$sqlreparto);
			
			while($riga = $rs->fetch_array()) {
				$arReparto[$riga['id_reparto']]=$riga['de_nomereparto'];
			}
			//------------------------------------------------
			if(!isset($dati["reparto"])) {
				$dati["reparto"] = execute_scalar("select id_reparto from ".DB_PREFIX."ts_reparti where fl_default='1'","");
			}
			$reparto = new optionlist("reparto",$dati["reparto"],$arReparto);
			$reparto->obbligatorio=0;
			$reparto->label="'{Department}'";
            $reparto->attributes=" class='filter'";
			$objform->addControllo($reparto);

			$gruppo = new optionlist("gruppo",isset($dati["gruppo"])?$dati["gruppo"]:"",array(
				"std"=>"{Standard}",
				"cd_utente"=>"{By person}",
				"cd_cliente"=>"{By client}",
				"cd_job"=>"{By job}",
				"worked"=>"{Work report}",
				""=>"--{None}--"
			));
			$gruppo->obbligatorio=0;
			$gruppo->label="'Reparto'";
            $gruppo->attributes=" class='filter'";
			$objform->addControllo($gruppo);


			$op = new hidden("op","cerca");

			$html = loadTemplateAndParse ("template/elenco.html");

			$html = str_replace("##STARTFORM##", $objform->startform(), $html);
			$html = str_replace("##op##", $op->gettag(), $html);
			$html = str_replace("##cliente##", $cliente->gettag(), $html);
			$html = str_replace("##job##", $job->gettag(), $html);
			$html = str_replace("##gruppo##", $gruppo->gettag(), $html);
			$html = str_replace("##persone##", $persona->gettag(), $html);
			$html = str_replace("##dal##", $dal->gettag(), $html);
			$html = str_replace("##reparto##", $reparto->gettag(), $html);
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
				END) AS costo
			FROM ".DB_PREFIX."frw_utenti b,".DB_PREFIX."ts_job d, ".DB_PREFIX."ts_clienti e,".DB_PREFIX."frw_extrauserdata h,".DB_PREFIX."ts_ore c
			LEFT OUTER JOIN ".DB_PREFIX."ts_users_annual_cost AC on AC.cd_user=c.cd_utente and AC.nu_anno=YEAR(c.dt_giorno)
			where c.cd_utente=b.id 
			and d.id_job=c.cd_job
			and d.cd_cliente=e.id_cliente #altriwhere# 
			and h.cd_user=b.id group by cd_cliente,de_nomecliente order by de_nomecliente";

			$altriwhere = "";

			if($dati['reparto']!='') {
				$altriwhere.=" and c.cd_reparto_ora=".$dati['reparto']." ";
			}
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
			if($dati['persona']) {

				if($dati['persona']=="-1") {
					$altriwhere.=" and b.fl_attivo='0' "; // OFF
				} elseif($dati['persona']=="-2") { 
					$altriwhere.=" and b.fl_attivo='1' "; // ON
				} elseif($dati['persona']=="") { 
					// ALL
				} else $altriwhere.=" and b.id='".$dati['persona']."' ";

			}
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
			$sommacosto = 0;
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
				$out.="<td class='n'>".numberf($r['costo'],0).MONEY."</td>";
				$out.="</tr>";

				$csv.='"'.$r['cliente'].'"'.";";
				$csv.='"'.numberf($r['ore'],1).'"'.";";
				$csv.='"'.numberf($r['giornate'],1).'"'.";";
				$csv.='"'.numberf($r['costo'],2).'"'.";";
				$csv.="\n";
				
				
				$sommaore += $r['ore'];
				$sommagiornate += $r['giornate'];				
				$sommacosto+= $r['costo'];
				$c++;
			}
			if($c>0) {
				$out.="<tr>";
			
				$out.="<th class='n'>&nbsp;</th>";
				$out.="<th class='n'>".numberf($sommaore,1)."h "."</th>";
				$out.="<th class='n'>".numberf($sommagiornate,1)."g "."</th>";
				$out.="<th class='n'>".numberf($sommacosto,0).MONEY."</th>";
				$out.="</tr>";

				$csv.=";";
				$csv.='"'.numberf($sommaore,1).'"'.";";
				$csv.='"'.numberf($sommagiornate,1).'"'.";";
				$csv.='"'.numberf($sommacosto,0).'"'.";";
				$csv.="\n";
				$sommaore = 0;
				$sommagiorni = 0;
				$sommaeuri = 0;
			}

		}

		if($dati['gruppo']=="cd_job") {

			//
			// visualization by job
			//
			$nomegruppo = "commesse";
			$sql="SELECT id_job,sum(c.nu_ore) as ore,sum(c.nu_ore/8) as giornate, e.de_nomecliente as cliente  ,d.de_nomejob as commessa , d.de_codice,

				SUM(CASE WHEN AC.nu_cost IS NOT NULL 
					THEN AC.nu_cost*c.nu_ore
					ELSE h.nu_costo*c.nu_ore
				END) AS costo

			FROM ".DB_PREFIX."frw_utenti b,".DB_PREFIX."ts_job d, ".DB_PREFIX."ts_clienti e,".DB_PREFIX."frw_extrauserdata h,".DB_PREFIX."ts_ore c
			LEFT OUTER JOIN ts_users_annual_cost AC on AC.cd_user=c.cd_utente and AC.nu_anno=YEAR(c.dt_giorno)
			where c.cd_utente=b.id 
			and d.id_job=c.cd_job
			and d.cd_cliente=e.id_cliente #altriwhere# 
			and h.cd_user=b.id group by cd_job,de_nomejob,de_nomecliente order by de_codice";
			$altriwhere = "";

			if($dati['reparto']!='') {
				$altriwhere.=" and c.cd_reparto_ora=".$dati['reparto']." ";
			}
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
			
			if($dati['persona']=="-1") {
				$altriwhere.=" and b.fl_attivo='0' "; // OFF
			} elseif($dati['persona']=="-2") { 
				$altriwhere.=" and b.fl_attivo='1' "; // ON
			} elseif($dati['persona']=="") { 
				// ALL
			} else $altriwhere.=" and b.id='".$dati['persona']."' ";

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
			$sommacosto = 0;
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
				$out.="<td class='n'>".numberf($r['costo'],2).MONEY."</td>";
				$out.="</tr>";

				
				$csv.='"'.$r['de_codice'].'"'.";";
				$csv.='"'.$r['cliente'].'"'.";";
				$csv.='"'.$r['commessa'].'"'.";";
				$csv.='"'.numberf($r['ore'],1).'"'.";";
				$csv.='"'.numberf($r['giornate'],1).'"'.";";
				$csv.='"'.numberf($r['costo'],2).'"'.";";
				$csv.="\n";
				
				

				$sommaore += $r['ore'];
				$sommagiornate += $r['giornate'];
				$sommacosto+= $r['costo'];
				$c++;
			}
			if($c>0) {
				$out.="<tr>";
			
				$out.="<th class='n' >&nbsp;</th>";
				$out.="<th class='n' >&nbsp;</th>";
				$out.="<th class='n' >&nbsp;</th>";
				$out.="<th class='n' >".numberf($sommaore,1)."h "."</th>";
				$out.="<th class='n' >".numberf($sommagiornate,1)."g "."</th>";
				$out.="<th class='n' >".numberf($sommacosto,0).MONEY."</th>";
				$out.="</tr>";

				$csv.=";";
				$csv.=";";
				$csv.=";";
				$csv.='"'.numberf($sommaore,1).'"'.";";
				$csv.='"'.numberf($sommagiornate,1).'"'.";";
				$csv.='"'.numberf($sommacosto,0).'"'.";";
				$csv.="\n";
				$sommaore = 0;
				$sommagiorni = 0;
				$sommaeuri = 0;
			}


		}

		if(isset($dati['gruppo']) && $dati['gruppo']=="cd_utente") {

			//
			// visualization by utente
			//
			$nomegruppo = "persona";
			$sql="SELECT b.id,h.de_sigla, CONCAT(b.cognome,' ',b.nome) as nome,sum(c.nu_ore) as ore,sum(c.nu_ore/8) as giornate,
			
			SUM(CASE WHEN AC.nu_cost IS NOT NULL 
				THEN AC.nu_cost*c.nu_ore
				ELSE h.nu_costo*c.nu_ore
			END) AS costo

			FROM 
            ".DB_PREFIX."ts_ore c 
            INNER JOIN ".DB_PREFIX."ts_job d ON d.id_job=c.cd_job
            LEFT OUTER JOIN ".DB_PREFIX."frw_utenti b ON c.cd_utente=b.id 
            LEFT OUTER JOIN ".DB_PREFIX."frw_extrauserdata h ON h.cd_user=b.id
			LEFT OUTER JOIN ts_users_annual_cost AC on AC.cd_user=c.cd_utente and AC.nu_anno=YEAR(c.dt_giorno)
            
            where 1
			
            #altriwhere# 
			and h.cd_user=b.id group by b.id,h.de_sigla,nome 
            
            order by h.de_sigla,nome";
			$altriwhere = "";

			if(isset($dati['reparto']) && $dati['reparto']!='') {
				$altriwhere.=" and c.cd_reparto_ora=".$dati['reparto']." ";
			}
			if(isset($dati['cliente']) && $dati['cliente']!='') {
				$altriwhere.=" and d.cd_cliente = '{$dati['cliente']}'";
			}
			if($dati['job']=="-1") {
				$altriwhere.=" and d.fl_attivo='0' "; // JOB OFF
			} elseif($dati['job']=="-2") { 
				$altriwhere.=" and d.fl_attivo='1' "; // JOB ON
			} elseif($dati['job']=="") { 
				// ALL JOBS
			} else $altriwhere.=" and d.id_job='".$dati['job']."' "; // SPECIFIC JOB
			if($dati['persona']=="-1") {
				$altriwhere.=" and b.fl_attivo='0' "; // OFF
			} elseif($dati['persona']=="-2") { 
				$altriwhere.=" and b.fl_attivo='1' "; // ON
			} elseif($dati['persona']=="") { 
				// ALL
			} else $altriwhere.=" and b.id='".$dati['persona']."' ";
			if(isset($dati['dal']) && $dati['dal']) {
				$altriwhere.=" and c.dt_giorno>='".$dati['dal']."' ";
			}
			if(isset($dati['al']) && $dati['al']) {
				$altriwhere.=" and c.dt_giorno<='".$dati['al']."' ";
			}
			$sql = str_replace("#altriwhere#",$altriwhere,$sql);
            //    echo $sql;
            //    die;

			$rs = $conn->query($sql) or trigger_error($conn->error." ".$sql);
			$out = "";
			//id_cliente 	de_nomecliente 	id_job 	de_codice 	de_nomejob 	dt_inizio 	dt_fine 	cd_cliente 	id_ora 	cd_utente 	de_nota 	cd_job 	nu_ore 	dt_giorno 	id 	username 	password 	nome 	cognome 	fl_attivo 	cd_profilo

			//$job = "";
			$sommaore = 0;
			$sommagiornate = 0;
			$sommacosto = 0;
			

			$header = "";
			$c = 0;
			$sommatutteore = 0;
					$out="<tr>";
				$out.="<th>{Abbreviation}</th>";
				$out.="<th>{Name}</th>";
				$out.="<th class='n'>{Hours}</th>";
				$out.="<th class='n'>{Days}</th>";
				$out.="<th class='n'>{Cost}</th>";
				$out.="</tr>";

				$csv="";
				$csv.='"'."{Abbreviation}".'";';
				$csv.='"'."{Name}".'";';
				$csv.='"'."{Hours}".'";';
				$csv.='"'."{Days}".'";';
				$csv.='"'."{Cost}".'";';
				$csv.="\n";
				$csv = translateHtml($csv);
			while($r=$rs->fetch_array()) {	

				$r = $this->removeDoubleQuotesFromArray($r);

				// remove double quotes from all the elements of the array $r


				$out.="<tr>";
				$out.="<td>".$r['de_sigla']."</td>";
				$out.="<td>".$r['nome']."</td>";
				$out.="<td class='n'>".numberf($r['ore'],1)."</td>";
				$out.="<td class='n'>".numberf($r['giornate'],1)."</td>";
				$out.="<td class='n'>".numberf($r['costo'],0).MONEY."</td>";
				$out.="</tr>";

				
				$csv.='"'.$r['de_sigla'].'"'.";";
				$csv.='"'.$r['nome'].'"'.";";
				$csv.='"'.numberf($r['ore'],1).'"'.";";
				$csv.='"'.numberf($r['giornate'],1).'"'.";";
				$csv.='"'.numberf($r['costo'],0).'"'.";";
				$csv.="\n";
				
				

				$sommaore += $r['ore'];
				$sommagiornate += $r['giornate'];
				
				$sommacosto+= $r['costo'];
				$c++;
			}
			if($c>0) {
				$out.="<tr>";
			
				$out.="<th class='n'>&nbsp;</th>";
				$out.="<th class='n'>&nbsp;</th>";
				$out.="<th class='n' >".numberf($sommaore,1)."h </th>";
				$out.="<th class='n' >".numberf($sommagiornate,1)."g </th>";
				$out.="<th class='n' >".numberf($sommacosto,0).MONEY."</th>";
				$out.="</tr>";

				$csv.=";";
				$csv.=";";
				$csv.='"'.numberf($sommaore,1).'"'.";";
				$csv.='"'.numberf($sommagiornate,1).'"'.";";
				$csv.='"'.numberf($sommacosto,0).'"'.";";
				$csv.="\n";
				$sommaore = 0;
				$sommagiornate = 0;
				$sommaeuri = 0;
			}


		}


		
		if($dati['gruppo']=="std") {
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
			$sommacosto = 0;
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
                $r['costo'] = 0;

                for($i=1;$i<=1;$i++) {
                    $altriwhere = "";
                    $altrijoin = "";

                    if($dati['reparto']!='') {
                        $altriwhere.=" and c.cd_reparto_ora=".$dati['reparto']." ";
                        // $altrijoin .= " INNER JOIN ".DB_PREFIX."ts_tipiora t ON t.id_tipoora=c.cd_tipoora " ;
                    }
                    if($dati['persona']=="-1") {
                        $altriwhere.=" and b.fl_attivo='0' "; // OFF
                        $altrijoin = " INNER JOIN ".DB_PREFIX."frw_utenti b ON b.id=c.cd_utente";
                    } elseif($dati['persona']=="-2") { 
                        $altriwhere.=" and b.fl_attivo='1' "; // ON
                        $altrijoin .= " INNER JOIN ".DB_PREFIX."frw_utenti b ON b.id=c.cd_utente";
                    } elseif($dati['persona']!="") {
                        $altriwhere.=" and b.id='".$dati['persona']."' "; // persona
                        $altrijoin .= " INNER JOIN ".DB_PREFIX."frw_utenti b ON b.id=c.cd_utente";
                    }
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
					END) AS costo

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
                        $r['costo'] += $rm['costo'];
						$fieldsAr[$rm['m']] = $rm['costo'];
						
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
				
				

				 $sommacosto+= $r['costo'];
				$c++;
			}


			// $out.=$sommacosto;
			// if($c>0) {
			// 	$out.="<tr>";
			
			// 	$out.="<th class='n' >&nbsp;</th>";
			// 	$out.="<th class='n' >&nbsp;</th>";
			// 	$out.="<th class='n' >&nbsp;</th>";
			// 	$out.="<th class='n' >".numberf($sommacosto,0).MONEY."</th>";
			// 	$out.="</tr>";

			// 	$csv.=";";
			// 	$csv.=";";
			// 	$csv.=";";
			// 	$csv.='"'.numberf($sommacosto,0).'"'.";";
			// 	$csv.="\n";
			// 	$sommaore = 0;
			// 	$sommagiorni = 0;
			// 	$sommaeuri = 0;
			// }





		}


		if($dati['gruppo']=="worked") {

			// client is mandatory
			$bhAr = array();

			$sql="SELECT id_job,nu_budget_hours,(select g.de_nomereparto FROM ".DB_PREFIX."ts_reparti g  WHERE g.id_reparto=h.cd_reparto) AS reparto, CONCAT(b.nome,' ',b.cognome) AS nome,h.de_sigla,c.dt_giorno,c.nu_ore AS ore, e.de_nomecliente AS cliente,d.de_codice AS codice,d.de_nomejob AS commessa ,  f.de_tipoora AS tipologia,c.de_nota AS descrizione,
			
			CASE WHEN AC.nu_cost IS NOT NULL 
				THEN (AC.nu_cost*c.nu_ore)
				ELSE (h.nu_costo*c.nu_ore)
			END AS costo
			
			FROM ".DB_PREFIX."frw_utenti b,".DB_PREFIX."ts_job d, ".DB_PREFIX."ts_clienti e,".DB_PREFIX."frw_extrauserdata h,".DB_PREFIX."ts_ore c
			LEFT JOIN ".DB_PREFIX."ts_tipiora f on f.id_tipoora=c.cd_tipoora
			LEFT OUTER JOIN ts_users_annual_cost AC on AC.cd_user=c.cd_utente and AC.nu_anno=YEAR(c.dt_giorno)	
			where c.cd_utente=b.id 
			and d.id_job=c.cd_job
			and d.cd_cliente=e.id_cliente #altriwhere# 
			and h.cd_user=b.id order by dt_giorno,1,2,3,4,5,6,7,8,9";
			$altriwhere = "";

			$id_utente =(integer)$session->get("idutente");
			$rs = $conn->query("select cd_reparto from ".DB_PREFIX."frw_utenti,".DB_PREFIX."frw_extrauserdata where id=".$id_utente." and cd_profilo=15 and cd_user=id") or die($conn->error());
			
			while($r=$rs->fetch_array()) {
				$altriwhere.=" and h.cd_reparto=".$r['cd_reparto']." ";
			}
			if($dati['reparto']!='') {
				$altriwhere.=" and h.cd_reparto=".$dati['reparto']." ";
			}
			if($dati['cliente']!='') {
				$altriwhere.=" and e.id_cliente = '{$dati['cliente']}'";
			}

			if($dati['job']=="-1") {
				$altriwhere.=" and d.fl_attivo='0' "; // OFF
			} elseif($dati['job']=="-2") { 
				$altriwhere.=" and d.fl_attivo='1' "; // ON
			} elseif($dati['job']=="") { 
				// ALL
			} else $altriwhere.=" and d.id_job='".$dati['job']."' ";

			if($dati['persona']=="-1") {
				$altriwhere.=" and b.fl_attivo='0' "; // OFF
			} elseif($dati['persona']=="-2") { 
				$altriwhere.=" and b.fl_attivo='1' "; // ON
			} elseif($dati['persona']=="") { 
				// ALL
			} else $altriwhere.=" and b.id='".$dati['persona']."' ";
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
			
			
			

			$header = "";
			$c = 0;
			$sommatutteore = 0;
				$out="<tr>";

				$out.="<th>{Date}</th>";
				$out.="<th class='n'>{Hours}</th>";
				$out.="<th class='n'>{Job}</th>";
				$out.="<th>{Description}</th>";
				$out.="<th>{Name}</th>";
				$out.="<th>{Department}</th>";
				$out.="</tr>";

				$csv="";
				$csv.='"'."{Date}".'"'.";";
				$csv.='"'."{Hours}".'"'.";";
				$csv.='"'."{Job}".'"'.";";
				$csv.='"'."{Description}".'"'.";";
				$csv.='"'."{Name}".'"'.";";
				$csv.='"'."{Department}".'"'.";";
				$csv.="\n";
				$csv = translateHtml($csv);
			while($r=$rs->fetch_array()) {

				if( $report_print == "" ) {
					$report_print = "<div class='report_print'><h2>{Client}: " . $r['cliente'] . "</h2>";
					$report_print .= "<h3>{Period}: ".datef($dati['dal'])." - ".datef($dati['al'])."</h3>";
					$report_print .= "</div>";
				}

				$r = $this->removeDoubleQuotesFromArray($r);
				$weekend = date("w",strtotime($r['dt_giorno']));
				if($weekend==0 || $weekend==6) {
					$color = "color:red";
				} else {
					$color = "";
				}

				$out.="<tr>";
				$out.="<td style='".$color."'>".datef($r['dt_giorno'])."</td>";
				$out.="<td class='n'>".numberf($r['ore'],1)."</td>";
				$out.="<td class='n'>".$r['codice']."</td>";
				$out.="<td>".$r['tipologia']. " ".$r['descrizione']."</td>";
				$out.="<td>".$r['nome']."</td>";
				$out.="<td>".$r['reparto']."</td>";
								$out.="</tr>";

				
				$csv.='"'.datef($r['dt_giorno']).'"'.";";
				$csv.='"'.numberf($r['ore'],1).'"'.";";
				$csv.='"'.$r['codice'].'"'.";";
				$csv.='"'.$r['tipologia'].' '.$r['descrizione'].'"'.";";
				$csv.='"'.$r['nome'].'"'.";";
				$csv.='"'.$r['reparto'].'"'.";";
								$csv.="\n";

				$sommaore += $r['ore'];
				$c++;
			
				$bhAr[$r['id_job']] = $r['nu_budget_hours'];
			}
			if($c>0) {


				$nu_budget_hours = 0;
				foreach($bhAr as $k => $v) $nu_budget_hours += $v;
				


				$out.="<tr>";
				
			
				$out.="<th class='n'>{Total time}</th>";
				$out.="<th class='n' >".numberf($sommaore,1)."h "."</th>";
				$out.="<th class='n'>" . ($nu_budget_hours>0 ? "{Limit hours}:":"")."</th>";
				$out.="<th>" .($nu_budget_hours>0 ? numberf($nu_budget_hours,1)."h" : "")." </th>";
				$out.="<th class='n'>{From}</th>";
				$out.="<th class='n' >".datef($dati['dal'])."</th>";
				$out.="</tr>";

				$csv.='"'."{Total time}".'"'.";";
				$csv.=numberf($sommaore,1).";".($nu_budget_hours>0 ? "{Limit hours}:":"").";".($nu_budget_hours>0 ? numberf($nu_budget_hours,1):"").";;;";
				$csv.="\n";
				$sommaore = 0;
				$sommagiorni = 0;
				$sommaeuri = 0;
			}

		}


		if($dati['gruppo']=="") {

			$sql="SELECT id_job,(select g.de_nomereparto from ".DB_PREFIX."ts_reparti g  where g.id_reparto=h.cd_reparto) as reparto, CONCAT(b.nome,' ',b.cognome) as nome,h.de_sigla,DAYOFMONTH(c.dt_giorno) as giorno, MONTHNAME(c.dt_giorno) as mese,YEAR(c.dt_giorno) as anno ,dt_giorno as ladata,c.nu_ore as ore, e.de_nomecliente as cliente,d.de_codice as codice,d.de_nomejob as commessa ,  f.de_tipoora as tipologia,c.de_nota as descrizione,
			
			CASE WHEN AC.nu_cost IS NOT NULL 
				THEN (AC.nu_cost*c.nu_ore)
				ELSE (h.nu_costo*c.nu_ore)
			END AS costo	
			
			FROM ".DB_PREFIX."frw_utenti b,".DB_PREFIX."ts_job d, ".DB_PREFIX."ts_clienti e,".DB_PREFIX."frw_extrauserdata h,".DB_PREFIX."ts_ore c
			LEFT JOIN ".DB_PREFIX."ts_tipiora f on f.id_tipoora=c.cd_tipoora
			LEFT OUTER JOIN ts_users_annual_cost AC on AC.cd_user=c.cd_utente and AC.nu_anno=YEAR(c.dt_giorno)
			where c.cd_utente=b.id 
			and d.id_job=c.cd_job
			and d.cd_cliente=e.id_cliente #altriwhere# 
			and h.cd_user=b.id order by dt_giorno,1,2,3,4,5,6,7,8,9";
			$altriwhere = "";

			$id_utente =$session->get("idutente");
			$rs = $conn->query("select cd_reparto from ".DB_PREFIX."frw_utenti,".DB_PREFIX."frw_extrauserdata where id=".$id_utente." and cd_profilo=15 and cd_user=id") or die($conn->error());
			
			while($r=$rs->fetch_array()) {
				$altriwhere.=" and h.cd_reparto=".$r['cd_reparto']." ";
			}
			if($dati['reparto']!='') {
				$altriwhere.=" and h.cd_reparto=".$dati['reparto']." ";
			}
			if($dati['cliente']!='') {
				$altriwhere.=" and e.id_cliente = '{$dati['cliente']}'";
			}

			if($dati['job']=="-1") {
				$altriwhere.=" and d.fl_attivo='0' "; // OFF
			} elseif($dati['job']=="-2") { 
				$altriwhere.=" and d.fl_attivo='1' "; // ON
			} elseif($dati['job']=="") { 
				// ALL
			} else $altriwhere.=" and d.id_job='".$dati['job']."' ";

			if($dati['persona']=="-1") {
				$altriwhere.=" and b.fl_attivo='0' "; // OFF
			} elseif($dati['persona']=="-2") { 
				$altriwhere.=" and b.fl_attivo='1' "; // ON
			} elseif($dati['persona']=="") { 
				// ALL
			} else $altriwhere.=" and b.id='".$dati['persona']."' ";
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
			$sommacosto = 0;
			

			$header = "";
			$c = 0;
			$sommatutteore = 0;
					$out="<tr>";
				$out.="<th>{Department}</th>";
				$out.="<th>{Name}</th>";
				$out.="<th>{Abbreviation}</th>";
				$out.="<th>{Date}</th>";
				$out.="<th class='n'>{Hours}</th>";
				$out.="<th>{Client}</th>";
				$out.="<th>{Code}</th>";
				$out.="<th>{Job}</th>";
				$out.="<th>{Type of hour}</th>";
				$out.="<th>{Notes}</th>";
				$out.="<th>{Cost}</th>";
                $out = translateHtml($out);
				$out.="</tr>";

				$csv="";
				$csv.='"'."{Department}".'"'.";";
				$csv.='"'."{Name}".'"'.";";
				$csv.='"'."{Abbreviation}".'"'.";";
				$csv.='"'."{Date}".'"'.";";
				$csv.='"'."{Hours}".'"'.";";
				$csv.='"'."{Client}".'"'.";";
				$csv.='"'."{Code}".'"'.";";
				$csv.='"'."{Job}".'"'.";";
				$csv.='"'."{Type of hour}".'"'.";";
				$csv.='"'."{Notes}".'"'.";";
				$csv.='"'."{Cost}".'"'.";";
				$csv.="\n";
				$csv = translateHtml($csv);
			while($r=$rs->fetch_array()) {

				$r = $this->removeDoubleQuotesFromArray($r);

				$weekend = date("w",strtotime($r['ladata']));
				if($weekend==0 || $weekend==6) {
					$color = "color:red";
				} else {
					$color = "";
				}

				$out.="<tr>";
				$out.="<td>".$r['reparto']."</td>";
				$out.="<td>".$r['nome']."</td>";
				$out.="<td>".$r['de_sigla']."</td>";
                $out.="<td style='".$color."'>".datef($r['ladata'])."</td>";
				// $out.="<td class='n'>".$r['giorno']."</td>";
				// $out.="<td>{".$r['mese']."}</td>";
				// $out.="<td class='n'>".$r['anno']."</td>";
				$out.="<td class='n'>".numberf($r['ore'],1)."</td>";
				$out.="<td>".$r['cliente']."</td>";
				$out.="<td style='white-space:nowrap'>".$r['codice']."</td>";
				$out.="<td>".$r['commessa']."</td>";
				$out.="<td>".$r['tipologia']."</td>";
				$out.="<td>".$r['descrizione']."</td>";
				$out.="<td class='n'>".numberf($r['costo'],0).MONEY."</td>";
				$out.="</tr>";

				
				$csv.='"'.$r['reparto'].'"'.";";
				$csv.='"'.$r['nome'].'"'.";";
				$csv.='"'.$r['de_sigla'].'"'.";";
                $csv.='"'.datef($r['ladata']).'"'.";";
				// $csv.='"'.$r['giorno'].'"'.";";
				// $csv.='"'.$r['mese'].'"'.";";
				// $csv.='"'.$r['anno'].'"'.";";
				$csv.='"'.numberf($r['ore'],1).'"'.";";
				$csv.='"'.$r['cliente'].'"'.";";
				$csv.='"'.$r['codice'].'"'.";";
				$csv.= '"'.$r['commessa'].'"'.";";
				$csv.='"'.$r['tipologia'].'"'.";";
				$csv.='"'.$r['descrizione'].'"'.";";
				$csv.='"'.numberf($r['costo'],2).'"'.";";
				$csv.="\n";
				
				

				$sommaore += $r['ore'];
				
				$sommacosto+= $r['costo'];
				$c++;
			}
			if($c>0) {
				$out.="<tr>";
			
				$out.="<th class='n' colspan='4'>{Total time}</th>";
				$out.="<th class='n' >".numberf($sommaore,1)."h "."</th>";
				$out.="<th class='n' colspan='5'>{Total cost}</th>";
				$out.="<th class='n' >".numberf($sommacosto,2).MONEY."</th>";
				$out.="</tr>";

				$csv.='"'."{Total time}".'"'.";;;;";
				$csv.=numberf($sommaore,1).";";
				$csv.='"'."{Total cost}".'"'.";;;;;";
				$csv.='"'.numberf($sommacosto,2).'"'.";";
				$csv.="\n";
				$sommaore = 0;
				$sommagiorni = 0;
				$sommaeuri = 0;
			}


		}
		
		if (isset($params["download_csv"]) && $params["download_csv"]==true) {
			$csv_converted = base64_encode(  mb_convert_encoding($csv, 'ISO-8859-1', 'UTF-8') );
			$csv="<br><a download='report-".$nomegruppo."-".date("Y-m-d").".csv' href=\"data:application/octet-stream;charset=utf-16le;base64,".$csv_converted."\" class=\"btn\">{Download CSV}</a>";
		} else {
			$csv = "";
		}

		$shareLink = "";
		if($dati['gruppo']== 'worked') {
			$shareLink = WEBURL."/src/componenti/tsreport/index.php?" .
				"op=cerca&".
				"dal=".$dati['dal']."&".
				"al=".$dati['al']."&".
				"cliente=".$dati['cliente']."&".
				"job=".$dati['job']."&".
				"persona=".$dati['persona']."&".
				"gruppo=".$dati['gruppo']."&".
				"share=1&".
				"reparto=".$dati['reparto'];

			$shareLink.="&check=".md5(ENCRYPTIONKEY.$dati['dal'].$dati['al'].$dati['cliente'].$dati['job'].$dati['persona'].$dati['gruppo'].$dati['reparto']);

			$exist = execute_row("select * from ".DB_PREFIX."ts_worked_reports where de_link = '".$shareLink."'",false);
			if($exist === false) {
				$dt = date("Y-m-d H:i:s");
				$conn->query("INSERT INTO ".DB_PREFIX."ts_worked_reports (de_link,dt_saved) values ('".$shareLink."','".$dt."') ");
				$id_dt = $conn->insert_id;
			} else {
				$dt = $exist['dt_saved'];
				$id_dt = $exist['id_report'];
			}

			$shareLink = WEBURL."/src/componenti/tsreport/index.php?report=".$id_dt.".".$dt;
			$shareLink = " <a href=\"".$shareLink."\" class=\"btn\">{Share}</a>";

		}



		
		return $report_print."<div class=\"grigliacontainer\"><table id='report' class='griglia'>".$header.$out."</table></div>".$csv. $shareLink;
	}



	function getHtmlCercaBox($def="") {
		//------------------------------------------------
		return "<input type='text' name='keyword' id='keyword' value=\"{$def}\"/>";
	}

}

?>