<?php
/*
	gestione reportistica timesheet
*/

class Oremancanti {

	var $gestore;

	var $arStati;

	function __construct() {
		global $session,$root,$conn;
		$this->gestore = $_SERVER["PHP_SELF"];

		checkAbilitazione("TSOREMANCANTI","TSOREMANCANTI");


	}

	function removeDoubleQuotesFromArray($r) {
        return array_map(function($value) {
            return is_string($value) ? str_replace('"', '', $value) : $value;
        }, $r);
    }


	function getPannello($dati) {

        if(!isset($dati["persona"])) {
            $dati["persona"] = "2";
        }

		global $session,$root,$conn;

		$html = "";
		
		if ($session->get("TSOREMANCANTI")) {

			
			
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
			$max_days=90*24*3600;
			$myDate=time()-$max_days;
			$arPeriodo=array();
			
			while($myDate<time()+3600){
				$next_monday =  strtotime("next monday", $myDate);
				$next_sunday =  strtotime("next sunday", $next_monday);
				//$arPeriodo[$next_monday."|".$next_sunday]="dal ".date("d/m/Y",$next_monday)." al ".date("d/m/Y",$next_sunday);
				$myDate+=24*3600;
			}
 			$arPeriodo=array_reverse($arPeriodo,true);

			$mesi = explode(",","{January},{February},{March},{April},{May},{June},{July},{August},{September},{October},{November},{December}");
			// periodi mensili
			$m = 52; //(integer)date("m");
			for($i=0; $i< $m;$i++){

				//$d = "Y/".str_pad(($i+1),2,"0",STR_PAD_LEFT)."/01";
				$d = "Y/m/01";
				$p=strtotime("-".$i." months",strtotime(date($d)));
				$next = strtotime( "-1 day", strtotime("next month",$p));
				$arPeriodo[$p."|".$next]=$mesi[date("n",$p)-1]." ".date("Y",$p);//." ($i) dal ".date("d/m/Y",$p)." al ".date("d/m/Y",$next);
			}
			
			$periodo = new optionlist("periodo",((isset($dati["periodo"])?$dati["periodo"]:"")),$arPeriodo);
			$periodo->obbligatorio=0;
			$periodo->label="'{Week}'";
			$periodo->attributes=" class='filter'";
			$objform->addControllo($periodo);



			//------------------------------------------------
			//combo persone
			$sql = "select id,CONCAT(nome,' ',cognome) as none from ".DB_PREFIX."frw_utenti order by cognome";
			$id_utente =$session->get("idutente");
			$rs = $conn->query("select cd_reparto from ".DB_PREFIX."frw_utenti,".DB_PREFIX."frw_extrauserdata where id=".$id_utente." and cd_profilo=15 and cd_user=id") or die($conn->error);
			if($riga = $rs->fetch_array()) {
				$sql = "select id,CONCAT(nome,' ',cognome) as none from ".DB_PREFIX."frw_utenti where exists(select 0 from ".DB_PREFIX."frw_extrauserdata where cd_reparto=".$riga['cd_reparto']." and cd_user=id) order by cognome";
			}

			$rs = $conn->query($sql) or trigger_error($conn->error." ".$sql);
			$arUtenti["-2"]="All people ON";
            $arUtenti["-1"]="All people OFF";
            $arUtenti[""]="All";
			while($riga = $rs->fetch_array()) {
				$arUtenti[$riga['id']]=$riga['none'];
			}
			//------------------------------------------------
			$persona = new optionlist("persona",isset($dati["persona"])?$dati["persona"]:"",$arUtenti);
			$persona->obbligatorio=0;
			$persona->label="'Person'";
			$persona->attributes=" class='filter'";
			$objform->addControllo($persona);

			$submit = new submit("cerca","cerca");
			$op = new hidden("op","cerca");

			$html = loadTemplateAndParse ("template/elenco.html");

			$html = str_replace("##STARTFORM##", $objform->startform(), $html);
			$html = str_replace("##op##", $op->gettag(), $html);
			$html = str_replace("##SUBMIT##", "<a href='javascript:checkForm();' class='btn'>{Find}</a>", $html);

			$html = str_replace("##persone##", $persona->gettag(), $html);
			$html = str_replace("##dal##", $periodo->gettag(), $html);
			
			$html = str_replace("##gestore##", $this->gestore, $html);
			$html = str_replace("##ENDFORM##", $objform->endform(), $html);

			if(isset($dati["op"]) && $dati["op"]=='cerca') {
				$html = str_replace("##corpo##", $this->eseguiRicerca($dati), $html);
			} else {
				$html = str_replace("##corpo##", "", $html);
			}


		} else {
			$html = "0";
		}
		return $html;
	}

