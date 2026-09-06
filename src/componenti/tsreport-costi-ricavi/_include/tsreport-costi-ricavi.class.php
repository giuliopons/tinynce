<?php
/*
	gestione reportistica costi e ricavi
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

    // Soglia stato importi -> elenco di en_status inclusi (cumulativo).
    function statiInclusi($stato) {
        switch($stato) {
            case "payed":         return array("invoice payed");
            case "invoice":       return array("invoice emitted","invoice payed");
            case "progressclaim": return array("progress claim","invoice emitted","invoice payed");
            case "all":
            default:              return array("estimate","progress claim","invoice emitted","invoice payed");
        }
    }

    // Filtro condiviso per gli importi memorizzati (ts_costi/ts_ricavi):
    // cliente via job, job attivo/specifico, date su dt_payment, stato en_status.
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
        $stati = $this->statiInclusi(isset($dati['stato']) ? $dati['stato'] : "all");
        $w .= " and $ax.en_status IN ('".implode("','", $stati)."')";
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

    // Etichette tradotte degli stati (fallback quando de_label e' vuota).
    function labelStato($en_status) {
        $ar = array(
            "estimate"        => "{Estimate}",
            "progress claim"  => "{Progress claim}",
            "invoice emitted" => "{Invoice emitted}",
            "invoice payed"   => "{Invoice paid}",
        );
        return isset($ar[$en_status]) ? $ar[$en_status] : $en_status;
    }

    // Dettaglio delle voci (ricavi o costi) che compongono l'importo di una cella
    // del report std, per un job e un mese (YYYY-MM), rispettando il filtro stato.
    // $tabella = "ts_ricavi" | "ts_costi". Ritorna l'HTML delle righe (unite da "+").
    function dettaglioImporti($tabella, $id_job, $ym, $stato) {
        global $conn,$session;
        $id_job = (int)$id_job;
        if(!preg_match('/^(\d{4})-(\d{2})$/', $ym, $m)) return "";
        $anno = (int)$m[1];
        $mese = (int)$m[2];
        $isRicavi   = ($tabella=="ts_ricavi");
        $tabStorico = $isRicavi ? "ts_ricavi_storico" : "ts_costi_storico";
        $chiaveId   = $isRicavi ? "id_ricavo" : "id_costo";
        $chiaveFk   = $isRicavi ? "cd_ricavo" : "cd_costo";
        // matita di modifica per ogni voce: solo per admin/superadmin
        $metric  = $isRicavi ? "ric" : "forn";
        $canEdit = in_array($session->get("idprofilo"), array(20,999999));

        $stati = $this->statiInclusi($stato);
        $sql = "SELECT ".$chiaveId." AS id, de_label, en_status
            FROM ".DB_PREFIX.$tabella."
            WHERE cd_job='".$id_job."' AND YEAR(dt_payment)='".$anno."' AND MONTH(dt_payment)='".$mese."'
            AND en_status IN ('".implode("','", $stati)."')
            ORDER BY dt_payment, ".$chiaveId;
        $rs = $conn->query($sql) or die($conn->error." SQL = ".$sql);

        $voci = array();
        while($r = $rs->fetch_array()) {
            // label corrente della voce: de_label, altrimenti nome tradotto dello stato.
            // htmlspecialchars non tocca le graffe, quindi i placeholder {..} restano risolvibili.
            $label = ($r['de_label']!=="" && $r['de_label']!==null)
                ? htmlspecialchars($r['de_label'])
                : $this->labelStato($r['en_status']);
			$prelabel = "";
			if ($tabella=="ts_costi") {
				$prelabel = execute_scalar("SELECT de_nomefornitore FROM ".DB_PREFIX."ts_fornitori WHERE id_fornitore=(SELECT cd_fornitore FROM ".DB_PREFIX."ts_costi WHERE id_costo='".$r['id']."')","");
				if($prelabel!=="") $prelabel = $prelabel.": ";	
			}
			
            // per le fatture, mostro anche l'ultimo SAL (progress claim) dallo storico.
            // prendo l'ultimo progress claim con label valorizzata e DIVERSA da quella della
            // fattura (lo stato successivo): serve a saltare una label errata copiata dalla
            // fattura (es. "FT 75" scritto per sbaglio come SAL) e mostrare il SAL corretto
            // inserito dopo, evitando l'inutile "FT 75 -> FT 75".
            if($r['en_status']=="invoice emitted" || $r['en_status']=="invoice payed") {
                $escLabel = addslashes((string)$r['de_label']);
                $sqlSal = "SELECT de_label FROM ".DB_PREFIX.$tabStorico."
                    WHERE ".$chiaveFk."='".(int)$r['id']."' AND en_status='progress claim'
                    AND de_label IS NOT NULL AND TRIM(de_label)<>'' AND de_label<>'".$escLabel."'
                    ORDER BY dt_modifica DESC LIMIT 1";
                $sal = execute_scalar($sqlSal);
                if($sal!==null && trim($sal)!=="") {
                    $label = htmlspecialchars($sal)." &rarr; ".$label;
                }
            }
            // matita: apre il dialog di modifica del ricavo/costo
            if($canEdit) {
                $label .= " <span class='icon-pencil rcr-edit' data-metric='".$metric."' data-id='".(int)$r['id']."' title='{Edit}'></span>";
            }
            $voci[] = $prelabel.$label;
        }
        return $this->renderVoci($voci);
    }

    // Dettaglio dei reparti che hanno prodotto il costo personale di una cella
    // del report std, per un job e un mese (YYYY-MM). Ritorna l'HTML (voci unite da "+").
    function dettaglioPersonale($id_job, $ym) {
        global $conn;
        $id_job = (int)$id_job;
        if(!preg_match('/^(\d{4})-(\d{2})$/', $ym, $m)) return "";
        $anno = (int)$m[1];
        $mese = (int)$m[2];
        $sql = "SELECT DISTINCT rp.de_nomereparto AS nome
            FROM ".DB_PREFIX."ts_ore c
            LEFT JOIN ".DB_PREFIX."ts_reparti rp ON rp.id_reparto=c.cd_reparto_ora
            WHERE c.cd_job='".$id_job."' AND YEAR(c.dt_giorno)='".$anno."' AND MONTH(c.dt_giorno)='".$mese."'
            ORDER BY rp.de_nomereparto";
        $rs = $conn->query($sql) or die($conn->error." SQL = ".$sql);
        $voci = array();
        while($r = $rs->fetch_array()) {
            $nome = ($r['nome']!==null && trim($r['nome'])!=="") ? $r['nome'] : "-";
            $voci[] = htmlspecialchars($nome);
        }
        return $this->renderVoci($voci);
    }

    // Rende un elenco di voci come righe, con il simbolo "+" in coda a tutte tranne l'ultima.
    function renderVoci($voci) {
        if(count($voci)==0) return "<div class='rcr-pop-row'>{No data}</div>";
        $out = "";
        $n = count($voci);
        foreach($voci as $i=>$v) {
            $plus = ($i < $n-1) ? " <span class='rcr-pop-plus'>+</span>" : "";
            $out .= "<div class='rcr-pop-row'>".$v.$plus."</div>";
        }
        return $out;
    }

    // Costruisce il frammento-form (per il dialog) di un ricavo (metric=ric) o di un costo
    // (metric=forn). $id vuoto = inserimento (prefill job/mese); $id valorizzato = modifica
    // (valori letti dal record). Campi identici a tsricavi/tscosti (i costi hanno in piu' il
    // fornitore). Ritorna "0" se l'utente non e' admin/superadmin.
    function getFormImporto($metric, $id="", $job="", $ym="") {
        global $session;
        if(!in_array($session->get("idprofilo"), array(20,999999))) return "0";

        $isRicavi = ($metric=="ric");
        $tabella  = $isRicavi ? "ts_ricavi" : "ts_costi";
        $chiaveId = $isRicavi ? "id_ricavo" : "id_costo";

        $arStati = array(
            "estimate"=>"{Estimate}",
            "progress claim"=>"{Progress claim}",
            "invoice emitted"=>"{Invoice emitted}",
            "invoice payed"=>"{Invoice paid}"
        );

        if($id!=="" && (int)$id>0) {
            // modifica: leggo i valori correnti dal record
            $dati = execute_row("SELECT * FROM ".DB_PREFIX.$tabella." WHERE ".$chiaveId."='".(int)$id."'");
            if(empty($dati)) return "0";
            $recId      = $dati[$chiaveId];
            $vCdJob     = $dati["cd_job"];
            $vFornitore = $isRicavi ? "" : $dati["cd_fornitore"];
            $vImporto   = $dati["nu_importo"];
            $vPayment   = $dati["dt_payment"];
            $vStatus    = $dati["en_status"];
            $vLabel     = $dati["de_label"];
        } else {
            // inserimento: prefill job + primo giorno del mese cliccato
            $recId      = "";
            $vCdJob     = (int)$job>0 ? (int)$job : "";
            $vFornitore = "";
            $vImporto   = "";
            $vPayment   = preg_match('/^\d{4}-\d{2}$/', $ym) ? $ym."-01" : date("Y-m-d");
            $vStatus    = "estimate";
            $vLabel     = "";
        }

        //costruzione form (stessi campi/generatori di tsricavi/tscosti)
        // nome dedicato ("datircr"): il form filtri della pagina si chiama gia' "dati", quindi
        // un secondo form "dati" nel dialog romperebbe document.dati usato da checkForm().
        $objform = new form("datircr");
        // niente <script src=controlloform.js>: sul dialog romperebbe la re-iniezione di
        // moveCheckFormFunction; i validatori sono gia' caricati dal form filtri della pagina.
        $objform->pathJsLib = '';

        $cd_job = new autocomplete("cd_job",$vCdJob,100,60,"../tsjob/ajax/jobsearch.php");
        $cd_job->label="'Commessa'";
        $cd_job->obbligatorio=1;
        $objform->addControllo($cd_job);

        // campo fornitore solo per i costi
        $rigaFornitore = "";
        if(!$isRicavi) {
            $cd_fornitore = new optionlist("cd_fornitore",$vFornitore);
            $cd_fornitore->loadSqlOptions("select id_fornitore, de_nomefornitore from ".DB_PREFIX."ts_fornitori order by de_nomefornitore","id_fornitore","de_nomefornitore","{choose}");
            $cd_fornitore->label="'Fornitore'";
            $objform->addControllo($cd_fornitore);
            $rigaFornitore = "<tr><td valign='top'>{Supplier}</td><td>".$cd_fornitore->gettag()."</td></tr>";
        }

        $nu_importo = new numerodecimale("nu_importo",$vImporto,12,12,2);
        $nu_importo->obbligatorio=1;
        $nu_importo->attributes.=" style='text-align:right'";
        $nu_importo->label="'Importo'";
        $objform->addControllo($nu_importo);

        $dt_payment = new data("dt_payment",$vPayment,"aaaa-mm-gg",$objform->name);
        $objform->addControllo($dt_payment);

        $en_status = new optionlist("en_status",$vStatus,$arStati);
        $objform->addControllo($en_status);

        $de_label = new testo("de_label",$vLabel,50,50);
        $de_label->label="'Riferimento'";
        $objform->addControllo($de_label);

        $hid_id     = new hidden("id",$recId);
        $hid_op     = new hidden("op","save");
        $hid_metric = new hidden("metric",$metric);

        $html = loadTemplateAndParse("template/editform.html");
        $html = str_replace("##STARTFORM##", $objform->startform(), $html);
        $html = str_replace("##id##", $hid_id->gettag(), $html);
        $html = str_replace("##op##", $hid_op->gettag(), $html);
        $html = str_replace("##metric##", $hid_metric->gettag(), $html);
        $html = str_replace("##RIGA_FORNITORE##", $rigaFornitore, $html);
        $html = str_replace("##cd_job##", $cd_job->gettag(), $html);
        $html = str_replace("##nu_importo##", $nu_importo->gettag(), $html);
        $html = str_replace("##dt_payment##", $dt_payment->gettag(), $html);
        $html = str_replace("##en_status##", $en_status->gettag(), $html);
        $html = str_replace("##de_label##", $de_label->gettag(), $html);
        $html = str_replace("##ENDFORM##", $objform->endform(), $html);
        $html = str_replace("##MONEY##", MONEY, $html);
        return $html;
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

			// filtro stato importi (solo costi fornitori / ricavi)
			$stato = new optionlist("stato", isset($dati["stato"]) ? $dati["stato"] : "all", array(
				"all"           => "{All}",
				"progressclaim" => "{Progress claim}",
				"invoice"       => "{Invoice emitted}",
				"payed"         => "{Invoice paid}",
			));
			$stato->obbligatorio=0;
			$stato->label="'{Status}'";
            $stato->attributes=" class='filter'";
			$objform->addControllo($stato);


			$op = new hidden("op","cerca");

			$html = loadTemplateAndParse ("template/elenco.html");

			$html = str_replace("##STARTFORM##", $objform->startform(), $html);
			$html = str_replace("##op##", $op->gettag(), $html);
			$html = str_replace("##cliente##", $cliente->gettag(), $html);
			$html = str_replace("##job##", $job->gettag(), $html);
			$html = str_replace("##gruppo##", $gruppo->gettag(), $html);
			$html = str_replace("##stato##", $stato->gettag(), $html);
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
			$mapForn = $this->mappaImporti("ts_costi",  $dati, "cliente");
			$mapRic  = $this->mappaImporti("ts_ricavi", $dati, "cliente");

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
			$out="<thead><tr>";
			$out.="<th>{Client}</th>";
			$out.="<th class='n'>{Personnel cost}</th>";
			$out.="<th class='n'>{Supplier costs}</th>";
			$out.="<th class='n'>{Revenues}</th>";
			$out.="<th class='n delta'>{Delta}</th>";
			$out.="</tr></thead><tbody>";

			$csv="";
			$csv.='"'."{Client}".'"'.";";
			$csv.='"'."{Personnel cost}".'"'.";";
			$csv.='"'."{Supplier costs}".'"'.";";
			$csv.='"'."{Revenues}".'"'.";";
			$csv.='"'."{Delta}".'"'.";";
			$csv.="\n";
			$csv = translateHtml($csv);
			while($r=$rs->fetch_array()) {

				$costo_fornitori = isset($mapForn[$r['id_cliente']]) ? $mapForn[$r['id_cliente']] : 0;
				$ricavi          = isset($mapRic[$r['id_cliente']])  ? $mapRic[$r['id_cliente']]  : 0;

				$r = $this->removeDoubleQuotesFromArray($r);

				$out.="<tr>";
				$out.="<td>".$r['cliente']."</td>";
				$out.="<td class='n'>".$this->money($r['costo_personale'],0)."</td>";
				$out.="<td class='n'>".$this->money($costo_fornitori,0)."</td>";
				$out.="<td class='n'>".$this->money($ricavi,0)."</td>";
				$delta = $ricavi - $costo_fornitori - $r['costo_personale'];
				$out.="<td class='n delta ".($delta < 0 ? "neg" : "")."'>".$this->money($delta,0)."</td>";
				$out.="</tr>";

				$csv.='"'.$r['cliente'].'"'.";";
				$csv.='"'.numberf($r['costo_personale'],2).'"'.";";
				$csv.='"'.numberf($costo_fornitori,2).'"'.";";
				$csv.='"'.numberf($ricavi,2).'"'.";";
				$csv.='"'.numberf($ricavi - $costo_fornitori - $r['costo_personale'],2).'"'.";";
				$csv.="\n";


				$sommacosto_personale+= $r['costo_personale'];
				$sommacosto_fornitori+= $costo_fornitori;
				$sommaricavi+= $ricavi;
				$c++;
			}
			
			$out.="</tbody>";
			if($c>0) {
				$out.="<tfoot><tr>";

				$out.="<th class='n'>&nbsp;</th>";
				$out.="<th class='n'>".$this->money($sommacosto_personale,0)."</th>";
				$out.="<th class='n'>".$this->money($sommacosto_fornitori,0)."</th>";
				$out.="<th class='n'>".$this->money($sommaricavi,0)."</th>";
				$out.="<th class='n delta'>".$this->money($sommaricavi - $sommacosto_fornitori - $sommacosto_personale,0)."</th>";
				$out.="</tr></tfoot>";

				$csv.=";";
				$csv.='"'.numberf($sommacosto_personale,0).'"'.";";
				$csv.='"'.numberf($sommacosto_fornitori,0).'"'.";";
				$csv.='"'.numberf($sommaricavi,0).'"'.";";
				$csv.='"'.numberf($sommaricavi - $sommacosto_fornitori - $sommacosto_personale,0).'"'.";";
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
			$mapForn = $this->mappaImporti("ts_costi",  $dati, "job");
			$mapRic  = $this->mappaImporti("ts_ricavi", $dati, "job");

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
				$out="<thead><tr>";
				$out.="<th>{Code}</th>";
				$out.="<th>{Client}</th>";
				$out.="<th>{Job}</th>";
				$out.="<th class='n'>{Personnel cost}</th>";
				$out.="<th class='n'>{Supplier costs}</th>";
				$out.="<th class='n'>{Revenues}</th>";
				$out.="<th class='n delta'>{Delta}</th>";
				$out.="</tr></thead><tbody>";

				$csv="";
				$csv.='"'."{Code}".'"'.";";
				$csv.='"'."{Client}".'"'.";";
				$csv.='"'."{Job}".'"'.";";
				$csv.='"'."{Personnel cost}".'"'.";";
				$csv.='"'."{Supplier costs}".'"'.";";
				$csv.='"'."{Revenues}".'"'.";";
				$csv.='"'."{Delta}".'"'.";";
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
				$out.="<td class='n'>".$this->money($r['costo_personale'],2)."</td>";
				$out.="<td class='n'>".$this->money($costo_fornitori,2)."</td>";
				$out.="<td class='n'>".$this->money($ricavi,2)."</td>";
				$delta = $ricavi - $costo_fornitori - $r['costo_personale'];
				$out.="<td class='n delta ".($delta < 0 ? "neg" : "")."'>".$this->money($delta,2)."</td>";
				$out.="</tr>";


				$csv.='"'.$r['de_codice'].'"'.";";
				$csv.='"'.$r['cliente'].'"'.";";
				$csv.='"'.$r['commessa'].'"'.";";
				$csv.='"'.numberf($r['costo_personale'],2).'"'.";";
				$csv.='"'.numberf($costo_fornitori,2).'"'.";";
				$csv.='"'.numberf($ricavi,2).'"'.";";
				$csv.='"'.numberf($ricavi - $costo_fornitori - $r['costo_personale'],2).'"'.";";
				$csv.="\n";



				$sommacosto_personale+= $r['costo_personale'];
				$sommacosto_fornitori+= $costo_fornitori;
				$sommaricavi+= $ricavi;
				$c++;
			}
			$out.="</tbody>";
			if($c>0) {
				$out.="<tfoot><tr>";

				$out.="<th class='n' >&nbsp;</th>";
				$out.="<th class='n' >&nbsp;</th>";
				$out.="<th class='n' >&nbsp;</th>";
				$out.="<th class='n' >".$this->money($sommacosto_personale,0)."</th>";
				$out.="<th class='n' >".$this->money($sommacosto_fornitori,0)."</th>";
				$out.="<th class='n' >".$this->money($sommaricavi,0)."</th>";
				$out.="<th class='n delta' >".$this->money($sommaricavi - $sommacosto_fornitori - $sommacosto_personale,0)."</th>";
				$out.="</tr></tfoot>";

				$csv.=";";
				$csv.=";";
				$csv.=";";
				$csv.='"'.numberf($sommacosto_personale,0).'"'.";";
				$csv.='"'.numberf($sommacosto_fornitori,0).'"'.";";
				$csv.='"'.numberf($sommaricavi,0).'"'.";";
				$csv.='"'.numberf($sommaricavi - $sommacosto_fornitori - $sommacosto_personale,0).'"'.";";
				$csv.="\n";
				$sommaore = 0;
				$sommagiorni = 0;
				$sommaeuri = 0;
			}


		} elseif($dati['gruppo']=="std") {
			//
			// standard visualization x mesi
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

			// metriche mostrate (ordine fisso) con label breve; Delta = Ric - Forn - Pers
			$metrics = array('ric'=>"{Ric}", 'forn'=>"{Forn}", 'pers'=>"{Pers}", 'delta'=>"{Delta}");
			$nMetrics = count($metrics);

			// mappe importi memorizzati per mese (id_job => YYYY-MM => tot)
			$mapForn = $this->mappaImporti("ts_costi",  $dati, "mese");
			$mapRic  = $this->mappaImporti("ts_ricavi", $dati, "mese");

			// celle vuote cliccabili per l'inserimento: solo per admin/superadmin
			$canEdit = in_array($session->get("idprofilo"), array(20,999999));

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

			// header a due righe (mese in colspan, metriche sotto) + gruppo Totale
			$out ="<thead><tr>";
			$out.="<th rowspan='2'>{Code}</th>";
			$out.="<th rowspan='2'>{Client}</th>";
			$out.="<th rowspan='2'>{Job}</th>";
			foreach($mesi as $mese) $out.="<th class='n mese' colspan='".$nMetrics."'>".$mese['label']."</th>";
			$out.="<th class='n total' colspan='".$nMetrics."'>{Total}</th>";
			$out.="</tr>";
			$out.="<tr>";
			foreach($mesi as $mese) foreach($metrics as $mk=> $mlabel) {
				$out.="<th class='n ".$mk."'>".$mlabel."</th>";
			}
			foreach($metrics as $mk=> $mlabel) $out.="<th class='n total ".$mk."'>".$mlabel."</th>";
			$out.="</tr></thead><tbody>";

			$csv ="";
			$csv.='"'."{Code}".'"'.";";
			$csv.='"'."{Client}".'"'.";";
			$csv.='"'."{Job}".'"'.";";
			foreach($mesi as $mese) foreach($metrics as $mlabel) $csv.='"'.$mese['label'].' - '.$mlabel.'"'.";";
			foreach($metrics as $mlabel) $csv.='"'."{Total}".' - '.$mlabel.'"'.";";
			$csv.="\n";
			$csv = translateHtml($csv);

			while($r=$rs->fetch_array()) {

				// costo personale per mese (sub-query)
				$r['costo_personale'] = 0;
				foreach ($fieldsAr as $k=>$v) $fieldsAr[$k] = 0;

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

				$id_job = $r['id_job'];
				$r = $this->removeDoubleQuotesFromArray($r);

				$out.="<tr>";
				$out.="<td class='nw'>".$r['de_codice']."</td>";
				$out.="<td class='nw'>".$r['cliente']."</td>";
				$out.="<td class='nw'>".$r['commessa']."</td>";

				$csv.='"'.$r['de_codice'].'"'.";";
				$csv.='"'.$r['cliente'].'"'.";";
				$csv.='"'.$r['commessa'].'"'.";";

				$rowTot = array('ric'=>0,'forn'=>0,'pers'=>0,'delta'=>0);
				foreach($mesi as $mese) {
					$ym = $mese['key'];
					// valori base del mese (servono anche a calcolare il Delta e i totali di riga)
					$vRic  = isset($mapRic[$id_job][$ym])  ? (float)$mapRic[$id_job][$ym]  : 0;
					$vForn = isset($mapForn[$id_job][$ym]) ? (float)$mapForn[$id_job][$ym] : 0;
					$vPers = isset($fieldsAr[$ym])         ? (float)$fieldsAr[$ym]         : 0;
					$rowTot['ric'] += $vRic; $rowTot['forn'] += $vForn; $rowTot['pers'] += $vPers;
					foreach($metrics as $mk=>$mlabel) {
						if($mk=='pers')     $val = $vPers;
						elseif($mk=='forn') $val = $vForn;
						elseif($mk=='ric')  $val = $vRic;
						else                $val = $vRic - $vForn - $vPers; // delta
						// celle con importo != 0: cliccabili per il dettaglio (popup via ajax).
						// il Delta e' derivato: mai cliccabile.
						$cls = "n ".$mk;
						$data = "";
						if($mk!='delta' && round((float)$val,0) != 0) {
							$cls .= " rcr-cell";
							$data = " data-metric='".$mk."' data-job='".$id_job."' data-ym='".$ym."'"
								." data-stato='".htmlspecialchars(isset($dati['stato'])?$dati['stato']:"all")."'";
						} elseif($mk!='delta' && $canEdit && ($mk=='ric' || $mk=='forn')) {
							// cella vuota (ricavo/costo): cliccabile per inserire un nuovo record nel mese
							$cls .= " rcr-empty";
							$data = " data-metric='".$mk."' data-job='".$id_job."' data-ym='".$ym."'";
						}
						if ($mk=='delta' && $val < 0 ) $cls .= " neg";
						$out.="<td class='".$cls."'".$data.">".$this->money($val,0)."</td>";
						$csv.='"'.numberf($val,2).'"'.";";
					}
				}
				// colonna Totale di riga (somma sui mesi); non cliccabile
				$rowTot['delta'] = $rowTot['ric'] - $rowTot['forn'] - $rowTot['pers'];
				foreach($metrics as $mk=>$mlabel) {
					$out.="<td class='n total ".$mk."'>".$this->money($rowTot[$mk],0)."</td>";
					$csv.='"'.numberf($rowTot[$mk],2).'"'.";";
				}
				$out.="</tr>";
				$csv.="\n";

				$sommacosto_personale+= $r['costo_personale'];
				$c++;
			}

			$out.="</tbody>";

		} else {

			die("Selezionare un tipo di report");
		}

	
		if (isset($params["download_csv"]) && $params["download_csv"]==true) {
			$csv_converted = base64_encode(  mb_convert_encoding($csv, 'ISO-8859-1', 'UTF-8') );
			$csv="<a id='download-report' download='report-".$nomegruppo."-".date("Y-m-d").".csv' href=\"data:application/octet-stream;charset=utf-16le;base64,".$csv_converted."\" class=\"btn\">{Download CSV}</a>";
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