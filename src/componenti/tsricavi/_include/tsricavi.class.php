<?php
/*
	gestione ricavi dei job (timesheet)
*/
class Ricavi {
	var $tbdb;	//tabella del database che contiene i dati
	var $start;	// posizione del primo record visualizzato
	var $omode;	// asc|desc
	var $oby;	// campo della tabella $tbdb utilizzato per ordinare
	var $ps;	// numero di righe per pagina nell'elenco
	var $linkaggiungi;	//link utilizzato per "aggiungere"
	var $linkaggiungi_label;
	var $linkaggiungi_icon;
	var $linkmodifica;	//link utilizzato per il comando "modifica"
	var $linkmodifica_label;
	var $linkelimina;	//link utilizzato per il comando "elimina"
	var $linkelimina_label;
	var $linkeliminamarcate;	//link utilizzato per il comando "elimina" sui record checked
	var $linkeliminamarcate_label;
	var $gestore;
	var $arStati;	//valori possibili della enum en_status

	function __construct($tbdb="ts_ricavi",$ps=100,$oby="dt_payment",$omode="desc",$start=0) {
		global $session,$root,$conn;
		$this->gestore = $_SERVER["PHP_SELF"];
		$this->tbdb = $tbdb;
		//se ci sono impostazioni inviate in get o in post usa quelle
		//se non ci sono quelle usa quelle in session
		//se non ci sono neanche in session usa i valori passati.
		$this->start = setVariabile("gridStart",$start,$this->tbdb);
		$this->omode= setVariabile("gridOrderMode",$omode,$this->tbdb);
		$this->oby= setVariabile("gridOrderBy",$oby,$this->tbdb);
		$this->ps = setVariabile("gridPageSize",$ps,$this->tbdb);
		$this->linkaggiungi = "$this->gestore?op=aggiungi";
		$this->linkaggiungi_label = "{Add new item}";
		$this->linkmodifica = "$this->gestore?op=modifica&id=##id_ricavo##";
		$this->linkmodifica_label = "modifica";
		$this->linkeliminamarcate = "javascript:confermaDeleteCheck(document.datagrid);";
		//valori della enum en_status (chiave db => label tradotta)
		$this->arStati = array(
			"estimate"=>"{Estimate}",
			"progress claim"=>"{Progress claim}",
			"invoice emitted"=>"{Invoice emitted}",
			"invoice payed"=>"{Invoice paid}"
		);
		checkAbilitazione("TSRICAVI","TSRICAVI");

	}
	function elenco($dati) {
		global $session;
		$html = "";
		if (isset($dati["keyword"])) $keyword=$dati["keyword"]; else $keyword="";
		$cliente = isset($dati['combocliente']) ? $dati['combocliente'] : '';
		$job     = isset($dati['combojob'])     ? $dati['combojob']     : '';
		$dal     = isset($dati['dal'])          ? $dati['dal']          : '';
		$al      = isset($dati['al'])           ? $dati['al']           : '';
		if ($session->get("TSRICAVI")) {
			$t=new grid(DB_PREFIX.$this->tbdb,$this->start, $this->ps, $this->oby, $this->omode);
			$t->checkboxFormAction=$this->gestore;
			$t->checkboxFormName="datagrid";
			$t->checkboxForm=true;
			$t->functionhtml = ""; //"myhtmlspecialchars";
			$t->mostraRecordTotali=true;
			$t->parametriDaPssare = "";
			if($keyword) $t->parametriDaPssare.="&keyword=".urlencode($keyword);
			if($dal)             $t->parametriDaPssare.="&dal=".urlencode($dal);
			if($al)              $t->parametriDaPssare.="&al=".urlencode($al);
			if($cliente!=='')    $t->parametriDaPssare.="&combocliente=".urlencode($cliente);
			if($job!=='')        $t->parametriDaPssare.="&combojob=".urlencode($job);
			//campi da visualizzare
			$t->campi="job,nu_importo,dt_payment,en_status,de_label";
			//titoli dei campi da visualizzare
			$t->titoli="{Job},{Amount},{Payment date},{Status},{Reference}";
			//id per fare i link
			$t->chiave="id_ricavo";
			//query per estrarre i dati
			$t->query="select r.id_ricavo, concat(j.de_codice,' - ',j.de_nomejob) as job, r.nu_importo, r.dt_payment, r.en_status, r.de_label from ".DB_PREFIX."ts_ricavi r left join ".DB_PREFIX."ts_job j on r.cd_job=j.id_job #WHERE#";
			$where = "";
			if($keyword) {
				if($where!="") { $where.= " and "; }
				$where.=" (j.de_nomejob like '%$keyword%' or j.de_codice like '%$keyword%') ";
			}
			if($cliente!=='' && (int)$cliente>0) {
				if($where!="") { $where.= " and "; }
				$where.=" j.cd_cliente=".(int)$cliente." ";
			}
			if($job!=='' && (int)$job>0) {
				if($where!="") { $where.= " and "; }
				$where.=" r.cd_job=".(int)$job." ";
			}
			if(preg_match('/^\d{4}-\d{2}-\d{2}$/',$dal)) {
				if($where!="") { $where.= " and "; }
				$where.=" r.dt_payment>='".$dal."' ";
			}
			if(preg_match('/^\d{4}-\d{2}-\d{2}$/',$al)) {
				if($where!="") { $where.= " and "; }
				$where.=" r.dt_payment<='".$al."' ";
			}
			if($where) {
				$t->query = str_replace("#WHERE#"," where {$where}",$t->query);
			} else {
				$t->query = str_replace("#WHERE#","",$t->query);
			}

			$t->addCampi('job',"link",array("url"=>$this->linkmodifica));
			$t->addCampi('nu_importo',"numero");
			$t->addCampiDate('dt_payment',"dd/mm/yyyy");
			$t->addScegliDaInsieme('en_status',$this->arStati);
			$t->arFormattazioneTD=array("nu_importo"=>"numero");
			$texto = $t->show();
			if (trim($texto)=="") $texto="Nessun record trovato.";
			$html .= $texto."<br/>";
		} else {
			$html = "0";
		}
		return $html;
	}

