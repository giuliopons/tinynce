<?php
/*
	gestione costi dei job (timesheet)
*/
class Costi {
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

	function __construct($tbdb="ts_costi",$ps=100,$oby="dt_payment",$omode="desc",$start=0) {
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
		$this->linkmodifica = "$this->gestore?op=modifica&id=##id_costo##";
		$this->linkmodifica_label = "modifica";
		$this->linkeliminamarcate = "javascript:confermaDeleteCheck(document.datagrid);";
		//valori della enum en_status (chiave db => label tradotta)
		$this->arStati = array(
			"estimate"=>"{Estimate}",
			"progress claim"=>"{Progress claim}",
			"invoice emitted"=>"{Invoice emitted}",
			"invoice payed"=>"{Invoice paid}"
		);
		checkAbilitazione("TSCOSTI","TSCOSTI");

	}
	function elenco($dati) {
		global $session;
		$html = "";
		if (isset($dati["keyword"])) $keyword=$dati["keyword"]; else $keyword="";
		if ($session->get("TSCOSTI")) {
			$t=new grid(DB_PREFIX.$this->tbdb,$this->start, $this->ps, $this->oby, $this->omode);
			$t->checkboxFormAction=$this->gestore;
			$t->checkboxFormName="datagrid";
			$t->checkboxForm=true;
			$t->functionhtml = ""; //"myhtmlspecialchars";
			$t->mostraRecordTotali=true;
			$t->parametriDaPssare = "";
			if($keyword) $t->parametriDaPssare.="&keyword=".urlencode($keyword);
			//campi da visualizzare
			$t->campi="job,de_nomefornitore,nu_importo,dt_payment,en_status";
			//titoli dei campi da visualizzare
			$t->titoli="{Job},{Supplier},{Amount},{Payment date},{Status}";
			//id per fare i link
			$t->chiave="id_costo";
			//query per estrarre i dati
			$t->query="select c.id_costo, concat(j.de_codice,' - ',j.de_nomejob) as job, f.de_nomefornitore, c.nu_importo, c.dt_payment, c.en_status from ".DB_PREFIX."ts_costi c left join ".DB_PREFIX."ts_job j on c.cd_job=j.id_job left join ".DB_PREFIX."ts_fornitori f on c.cd_fornitore=f.id_fornitore #WHERE#";
			$where = "";
			if($keyword) {
				if($where!="") { $where.= " and "; }
				$where.=" (j.de_nomejob like '%$keyword%' or j.de_codice like '%$keyword%' or f.de_nomefornitore like '%$keyword%') ";
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
		if ($session->get("TSCOSTI")) {
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
				$dati = array("id_costo"=>"",
					"cd_job"=>"",
					"cd_fornitore"=>"",
					"nu_importo"=>"",
					"dt_payment"=>"",
					"en_status"=>"estimate");
				$action = "aggiungiStep2";
			}

			//costruzione form
			$objform = new form();

			$cd_job = new autocomplete("cd_job",$dati["cd_job"],100,60,"../tsjob/ajax/jobsearch.php");
			$cd_job->label="'Commessa'";
			$cd_job->obbligatorio=1;
			$objform->addControllo($cd_job);

			$cd_fornitore = new optionlist("cd_fornitore",$dati["cd_fornitore"]);
			$cd_fornitore->loadSqlOptions("select id_fornitore, de_nomefornitore from ".DB_PREFIX."ts_fornitori order by de_nomefornitore","id_fornitore","de_nomefornitore","{choose}");
			$cd_fornitore->label="'Fornitore'";
			$objform->addControllo($cd_fornitore);

			$nu_importo = new numerodecimale("nu_importo",($dati["nu_importo"]),12,12,2);
			$nu_importo->obbligatorio=1;
			$nu_importo->label="'Importo'";
			$objform->addControllo($nu_importo);

			$valore = $dati["dt_payment"];
			if ($valore=="") $valore = date("Y-m-d");
			$dt_payment = new data("dt_payment",$valore,"aaaa-mm-gg",$objform->name);
			$objform->addControllo($dt_payment);

			$en_status = new optionlist("en_status",$dati["en_status"],$this->arStati);
			$objform->addControllo($en_status);

			$id_costo = new hidden("id",$dati["id_costo"]);
			$op = new hidden("op",$action);

			$html = loadTemplateAndParse("template/dettaglio.html");
			$html = str_replace("##STARTFORM##", $objform->startform(), $html);
			$html = str_replace("##id##", $id_costo->gettag(), $html);
			$html = str_replace("##op##", $op->gettag(), $html);
			$html = str_replace("##cd_job##", $cd_job->gettag(), $html);
			$html = str_replace("##cd_fornitore##", $cd_fornitore->gettag(), $html);
			$html = str_replace("##nu_importo##", $nu_importo->gettag(), $html);
			$html = str_replace("##dt_payment##", $dt_payment->gettag(), $html);
			$html = str_replace("##en_status##", $en_status->gettag(), $html);
			$html = str_replace("##gestore##", $this->gestore, $html);
			$html = str_replace("##ENDFORM##", $objform->endform(), $html);
			$html = str_replace("##MONEY##", MONEY, $html);
		} else {
			$html = "0";
		}
		return $html;
	}
	function getDati($id) {
		return execute_row("SELECT * from ".DB_PREFIX."ts_costi where id_costo='{$id}'");
	}

	function updateAndInsert($arDati) {
		// in:
		// arDati--> array POST del form
		// risultato:
		//	"" --> ok
		//  "0" --> il tuo profilo non ti consente l'inserimento/modifica
		global $session, $conn;
		if ($session->get("TSCOSTI")) {
			$cd_job			= (int)$arDati["cd_job"];
			$cd_fornitore	= (int)$arDati["cd_fornitore"];
			$nu_importo		= addslashes($arDati["nu_importo"]);
			$dt_payment		= addslashes($arDati["dt_payment"]);
			$en_status		= addslashes($arDati["en_status"]);
			if ($arDati["id"]!="") {
				/*
					Modifica
				*/
				$id = (int)$arDati["id"];
				$sql="UPDATE ".DB_PREFIX."ts_costi set cd_job='{$cd_job}', cd_fornitore='{$cd_fornitore}', nu_importo='{$nu_importo}', dt_payment='{$dt_payment}', en_status='{$en_status}' where id_costo='{$id}'";
				$conn->query($sql) or (trigger_error($conn->error."<br>$sql='{$sql}'"));
				$html= "";
			} else {
				/*
					Inserimento
				*/
				$sql="INSERT into ".DB_PREFIX."ts_costi (cd_job,dt_saved,dt_payment,en_status,cd_fornitore,nu_importo) values('{$cd_job}',NOW(),'{$dt_payment}','{$en_status}','{$cd_fornitore}','{$nu_importo}')";
				$conn->query($sql) or (trigger_error($conn->error."<br>$sql='{$sql}'"));
				$html= "";
			}
		} else {
			$html="0";		//il tuo profilo non ti consente l'inserimento
		}
		return $html;
	}

	function deleteItem($id) {
		// in:
		// id --> id costo da cancellare
		// result:
		//	"" --> ok
		//  "0" -->no permission
		global $session,$conn,$root;
		if ($session->get("TSCOSTI")) {

			$id = (int)$id;
			$sql = "delete from ".DB_PREFIX."ts_costi where id_costo='{$id}'";
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
		if ($session->get("TSCOSTI")) {
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
}
?>