	function eseguiRicerca($dati) {
		global $session;
		global $conn;
		

		$sql="
		SELECT 
			(select g.de_nomereparto from ".DB_PREFIX."ts_reparti g  where g.id_reparto=h.cd_reparto) as reparto, 
			CONCAT(b.nome,' ',b.cognome) as nome,h.de_sigla,SUM(c.nu_ore) as ore, h.nu_oresettimanali as oresettimanali ,  (h.nu_oresettimanali / 5 * #giorni#) as oretot, SUM(c.nu_ore)-(h.nu_oresettimanali / 5 * #giorni#) as diff
		FROM ".DB_PREFIX."frw_utenti b,".DB_PREFIX."frw_extrauserdata h,".DB_PREFIX."ts_ore c		
			where c.cd_utente=b.id 
			 #altriwhere#  
			and h.cd_user=b.id 
			group by reparto,nome,cognome,h.de_sigla,h.nu_oresettimanali
		union
		SELECT 
		    (select g.de_nomereparto from ".DB_PREFIX."ts_reparti g  where g.id_reparto=h.cd_reparto) as reparto, 
			CONCAT(b.nome,' ',b.cognome) as nome,h.de_sigla as cognome,0 as ore, h.nu_oresettimanali as oresettimanali ,  (h.nu_oresettimanali / 5 * #giorni#) as oretot, -(h.nu_oresettimanali / 5 * #giorni#) as diff
			FROM ".DB_PREFIX."frw_utenti b,".DB_PREFIX."frw_extrauserdata h		
			where not exists(select 0 from ".DB_PREFIX."ts_ore c where c.cd_utente=b.id #altriwhere3# )
			 #altriwhere2#  
			and h.cd_user=b.id
			group by reparto,nome,cognome,de_sigla,h.nu_oresettimanali
			order by 1,2,3,4,5";
		$altriwhere = "";
		$altriwhere2 = "";
		$altriwhere3 = "";

		$id_utente =$session->get("idutente");
		$rs = $conn->query("select cd_reparto from ".DB_PREFIX."frw_utenti,".DB_PREFIX."frw_extrauserdata where id=".$id_utente." and cd_profilo in (15,16) and cd_user=id") or trigger_error($conn->error." ".$sql);
		
		while($r=$rs->fetch_array()) {
			$altriwhere.=" and h.cd_reparto=".$r['cd_reparto']." ";
			$altriwhere2.=" and h.cd_reparto=".$r['cd_reparto']." ";
		}
		
		
		if($dati['persona']) {
            if($dati['persona'] == "-1") {
                // inattivi
                $altriwhere.=" and b.fl_attivo='0' ";
			    $altriwhere2.=" and b.fl_attivo='0' ";
            } elseif($dati['persona'] == "-2") {
                // attivi
                $altriwhere.=" and b.fl_attivo='1' ";
			    $altriwhere2.=" and  b.fl_attivo='1' ";
            } else {
                // utente specifico
                $altriwhere.=" and b.id='".$dati['persona']."' ";
                $altriwhere2.=" and b.id='".$dati['persona']."' ";
            }
		} else {
            // tutti

        }

		$giorni = 30;
		if($dati['periodo']) {
			// C'È SEMPRE

			//echo $dati['periodo'];
			$temp = explode("|",$dati['periodo']);
			$from = $temp[0];
			$to = $temp[1];

			$altriwhere.=" and c.dt_giorno>='".date('Y-m-d',$from)."' ";
			$altriwhere.=" and c.dt_giorno<='".date('Y-m-d',$to)."' ";
			$altriwhere3.=" and c.dt_giorno>='".date('Y-m-d',$from)."' ";
			$altriwhere3.=" and c.dt_giorno<='".date('Y-m-d',$to)."' ";

			$giorniTot = floor(($to - $from) / (60 * 60 * 24));
			$giorni=1;
			for($i=0;$i<$giorniTot;$i++){
				$z = $from + (60 * 60 * 24 * $i);
				//echo (date("Ymd",$z))." ".date("w",$z)." $z<hr>";
				if(date("w",$z)!=0 && date("w",$z)!=6) {
					$giorni++;
				}
			}
			//echo $giorni;

		}
		
			
		
		$sql = str_replace("#altriwhere#",$altriwhere,$sql);
		$sql = str_replace("#altriwhere2#",$altriwhere2,$sql);
		$sql = str_replace("#altriwhere3#",$altriwhere3,$sql);
		$sql = str_replace("#giorni#",$giorni,$sql);

		

		

		$rs = $conn->query($sql) or trigger_error($conn->error." ".$sql);
		$out = "";
		//id_cliente 	de_nomecliente 	id_job 	de_codice 	de_nomejob 	dt_inizio 	dt_fine 	cd_cliente 	id_ora 	cd_utente 	de_nota 	cd_job 	nu_ore 	dt_giorno 	id 	username 	password 	nome 	cognome 	fl_attivo 	cd_profilo

		
	
		

		$header = "";
		$c = 0;
		$sommatutteore = 0;
		        $out="<tr>";
			$out.="<th>{Department}</th>";
			$out.="<th>{Shortname}</th>";			
			$out.="<th>{Name}</th>";
			$out.="<th class='n'>{Weekly hours}</th>";
			$out.="<th class='n'>{Total expected hours}</th>";
			$out.="<th class='n'>{Effective hours}</th>";
			$out.="<th class='n'>{Difference}</th>";
			$out.="</tr>";
			$out = translateHtml($out);

			$csv="";
			$csv.='"'."{Department}".'"'.";";
			$csv.='"'."{Shortname}".'"'.";";			
			$csv.='"'."{Name}".'"'.";";
			$csv.='"'."{Weekly hours}".'"'.";";
			$csv.='"'."{Total expected hours}".'"'.";";
			$csv.='"'."{Effective hours}".'"'.";";
			$csv.='"'."{Difference}".'"'.";";
			$csv.="\n";
			$csv = translateHtml($csv);

		while($r=$rs->fetch_array()) {

				$r = $this->removeDoubleQuotesFromArray($r);

			$out.="<tr>";
			$out.="<td>".$r['reparto']."</td>";
			$out.="<td>".$r['de_sigla']."</td>";
			$out.="<td>".$r['nome']."</td>";
			$out.="<td class='n'>".number_format($r['oresettimanali'],1,',','.')."</td>";
			$out.="<td class='n'>".number_format(round($r['oretot']),1,',','.')."</td>";
			$out.="<td class='n'>".number_format($r['ore'],1,',','.')."</td>";
			if ($r['diff'] < 0) {
				$class="neg";
			} else {
				$class="";
			}
			$out.="<td class='n $class'>".number_format(round($r['diff']),1,',','.')."</td>";
		
			$out.="</tr>";

			$csv.='"'.$r['reparto'].'"'.";";
			$csv.='"'.$r['de_sigla'].'"'.";";
			$csv.='"'.$r['nome'].'"'.";";
			$csv.='"'.number_format($r['oresettimanali'],1,',','').'"'.";";
			$csv.='"'.number_format(round($r['oretot']),1,',','').'"'.";";
			$csv.='"'.number_format($r['ore'],1,',','').'"'.";";
			$csv.='"'.number_format(round($r['diff']),1,',','').'"'.";";
		
			$csv.="\n";
			
				
			$c++;
		}
		if($c>0) {
			$out.="<tr>";
		
			$out.="<th class='n' colspan='7'></th>";
			$out.="</tr>";
			
		}
		$csv="<br><a download='ore-mancanti-".date("Y-m-d").".csv' href=\"data:application/octet-stream;charset=utf-16le;base64,".base64_encode(($csv))."\" class='btn'>{Download CSV}</a>";
		return "<div class='grigliacontainer'><table id='report' class='griglia'>".$header.$out."</table></div> ".$csv;
	}




	function getHtmlComboClienti($def="") {
		global $conn;
		//------------------------------------------------
		//combo filtri
		$sql = "select id_cliente,cd_cliente,de_nomecliente,count(*) as c from ".DB_PREFIX."ts_clienti 
			left outer join ".DB_PREFIX."ts_job on id_cliente=cd_cliente 
			group by id_cliente,cd_cliente,de_nomecliente order by de_nomecliente";
		$rs = $conn->query($sql) or trigger_error($conn->error." ".$sql);
		$arFiltri = array(""=>"--{All}--");
		while($riga = $rs->fetch_array()) {
			if ($riga['cd_cliente']=="") $riga['c']=0;
			$arFiltri[$riga['id_cliente']]=$riga['de_nomecliente']." (".$riga['c']." job)";
		}
		//------------------------------------------------
		$out = "";
		foreach ($arFiltri as $k => $v) { $out.="<option value='{$k}' ".(($k."x"==$def."x")?"selected":"").">{$v}</option>"; }
		return "<select onchange='aggiornaGriglia()' name='combocliente' id='combocliente'>{$out}</select>";
	}


	function getHtmlCercaBox($def="") {
		//------------------------------------------------
		return "<input type='text' name='keyword' id='keyword' value=\"{$def}\"/>";
	}

}

?>