	/*
		mostra il dettaglio.
		ritorna 0 se l'utente non e' abilitato, altrimenti restituisce l'html.
	*/
	function getDettaglio($id="") {
		global $session,$root;
		if ($session->get("TSRICAVI")) {
			if ($id!="") {
				/*
					modifica
				*/
				$dati = $this->getDati($id);
				if(empty($dati)) return "0";
				$action = "modificaStep2";
			} else {
				/*
					inserimento
				*/
				$dati = array("id_ricavo"=>"",
					"cd_job"=>"",
					"nu_importo"=>"",
					"dt_payment"=>"",
					"en_status"=>"estimate",
					"de_label"=>"");
				$action = "aggiungiStep2";
			}

			//costruzione form
			$objform = new form();

			$cd_job = new autocomplete("cd_job",$dati["cd_job"],100,60,"../tsjob/ajax/jobsearch.php");
			$cd_job->label="'Commessa'";
			$cd_job->obbligatorio=1;
			$objform->addControllo($cd_job);

			$nu_importo = new numerodecimale("nu_importo",($dati["nu_importo"]),12,12,2);
			$nu_importo->obbligatorio=1;
			$nu_importo->attributes.=" style='text-align:right'";
			$nu_importo->label="'Importo'";
			$objform->addControllo($nu_importo);

			$valore = $dati["dt_payment"];
			if ($valore=="") $valore = date("Y-m-d");
			$dt_payment = new data("dt_payment",$valore,"aaaa-mm-gg",$objform->name);
			$objform->addControllo($dt_payment);

			$en_status = new optionlist("en_status",$dati["en_status"],$this->arStati);
			$objform->addControllo($en_status);

			$de_label = new testo("de_label",$dati["de_label"],50,50);
			$de_label->label="'Riferimento'";
			$objform->addControllo($de_label);

			$id_ricavo = new hidden("id",$dati["id_ricavo"]);
			$op = new hidden("op",$action);

			//storico modifiche (solo in modifica)
			$storico = ($id!="") ? "<fieldset class='mainfieldset'><legend>{Change history}</legend>".$this->getStorico($id)."</fieldset>" : "";

			$html = loadTemplateAndParse("template/dettaglio.html");
			$html = str_replace("##STARTFORM##", $objform->startform(), $html);
			$html = str_replace("##id##", $id_ricavo->gettag(), $html);
			$html = str_replace("##op##", $op->gettag(), $html);
			$html = str_replace("##cd_job##", $cd_job->gettag(), $html);
			$html = str_replace("##nu_importo##", $nu_importo->gettag(), $html);
			$html = str_replace("##dt_payment##", $dt_payment->gettag(), $html);
			$html = str_replace("##en_status##", $en_status->gettag(), $html);
			$html = str_replace("##de_label##", $de_label->gettag(), $html);
			$html = str_replace("##STORICO_PANEL##", $storico, $html);
			$html = str_replace("##gestore##", $this->gestore, $html);
			$html = str_replace("##ENDFORM##", $objform->endform(), $html);
			$html = str_replace("##MONEY##", MONEY, $html);
		} else {
			$html = "0";
		}
		return $html;
	}
	function getDati($id) {
		return execute_row("SELECT * from ".DB_PREFIX."ts_ricavi where id_ricavo='{$id}'");
	}

