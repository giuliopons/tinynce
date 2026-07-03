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

    // Formattazione importo per l'output HTML: se il valore arrotondato e' zero
    // mostra un "-" grigio invece di "0€", per alleggerire la tabella.
    function money($val, $dec=0) {
        if(round((float)$val, $dec) == 0) return "<span style='color:#bbb'>-</span>";
        return numberf($val,$dec)."<span style='color:#bbb'>".MONEY."</span>";
    }

    // Colonne selezionate: alla prima apertura (op non impostato) tutte attive;
    // in ricerca valgono solo le checkbox effettivamente inviate. Fallback: personale.
    function colonneSelezionate($dati) {
        $firstLoad = !isset($dati['op']);
        $col = array(
            'pers' => $firstLoad ? true : ((isset($dati['col_pers']) && $dati['col_pers']=='1')),
            'forn' => $firstLoad ? true : ((isset($dati['col_forn']) && $dati['col_forn']=='1')),
            'ric'  => $firstLoad ? true : ((isset($dati['col_ric'])  && $dati['col_ric'] =='1')),
        );
        if(!$col['pers'] && !$col['forn'] && !$col['ric']) $col['pers'] = true;
        return $col;
    }

    // Filtro condiviso per gli importi memorizzati (ts_costi/ts_ricavi):
    // cliente via job, job attivo/specifico, date su dt_payment.
    // $ax = alias tabella importi, $aj = alias ts_job.
    function filtroImporti($dati, $ax, $aj) {
        $w = "";
        if(isset($dati['cliente']) && $dati['cliente']!='') $w .= " and $aj.cd_cliente='".$dati['cliente']."'";
        if($dati['job']=="-1")      $w .= " and $aj.fl_attivo='0'";
        elseif($dati['job']=="-2")  $w .= " and $aj.fl_attivo='1'";
        elseif($dati['job']=="")    { /* tutti i job */ }
        else                        $w .= " and $aj.id_job='".$dati['job']."'";
        if(isset($dati['dal']) && $dati['dal']) $w .= " and $ax.dt_payment>='".$dati['dal']."'";
        if(isset($dati['al'])  && $dati['al'])  $w .= " and $ax.dt_payment<='".$dati['al']."'";
        return $w;
    }

    // Mappa di lookup degli importi aggregati.
    // $tabella = "ts_costi" | "ts_ricavi"; $mode = "cliente" | "job" | "mese".
    // Ritorna: cliente/job => array[key=>tot]; mese => array[id_job][YYYY-MM]=>tot.
    function mappaImporti($tabella, $dati, $mode) {
        global $conn;
        $filtro = $this->filtroImporti($dati, "x", "j");
        $map = array();
        if($mode=="cliente") {
            $sql = "SELECT j.cd_cliente AS k, SUM(x.nu_importo) AS tot
                FROM ".DB_PREFIX.$tabella." x INNER JOIN ".DB_PREFIX."ts_job j ON x.cd_job=j.id_job
                WHERE 1=1 ".$filtro." GROUP BY j.cd_cliente";
            $rs = $conn->query($sql) or die($conn->error." SQL = ".$sql);
            while($r=$rs->fetch_array()) $map[$r['k']] = $r['tot'];
        } elseif($mode=="job") {
            $sql = "SELECT x.cd_job AS k, SUM(x.nu_importo) AS tot
                FROM ".DB_PREFIX.$tabella." x INNER JOIN ".DB_PREFIX."ts_job j ON x.cd_job=j.id_job
                WHERE 1=1 ".$filtro." GROUP BY x.cd_job";
            $rs = $conn->query($sql) or die($conn->error." SQL = ".$sql);
            while($r=$rs->fetch_array()) $map[$r['k']] = $r['tot'];
        } elseif($mode=="mese") {
            $sql = "SELECT x.cd_job AS j,
                    CONCAT(YEAR(x.dt_payment),'-',LPAD(MONTH(x.dt_payment),2,'00')) AS m,
                    SUM(x.nu_importo) AS tot
                FROM ".DB_PREFIX.$tabella." x INNER JOIN ".DB_PREFIX."ts_job j ON x.cd_job=j.id_job
                WHERE 1=1 ".$filtro." GROUP BY x.cd_job, m";
            $rs = $conn->query($sql) or die($conn->error." SQL = ".$sql);
            while($r=$rs->fetch_array()) $map[$r['j']][$r['m']] = $r['tot'];
        }
        return $map;
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

			//------------------------------------------------
			// checkbox selezione colonne (costo personale / fornitori / ricavi)
			$col = $this->colonneSelezionate($dati);
			$colonne  = "<label style='margin-right:1rem;white-space:nowrap'><input type='checkbox' name='col_pers' value='1' ".($col['pers']?"checked":"")."/> {Personnel cost}</label>";
			$colonne .= "<label style='margin-right:1rem;white-space:nowrap'><input type='checkbox' name='col_forn' value='1' ".($col['forn']?"checked":"")."/> {Supplier costs}</label>";
			$colonne .= "<label style='margin-right:1rem;white-space:nowrap'><input type='checkbox' name='col_ric' value='1' ".($col['ric']?"checked":"")."/> {Revenues}</label>";

			$html = loadTemplateAndParse ("template/elenco.html");

			$html = str_replace("##STARTFORM##", $objform->startform(), $html);
			$html = str_replace("##op##", $op->gettag(), $html);
			$html = str_replace("##cliente##", $cliente->gettag(), $html);
			$html = str_replace("##job##", $job->gettag(), $html);
			$html = str_replace("##gruppo##", $gruppo->gettag(), $html);
			$html = str_replace("##colonne##", $colonne, $html);
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

		// colonne selezionate (costo personale / fornitori / ricavi)
		$col = $this->colonneSelezionate($dati);

		$nomegruppo = "";
		if($dati['gruppo']=="cd_cliente") {
			//
			// visualization by client
			//
			$nomegruppo = "cliente";
			$sql="SELECT SUM(c.nu_ore) as ore, SUM(c.nu_ore/8) as giornate, e.de_nomecliente as cliente , e.id_cliente as id_cliente ,
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

			// mappe importi memorizzati (per id_cliente)
			$mapForn = $col['forn'] ? $this->mappaImporti("ts_costi",  $dati, "cliente") : array();
			$mapRic  = $col['ric']  ? $this->mappaImporti("ts_ricavi", $dati, "cliente") : array();

			$rs = $conn->query($sql) or die($conn->error." SQL = ".$sql);
			$out = "";
			//id_cliente 	de_nomecliente 	id_job 	de_codice 	de_nomejob 	dt_inizio 	dt_fine 	cd_cliente 	id_ora 	cd_utente 	de_nota 	cd_job 	nu_ore 	dt_giorno 	id 	username 	password 	nome 	cognome 	fl_attivo 	cd_profilo

			//$job = "";
			$sommaore = 0;
			$sommacosto_personale = 0;
			$sommacosto_fornitori = 0;
			$sommaricavi = 0;
			$sommagiornate = 0;


			$header = "";
			$c = 0;
			$sommatutteore = 0;
				$out="<tr>";
				$out.="<th>{Client}</th>";
				if($col['pers']) $out.="<th class='n'>{Personnel cost}</th>";
				if($col['forn']) $out.="<th class='n'>{Supplier costs}</th>";
				if($col['ric'])  $out.="<th class='n'>{Revenues}</th>";
				$out.="</tr>";

				$csv="";
				$csv.='"'."{Client}".'"'.";";
				if($col['pers']) $csv.='"'."{Personnel cost}".'"'.";";
				if($col['forn']) $csv.='"'."{Supplier costs}".'"'.";";
				if($col['ric'])  $csv.='"'."{Revenues}".'"'.";";
				$csv.="\n";
				$csv = translateHtml($csv);
			while($r=$rs->fetch_array()) {

				$costo_fornitori = isset($mapForn[$r['id_cliente']]) ? $mapForn[$r['id_cliente']] : 0;
				$ricavi          = isset($mapRic[$r['id_cliente']])  ? $mapRic[$r['id_cliente']]  : 0;

				$r = $this->removeDoubleQuotesFromArray($r);

				$out.="<tr>";
				$out.="<td>".$r['cliente']."</td>";
				if($col['pers']) $out.="<td class='n'>".$this->money($r['costo_personale'],0)."</td>";
				if($col['forn']) $out.="<td class='n'>".$this->money($costo_fornitori,0)."</td>";
				if($col['ric'])  $out.="<td class='n'>".$this->money($ricavi,0)."</td>";
				$out.="</tr>";

				$csv.='"'.$r['cliente'].'"'.";";
				if($col['pers']) $csv.='"'.numberf($r['costo_personale'],2).'"'.";";
				if($col['forn']) $csv.='"'.numberf($costo_fornitori,2).'"'.";";
				if($col['ric'])  $csv.='"'.numberf($ricavi,2).'"'.";";
				$csv.="\n";


				$sommacosto_personale+= $r['costo_personale'];
				$sommacosto_fornitori+= $costo_fornitori;
				$sommaricavi+= $ricavi;
				$c++;
			}
			if($c>0) {
				$out.="<tr>";

				$out.="<th class='n'>&nbsp;</th>";
				if($col['pers']) $out.="<th class='n'>".$this->money($sommacosto_personale,0)."</th>";
				if($col['forn']) $out.="<th class='n'>".$this->money($sommacosto_fornitori,0)."</th>";
				if($col['ric'])  $out.="<th class='n'>".$this->money($sommaricavi,0)."</th>";
				$out.="</tr>";

				$csv.=";";
				if($col['pers']) $csv.='"'.numberf($sommacosto_personale,0).'"'.";";
				if($col['forn']) $csv.='"'.numberf($sommacosto_fornitori,0).'"'.";";
				if($col['ric'])  $csv.='"'.numberf($sommaricavi,0).'"'.";";
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

			// mappe importi memorizzati (per id_job)
			$mapForn = $col['forn'] ? $this->mappaImporti("ts_costi",  $dati, "job") : array();
			$mapRic  = $col['ric']  ? $this->mappaImporti("ts_ricavi", $dati, "job") : array();

			$rs = $conn->query($sql) or trigger_error($conn->error." ".$sql);
			$out = "";
			//id_cliente 	de_nomecliente 	id_job 	de_codice 	de_nomejob 	dt_inizio 	dt_fine 	cd_cliente 	id_ora 	cd_utente 	de_nota 	cd_job 	nu_ore 	dt_giorno 	id 	username 	password 	nome 	cognome 	fl_attivo 	cd_profilo

			//$job = "";
			$sommaore = 0;
			$sommacosto_personale = 0;
			$sommacosto_fornitori = 0;
			$sommaricavi = 0;
			$sommagiornate = 0;

			$header = "";
			$c = 0;
			$sommatutteore = 0;
				$out="<tr>";
				$out.="<th>{Code}</th>";
				$out.="<th>{Client}</th>";
				$out.="<th>{Job}</th>";
				if($col['pers']) $out.="<th class='n'>{Personnel cost}</th>";
				if($col['forn']) $out.="<th class='n'>{Supplier costs}</th>";
				if($col['ric'])  $out.="<th class='n'>{Revenues}</th>";
				$out.="</tr>";

				$csv="";
				$csv.='"'."{Code}".'"'.";";
				$csv.='"'."{Client}".'"'.";";
				$csv.='"'."{Job}".'"'.";";
				if($col['pers']) $csv.='"'."{Personnel cost}".'"'.";";
				if($col['forn']) $csv.='"'."{Supplier costs}".'"'.";";
				if($col['ric'])  $csv.='"'."{Revenues}".'"'.";";
				$csv.="\n";
				$csv = translateHtml($csv);
			while($r=$rs->fetch_array()) {

				$costo_fornitori = isset($mapForn[$r['id_job']]) ? $mapForn[$r['id_job']] : 0;
				$ricavi          = isset($mapRic[$r['id_job']])  ? $mapRic[$r['id_job']]  : 0;

				$r = $this->removeDoubleQuotesFromArray($r);

				$out.="<tr>";
				$out.="<td style='white-space:nowrap'>".$r['de_codice']."</td>";
				$out.="<td>".$r['cliente']."</td>";
				$out.="<td>".$r['commessa']."</td>";
				if($col['pers']) $out.="<td class='n'>".$this->money($r['costo_personale'],2)."</td>";
				if($col['forn']) $out.="<td class='n'>".$this->money($costo_fornitori,2)."</td>";
				if($col['ric'])  $out.="<td class='n'>".$this->money($ricavi,2)."</td>";
				$out.="</tr>";


				$csv.='"'.$r['de_codice'].'"'.";";
				$csv.='"'.$r['cliente'].'"'.";";
				$csv.='"'.$r['commessa'].'"'.";";
				if($col['pers']) $csv.='"'.numberf($r['costo_personale'],2).'"'.";";
				if($col['forn']) $csv.='"'.numberf($costo_fornitori,2).'"'.";";
				if($col['ric'])  $csv.='"'.numberf($ricavi,2).'"'.";";
				$csv.="\n";



				$sommacosto_personale+= $r['costo_personale'];
				$sommacosto_fornitori+= $costo_fornitori;
				$sommaricavi+= $ricavi;
				$c++;
			}
			if($c>0) {
				$out.="<tr>";

				$out.="<th class='n' >&nbsp;</th>";
				$out.="<th class='n' >&nbsp;</th>";
				$out.="<th class='n' >&nbsp;</th>";
				if($col['pers']) $out.="<th class='n' >".$this->money($sommacosto_personale,0)."</th>";
				if($col['forn']) $out.="<th class='n' >".$this->money($sommacosto_fornitori,0)."</th>";
				if($col['ric'])  $out.="<th class='n' >".$this->money($sommaricavi,0)."</th>";
				$out.="</tr>";

				$csv.=";";
				$csv.=";";
				$csv.=";";
				if($col['pers']) $csv.='"'.numberf($sommacosto_personale,0).'"'.";";
				if($col['forn']) $csv.='"'.numberf($sommacosto_fornitori,0).'"'.";";
				if($col['ric'])  $csv.='"'.numberf($sommaricavi,0).'"'.";";
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
			$d0 = strtotime($dati['dal']);
			$year = date("Y", $d0);

			// metriche selezionate (ordine fisso) con label breve
			$metrics = array();
			if($col['pers']) $metrics['pers'] = "{Pers}";
			if($col['forn']) $metrics['forn'] = "{Forn}";
			if($col['ric'])  $metrics['ric']  = "{Ric}";
			$nMetrics = count($metrics);

			// mappe importi memorizzati per mese (id_job => YYYY-MM => tot)
			$mapForn = $col['forn'] ? $this->mappaImporti("ts_costi",  $dati, "mese") : array();
			$mapRic  = $col['ric']  ? $this->mappaImporti("ts_ricavi", $dati, "mese") : array();

			// elenco mesi ordinati
			$mesi = array();
			$fieldsAr = array();
			for ($i = 0; $i <= $totalMonths; $i++) {
				$dn  = 24*60*60* 31*$i + $d0;
				$label = date("F", $dn);
				if(date("m", $dn) == 1) $year = date("Y", $dn);
				$ym = date("Y", $dn)."-".date("m", $dn);
				$mesi[] = array("key"=>$ym, "label"=>"{".$label."} ".$year);
				$fieldsAr[$ym] = "0";
				$year = "";
			}

			if($nMetrics <= 1) {
				// una sola metrica: header a riga singola (come in precedenza)
				$out ="<tr>";
				$out.="<th>{Code}</th>";
				$out.="<th>{Client}</th>";
				$out.="<th>{Job}</th>";
				foreach($mesi as $mese) $out.="<th class='n'>".$mese['label']."</th>";
				$out.="</tr>";

				$csv ="";
				$csv.='"'."{Code}".'"'.";";
				$csv.='"'."{Client}".'"'.";";
				$csv.='"'."{Job}".'"'.";";
				foreach($mesi as $mese) $csv.='"'.$mese['label'].'"'.";";
				$csv.="\n";
			} else {
				// piu metriche: header a due righe (mese in colspan, metriche sotto)
				$out ="<tr>";
				$out.="<th rowspan='2'>{Code}</th>";
				$out.="<th rowspan='2'>{Client}</th>";
				$out.="<th rowspan='2'>{Job}</th>";
				foreach($mesi as $mese) $out.="<th class='n' colspan='".$nMetrics."'>".$mese['label']."</th>";
				$out.="</tr>";
				$out.="<tr>";
				foreach($mesi as $mese) foreach($metrics as $mlabel) $out.="<th class='n'>".$mlabel."</th>";
				$out.="</tr>";

				$csv ="";
				$csv.='"'."{Code}".'"'.";";
				$csv.='"'."{Client}".'"'.";";
				$csv.='"'."{Job}".'"'.";";
				foreach($mesi as $mese) foreach($metrics as $mlabel) $csv.='"'.$mese['label'].' - '.$mlabel.'"'.";";
				$csv.="\n";
			}
			$csv = translateHtml($csv);

			while($r=$rs->fetch_array()) {

				// costo personale per mese (sub-query) solo se la metrica e selezionata
				$r['costo_personale'] = 0;
				foreach ($fieldsAr as $k=>$v) $fieldsAr[$k] = 0;

				if($col['pers']) {
					$altriwhere = "";
					if($dati['dal']) $altriwhere.=" and c.dt_giorno>='".$dati['dal']."' ";
					if($dati['al'])  $altriwhere.=" and c.dt_giorno<='".$dati['al']."' ";

					$sqlm = "SELECT CONCAT(YEAR(c.dt_giorno),'-',LPAD(MONTH(c.dt_giorno),2,'00')) as m,
						SUM(CASE WHEN AC.nu_cost IS NOT NULL
							THEN AC.nu_cost*c.nu_ore
							ELSE u.nu_costo*c.nu_ore
						END) AS costo_personale
						FROM ".DB_PREFIX."ts_ore c
						inner join ".DB_PREFIX."frw_extrauserdata u on u.cd_user = c.cd_utente
						LEFT OUTER JOIN ".DB_PREFIX."ts_users_annual_cost AC on AC.cd_user=c.cd_utente and AC.nu_anno=YEAR(c.dt_giorno)
						WHERE cd_job = '{$r['id_job']}' ".$altriwhere." group by m";

					$rsm = $conn->query($sqlm) or die($conn->error);
					while($rm = $rsm->fetch_array()) {
						$r['costo_personale'] += $rm['costo_personale'];
						if(isset($fieldsAr[$rm['m']])) $fieldsAr[$rm['m']] = $rm['costo_personale'];
					}
				}

				$id_job = $r['id_job'];
				$r = $this->removeDoubleQuotesFromArray($r);

				$out.="<tr>";
				$out.="<td style='white-space:nowrap'>".$r['de_codice']."</td>";
				$out.="<td>".$r['cliente']."</td>";
				$out.="<td>".$r['commessa']."</td>";

				$csv.='"'.$r['de_codice'].'"'.";";
				$csv.='"'.$r['cliente'].'"'.";";
				$csv.='"'.$r['commessa'].'"'.";";

				foreach($mesi as $mese) {
					$ym = $mese['key'];
					foreach($metrics as $mk=>$mlabel) {
						if($mk=='pers')     $val = isset($fieldsAr[$ym]) ? $fieldsAr[$ym] : 0;
						elseif($mk=='forn') $val = isset($mapForn[$id_job][$ym]) ? $mapForn[$id_job][$ym] : 0;
						else                $val = isset($mapRic[$id_job][$ym])  ? $mapRic[$id_job][$ym]  : 0;
						$out.="<td class='n'>".$this->money($val,0)."</td>";
						$csv.='"'.numberf($val,2).'"'.";";
					}
				}
				$out.="</tr>";
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