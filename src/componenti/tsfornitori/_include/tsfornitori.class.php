<?php
/*
	gestione fornitori dei timesheet
*/
class Fornitori {
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
	function __construct($tbdb="ts_fornitori",$ps=100,$oby="de_nomefornitore",$omode="asc",$start=0) {
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
		$this->linkmodifica = "$this->gestore?op=modifica&id=##id_fornitore##";
		$this->linkmodifica_label = "modifica";
		$this->linkeliminamarcate = "javascript:confermaDeleteCheck(document.datagrid);";
		checkAbilitazione("TSFORNITORI","TSFORNITORI");

	}
	function elenco($dati) {
		global $session;
		$html = "";
		if (isset($dati["keyword"])) $keyword=$dati["keyword"]; else $keyword="";
		if ($session->get("TSFORNITORI")) {
			$t=new grid(DB_PREFIX.$this->tbdb,$this->start, $this->ps, $this->oby, $this->omode);
			$t->checkboxFormAction=$this->gestore;
			$t->checkboxFormName="datagrid";
			$t->checkboxForm=true;
			$t->functionhtml = ""; //"myhtmlspecialchars";
			$t->mostraRecordTotali=true;
			$t->parametriDaPssare = "";
			if($keyword) $t->parametriDaPssare.="&keyword=".urlencode($keyword);
			//campi da visualizzare
			$t->campi="de_nomefornitore";
			//titoli dei campi da visualizzare
			$t->titoli="{Supplier}";
			//id per fare i link
			$t->chiave="id_fornitore";
			//query per estrarre i dati
			$t->query="select id_fornitore,de_nomefornitore from ".DB_PREFIX."ts_fornitori #WHERE#";
			$where = "";
			if($keyword) {
				if($where!="") { $where.= " and "; }
				$where.=" de_nomefornitore like '%$keyword%'";
			}
			if($where) {
				$t->query = str_replace("#WHERE#"," where {$where}",$t->query);
			} else {
				$t->query = str_replace("#WHERE#","",$t->query);
			}

			$t->addCampi('de_nomefornitore',"link",array("url"=>$this->linkmodifica));
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
		if ($session->get("TSFORNITORI")) {
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
				$dati = array("id_fornitore"=>"",
					"de_nomefornitore"=>"");
				$action = "aggiungiStep2";
			}

			//costruzione form
			$objform = new form();

			$de_nomefornitore = new testo("de_nomefornitore",($dati["de_nomefornitore"]),50,50);
			$de_nomefornitore->obbligatorio=1;
			$de_nomefornitore->label="'Nome del fornitore'";
			$objform->addControllo($de_nomefornitore);

			$id_fornitore = new hidden("id",$dati["id_fornitore"]);
			$op = new hidden("op",$action);

			$html = loadTemplateAndParse("template/dettaglio.html");
			$html = str_replace("##STARTFORM##", $objform->startform(), $html);
			$html = str_replace("##id##", $id_fornitore->gettag(), $html);
			$html = str_replace("##op##", $op->gettag(), $html);
			$html = str_replace("##de_nomefornitore##", $de_nomefornitore->gettag(), $html);
			$html = str_replace("##gestore##", $this->gestore, $html);
			$html = str_replace("##ENDFORM##", $objform->endform(), $html);
		} else {
			$html = "0";
		}
		return $html;
	}
	function getDati($id) {
		return execute_row("SELECT * from ".DB_PREFIX."ts_fornitori where id_fornitore='{$id}'");
	}

	function updateAndInsert($arDati) {
		// in:
		// arDati--> array POST del form
		// risultato:
		//	"" --> ok
		//  "0" --> il tuo profilo non ti consente l'inserimento/modifica
		global $session, $conn;
		if ($session->get("TSFORNITORI")) {
			if ($arDati["id"]!="") {
				/*
					Modifica
				*/
				$sql="UPDATE ".DB_PREFIX."ts_fornitori set de_nomefornitore='##de_nomefornitore##' where id_fornitore='##id_fornitore##'";
				$sql= str_replace("##de_nomefornitore##",$arDati["de_nomefornitore"],$sql);
				$sql= str_replace("##id_fornitore##",$arDati["id"],$sql);
				$conn->query($sql) or (trigger_error($conn->error."<br>$sql='{$sql}'"));
				$numero = $arDati["id"];
				$html= "";
			} else {
				/*
					Inserimento
				*/
				$sql="INSERT into ".DB_PREFIX."ts_fornitori (de_nomefornitore) values('##de_nomefornitore##')";
				$sql= str_replace("##de_nomefornitore##",$arDati["de_nomefornitore"],$sql);
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
		// id --> id fornitore da cancellare
		// result:
		//	"" --> ok
		//  "0" -->no permission
		global $session,$conn,$root;
		if ($session->get("TSFORNITORI")) {

			$sql = "delete from ".DB_PREFIX."ts_fornitori where id_fornitore='{$id}'";
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
		if ($session->get("TSFORNITORI")) {
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

	function getHtmlComboFornitori($def="") {
		global $conn;
		//------------------------------------------------
		//combo filtri
		$sql = "select id_fornitore,de_nomefornitore from ".DB_PREFIX."ts_fornitori order by de_nomefornitore";
		$rs = $conn->query($sql) or (trigger_error($conn->error."<br>$sql='{$sql}'"));
		$arFiltri = array("-999"=>"-- scegli --");
		while($riga = $rs->fetch_array()) {
			$arFiltri[$riga['id_fornitore']]=$riga['de_nomefornitore'];
		}
		//------------------------------------------------
		$out = "";
		foreach ($arFiltri as $k => $v) { $out.="<option value='{$k}' ".(($k."x"==$def."x")?"selected":"").">{$v}</option>"; }
		return "<select onchange='aggiornaGriglia()' name='combofornitore' id='combofornitore'>{$out}</select>";
	}

	function getHtmlCercaBox($def="") {
		//------------------------------------------------
		return "<input type='text' name='keyword' id='keyword' value=\"{$def}\"/>";
	}
}
?>