	/*
		restituisce l'html del pannello con lo storico delle modifiche del ricavo.
	*/
	function getStorico($id) {
		$id = (int)$id;
		$t=new grid(DB_PREFIX."ts_ricavi_storico",0,50,"dt_modifica","desc","0","storico");
		$t->flagOrdinatori="off";
		$t->mostraRecordTotali=false;
		$t->checkboxForm=false;
		//campi da visualizzare
		$t->campi="dt_modifica,utente,dt_payment,nu_importo,en_status,de_label";
		//titoli dei campi da visualizzare
		$t->titoli="{Modified on},{Modified by},{Payment date},{Amount},{Status},{Reference}";
		//id per la chiave
		$t->chiave="id_storico";
		//query per estrarre i dati
		$t->query="select s.id_storico, s.dt_modifica, concat(u.nome,' ',u.cognome) as utente, s.dt_payment, s.nu_importo, s.en_status, s.de_label from ".DB_PREFIX."ts_ricavi_storico s left join ".DB_PREFIX."frw_utenti u on s.cd_utente=u.id where s.cd_ricavo='{$id}' order by s.dt_modifica desc";
		$t->addCampiDate('dt_modifica',"dd/mm/yyyy hh:ii");
		$t->addCampiDate('dt_payment',"dd/mm/yyyy");
		$t->addCampi('nu_importo',"numero");
		$t->addScegliDaInsieme('en_status',$this->arStati);
		$t->arFormattazioneTD=array("nu_importo"=>"numero");
		$texto = $t->show();
		if (trim($texto)=="") $texto="{No changes recorded.}";
		return $texto;
	}

	/*
		registra uno snapshot della versione corrente del ricavo nello storico.
		i parametri sono gia' escaped (addslashes) dal chiamante.
	*/
	function logStorico($cd_ricavo,$dt_payment,$nu_importo,$en_status,$de_label) {
		global $session,$conn;
		$cd_ricavo = (int)$cd_ricavo;
		$cd_utente = (int)$session->get("idutente");
		$sql="INSERT into ".DB_PREFIX."ts_ricavi_storico (cd_ricavo,cd_utente,dt_modifica,dt_payment,nu_importo,en_status,de_label) values('{$cd_ricavo}','{$cd_utente}',NOW(),'{$dt_payment}','{$nu_importo}','{$en_status}','{$de_label}')";
		$conn->query($sql) or (trigger_error($conn->error."<br>$sql='{$sql}'"));
	}

	function updateAndInsert($arDati) {
		// in:
		// arDati--> array POST del form
		// risultato:
		//	"" --> ok
		//  "0" --> il tuo profilo non ti consente l'inserimento/modifica
		global $session, $conn;
		if ($session->get("TSRICAVI")) {
			$cd_job		= (int)$arDati["cd_job"];
			$nu_importo	= addslashes($arDati["nu_importo"]);
			$dt_payment	= addslashes($arDati["dt_payment"]);
			$en_status	= addslashes($arDati["en_status"]);
			$de_label	= addslashes(substr($arDati["de_label"],0,50));
			if ($arDati["id"]!="") {
				/*
					Modifica
				*/
				$id = (int)$arDati["id"];
				//leggo lo stato corrente per capire se qualcosa e' cambiato
				$old = $this->getDati($id);
				$sql="UPDATE ".DB_PREFIX."ts_ricavi set cd_job='{$cd_job}', nu_importo='{$nu_importo}', dt_payment='{$dt_payment}', en_status='{$en_status}', de_label='{$de_label}' where id_ricavo='{$id}'";
				$conn->query($sql) or (trigger_error($conn->error."<br>$sql='{$sql}'"));
				//storico: registro solo se un campo tracciato e' cambiato
				if( !empty($old) && (
					$old["dt_payment"]        != $arDati["dt_payment"]
					|| (float)$old["nu_importo"] != (float)$arDati["nu_importo"]
					|| $old["en_status"]      != $arDati["en_status"]
					|| (string)$old["de_label"]  != (string)substr($arDati["de_label"],0,50)
				) ) {
					$this->logStorico($id,$dt_payment,$nu_importo,$en_status,$de_label);
				}
				$html= "";
			} else {
				/*
					Inserimento
				*/
				$sql="INSERT into ".DB_PREFIX."ts_ricavi (cd_job,dt_saved,dt_payment,en_status,nu_importo,de_label) values('{$cd_job}',NOW(),'{$dt_payment}','{$en_status}','{$nu_importo}','{$de_label}')";
				$conn->query($sql) or (trigger_error($conn->error."<br>$sql='{$sql}'"));
				//storico: prima versione del ricavo
				$id = (int)$conn->insert_id;
				$this->logStorico($id,$dt_payment,$nu_importo,$en_status,$de_label);
				$html= "";
			}
		} else {
			$html="0";		//il tuo profilo non ti consente l'inserimento
		}
		return $html;
	}

