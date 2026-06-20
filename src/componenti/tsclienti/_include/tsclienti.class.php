<?php
/*
	gestione clienti dei timesheet
*/
class Clienti {
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
	var $arStati;
	function __construct($tbdb="ts_clienti",$ps=100,$oby="de_nomecliente",$omode="asc",$start=0) {
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
		$this->linkmodifica = "$this->gestore?op=modifica&id=##id_cliente##";
		$this->linkmodifica_label = "modifica";
		// $this->linkelimina = "javascript:confermaDelete('##id_cliente##');";
		// $this->linkelimina_label = "elimina";
		$this->linkeliminamarcate = "javascript:confermaDeleteCheck(document.datagrid);";
		checkAbilitazione("TSCLIENTI","TSCLIENTI");

	}
	function elenco($dati) {
		global $session;
		$html = "";
		//echo $dati["combostato"]."///";
		if (isset($dati["combocliente"])) $combocliente=$dati["combocliente"]; else $combocliente="";
		if (isset($dati["keyword"])) $keyword=$dati["keyword"]; else $keyword="";
		if ($session->get("TSCLIENTI")) {
			$t=new grid(DB_PREFIX.$this->tbdb,$this->start, $this->ps, $this->oby, $this->omode);
			$t->checkboxFormAction=$this->gestore;
			$t->checkboxFormName="datagrid";
			$t->checkboxForm=true;
			$t->functionhtml = ""; //"myhtmlspecialchars";
			$t->mostraRecordTotali=true;
			$t->parametriDaPssare = "";
			if($combocliente) $t->parametriDaPssare.="&combocliente=".urlencode($combocliente);
			if($keyword) $t->parametriDaPssare.="&keyword=".urlencode($keyword);
			//campi da visualizzare
			$t->campi="de_nomecliente,quanti";
			//titoli dei campi da visualizzare
			$t->titoli="{Client},{Jobs}";
			//id per fare i link
			$t->chiave="id_cliente";
			//query per estrarre i dati
			$t->query="select id_cliente,de_nomecliente, (select count(*) from ".DB_PREFIX."ts_job where cd_cliente=id_cliente) as quanti from ".DB_PREFIX."ts_clienti #WHERE# group by id_cliente,de_nomecliente";
			$where = "";
			if($combocliente) {
				if($combocliente==-999) {
				} else {
					if($where!="") { $where.= " and "; }
					$where.=" id_cliente='{$combocliente}'";
				}
			}
			if($keyword) {
				if($where!="") { $where.= " and "; }
				$where.=" de_nomecliente like '%$keyword%'";
			}
			if($where) {
				$t->query = str_replace("#WHERE#"," where {$where}",$t->query);
			} else {
				$t->query = str_replace("#WHERE#","",$t->query);
			}
			// $t->addCampi("quanti","numero");

			// $t->addComando($this->linkmodifica,$this->linkmodifica_label,"{Edit}");
			// $t->addComando($this->linkelimina,$this->linkelimina_label,"Elimina questo record");
			$t->addComando("../tsjob/index.php?combocliente=##id_cliente##&combostato=-999&comboanno=-999","icon-suitcase","{Show jobs for this client}");
			$t->addCampi('de_nomecliente',"link",array("url"=>$this->linkmodifica));
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
		ritorna 0 se l'utente non � abilitato, altrimenti restituisce l'html.
	*/
	function getDettaglio($id="") {
		global $session,$root;
		if ($session->get("TSCLIENTI")) {
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
				$dati = array("id_cliente"=>"",
					"de_nomecliente"=>"");
				$action = "aggiungiStep2";
			}

			//costruzione form
			$objform = new form();
		
			$de_nomecliente = new testo("de_nomecliente",($dati["de_nomecliente"]),50,50);
			$de_nomecliente->obbligatorio=1;
			$de_nomecliente->label="'Nome del cliente'";
			$objform->addControllo($de_nomecliente);

			$id_cliente = new hidden("id",$dati["id_cliente"]);
			$op = new hidden("op",$action);
			// $submit = new submit("invia","salva");

			$html = loadTemplateAndParse("template/dettaglio.html");
			$html = str_replace("##STARTFORM##", $objform->startform(), $html);
			$html = str_replace("##id##", $id_cliente->gettag(), $html);
			$html = str_replace("##op##", $op->gettag(), $html);
			//$html = str_replace("##submit##", $submit->gettagimage($root."images/salva.gif"," Salva"), $html);
			$html = str_replace("##de_nomecliente##", $de_nomecliente->gettag(), $html);
			$html = str_replace("##gestore##", $this->gestore, $html);
			$html = str_replace("##ENDFORM##", $objform->endform(), $html);
		} else {
			$html = "0";
		}
		return $html;
	}
	function getDati($id) {
		return execute_row("SELECT * from ".DB_PREFIX."ts_clienti where id_cliente='{$id}'");
	}

	function updateAndInsert($arDati) {
		// in:
		// arDati--> array POST del form
		// risultato:
		//	"" --> ok
		//	"1" --> nome gia' utilizzato da un altro componente
		//  "0" --> il tuo profilo non ti consente l'inserimento/modifica
		global $session, $conn;
		if ($session->get("TSCLIENTI")) {
			if ($arDati["id"]!="") {
				/*
					Modifica
				*/
				$sql="UPDATE ".DB_PREFIX."ts_clienti set de_nomecliente='##de_nomecliente##' where id_cliente='##id_cliente##'";
				//";
				$sql= str_replace("##de_nomecliente##",$arDati["de_nomecliente"],$sql);
				$sql= str_replace("##id_cliente##",$arDati["id"],$sql);
				$conn->query($sql) or (trigger_error($conn->error."<br>$sql='{$sql}'"));
				$numero = $arDati["id"];
				$html= "";
			} else {
				/*
					Inserimento
				*/
				$sql="INSERT into ".DB_PREFIX."ts_clienti (de_nomecliente) values('##de_nomecliente##')";
				$sql= str_replace("##de_nomecliente##",$arDati["de_nomecliente"],$sql);
				$conn->query($sql) or (trigger_error($conn->error."<br>$sql='{$sql}'"));
				$numero = $conn->insert_id;
				$html= "";
			}
		} else {
			$html="0";		//il tuo profilo non ti consente l'inserimento
		}
		return $html;
	}

	function deleteItem($id) {
		// in:
		// id --> id tipo da cancellare
		// result:
		//	"" --> ok
		//  "0" -->no permission
		// -2 connected items error
		global $session,$conn,$root;
		if ($session->get("TSCLIENTI")) {

			$jobs = concatenaId("select id_job from ".DB_PREFIX."ts_job where cd_cliente = '$id'");
			if($jobs<>"") {
				$sql = "delete from ".DB_PREFIX."ts_ore where cd_job in ($jobs)";
				$conn->query($sql) or (trigger_error($conn->error."<br>$sql='{$sql}'"));
				// $sql = "delete from ts_tbc_utenti_job where cd_job in ($jobs)";
				// $conn->query($sql) or (trigger_error($conn->error."<br>$sql='{$sql}'"));
				$sql = "delete from ".DB_PREFIX."ts_job where cd_cliente='$id'";
				$conn->query($sql) or (trigger_error($conn->error."<br>$sql='{$sql}'"));
				$sql = "delete from ".DB_PREFIX."ts_default_job_tipi where cd_job in ($jobs)";
				$conn->query($sql) or (trigger_error($conn->error."<br>$sql='{$sql}'"));
			}

			$sql = "delete from ".DB_PREFIX."ts_clienti where id_cliente='{$id}'";
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
		if ($session->get("TSCLIENTI")) {
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


	function getHtmlComboClienti($def="") {
		global $conn;
		//------------------------------------------------
		//combo filtri
		$sql = "select id_cliente,cd_cliente,de_nomecliente,count(*) as c from ".DB_PREFIX."ts_clienti left outer join ".DB_PREFIX."ts_job on id_cliente=cd_cliente group by id_cliente,cd_cliente,de_nomecliente order by de_nomecliente";
		$rs = $conn->query($sql) or (trigger_error($conn->error."<br>$sql='{$sql}'"));
		$arFiltri = array("-999"=>"-- scegli --");
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