	function deleteItem($id) {
		// in:
		// id --> id ricavo da cancellare
		// result:
		//	"" --> ok
		//  "0" -->no permission
		global $session,$conn,$root;
		if ($session->get("TSRICAVI")) {

			$id = (int)$id;
			//cancello prima lo storico collegato, poi il ricavo
			$sqlst = "delete from ".DB_PREFIX."ts_ricavi_storico where cd_ricavo='{$id}'";
			$conn->query($sqlst) or (trigger_error($conn->error."<br>$sqlst='{$sqlst}'"));
			$sql = "delete from ".DB_PREFIX."ts_ricavi where id_ricavo='{$id}'";
			$conn->query($sql) or (trigger_error($conn->error."<br>$sql='{$sql}'"));

			$html = "";
		} else {
			$html="0";		//no permission
		}
		return $html;
	}
	function eliminaSelezionati($dati) {
		// in:
		// dati --> $_POST
		// result:
		//	"" --> ok
		//  "0" -->no permission
		//  "-2" -->connected items error
		global $session;
		if ($session->get("TSRICAVI")) {
			$html="0";
			$p=$dati['gridcheck'];
			for ($i=0;$i<count($p);$i++) {
				$out = $this->deleteItem($p[$i]);
				if($out != "") return "-2";
			}
			$html = "";
		} else {
			$html="0";		//no permission
		}
		return $html;
	}

	function getHtmlCercaBox($def="") {
		//------------------------------------------------
		return "<input type='text' name='keyword' id='keyword' value=\"{$def}\"/>";
	}

	/*
		tendina clienti per il filtro dell'elenco.
		onchange popola la tendina progetti (combojob) via ../tsreport/elencojob.php
	*/
	function getHtmlComboClienti($def="") {
		global $conn;
		$sql = "select id_cliente,de_nomecliente from ".DB_PREFIX."ts_clienti order by de_nomecliente";
		$rs = $conn->query($sql) or (trigger_error($conn->error."<br>$sql='{$sql}'"));
		$arFiltri = array(""=>"--{All}--");
		while($riga = $rs->fetch_array()) {
			$arFiltri[$riga['id_cliente']]=$riga['de_nomecliente'];
		}
		$out = "";
		foreach ($arFiltri as $k => $v) { $out.="<option value='{$k}' ".(($k."x"==$def."x")?"selected":"").">{$v}</option>"; }
		return "<select onchange='loadjobs(this)' name='combocliente' id='combocliente' class='filter'>{$out}</select>";
	}

	/*
		tendina progetti (job) per il filtro dell'elenco, popolata per il cliente selezionato.
		coerente con il formato di ../tsreport/elencojob.php (label = de_codice + ' ' + de_nomejob)
	*/
	function getHtmlComboJob($def="",$cliente="") {
		global $conn;
		$arFiltri = array(""=>"{All}");
		if($cliente!=='' && (int)$cliente>0) {
			$sql = "select id_job,de_codice,de_nomejob from ".DB_PREFIX."ts_job where cd_cliente='".(int)$cliente."' order by de_codice";
			$rs = $conn->query($sql) or (trigger_error($conn->error."<br>$sql='{$sql}'"));
			while($riga = $rs->fetch_array()) {
				$arFiltri[$riga['id_job']]=$riga['de_codice']." ".$riga['de_nomejob'];
			}
		}
		$out = "";
		foreach ($arFiltri as $k => $v) { $out.="<option value='{$k}' ".(($k."x"==$def."x")?"selected":"").">{$v}</option>"; }
		return "<select name='combojob' id='combojob' class='filter'>{$out}</select>";
	}
}
?>
