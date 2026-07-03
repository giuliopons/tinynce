<?php
/*
	gestione tipologie cliente
*/
class TipiCliente {
	var $tbdb;	//tabella del database che contiene i dati
	var $start;	// posizione del primo record visualizzato
	var $omode;	// asc|desc
	var $oby;	// campo della tabella $tbdb utilizzato per ordinare
	var $ps;	// numero di righe per pagina nell'elenco
	var $linkaggiungi;	//link utilizzato per "aggiungere"
	var $linkaggiungi_label;
	var $linkmodifica;	//link utilizzato per il comando "modifica"
	var $linkmodifica_label;
	var $linkelimina;	//link utilizzato per il comando "elimina"
	var $linkelimina_label;
	var $linkeliminamarcate;	//link utilizzato per il comando "elimina" sui record checked
	var $linkeliminamarcate_label;
	var $gestore;
	function __construct($tbdb="ts_tipi_cliente",$ps=100,$oby="de_tipo_cliente",$omode="asc",$start=0) {
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
		$this->linkmodifica = "$this->gestore?op=modifica&id=##id_tipo_cliente##";
		$this->linkmodifica_label = "modifica";
		$this->linkeliminamarcate = "javascript:confermaDeleteCheck(document.datagrid);";
		checkAbilitazione("TSTIPICLIENTE","TSTIPICLIENTE");

	}
	function elenco($dati) {
		global $session;
		$html = "";
		if (isset($dati["keyword"])) $keyword=$dati["keyword"]; else $keyword="";
		if ($session->get("TSTIPICLIENTE")) {
			$t=new grid(DB_PREFIX.$this->tbdb,$this->start, $this->ps, $this->oby, $this->omode);
			$t->checkboxFormAction=$this->gestore;
			$t->checkboxFormName="datagrid";
			$t->checkboxForm=true;
			$t->functionhtml = "";
			$t->mostraRecordTotali=true;
			$t->parametriDaPssare = "";
			if($keyword) $t->parametriDaPssare.="&keyword=".urlencode($keyword);
			//campi da visualizzare
			$t->campi="de_tipo_cliente,quanti";
			//titoli dei campi da visualizzare
			$t->titoli="{Client type},{Clients}";
			//id per fare i link
			$t->chiave="id_tipo_cliente";
			//query per estrarre i dati
			$t->query="select id_tipo_cliente,de_tipo_cliente, (select count(*) from ".DB_PREFIX."ts_clienti where cd_tipo_cliente=id_tipo_cliente) as quanti from ".DB_PREFIX."ts_tipi_cliente #WHERE#";
			$where = "";
			if($keyword) {
				if($where!="") { $where.= " and "; }
				$where.=" de_tipo_cliente like '%$keyword%'";
			}
			if($where) {
				$t->query = str_replace("#WHERE#"," where {$where}",$t->query);
			} else {
				$t->query = str_replace("#WHERE#","",$t->query);
			}

			$t->addCampi('de_tipo_cliente',"link",array("url"=>$this->linkmodifica));
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
		if ($session->get("TSTIPICLIENTE")) {
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
				$dati = array("id_tipo_cliente"=>"",
					"de_tipo_cliente"=>"");
				$action = "aggiungiStep2";
			}

			//costruzione form
			$objform = new form();

			$de_tipo_cliente = new testo("de_tipo_cliente",($dati["de_tipo_cliente"]),60,50);
			$de_tipo_cliente->obbligatorio=1;
			$de_tipo_cliente->label="'Tipo cliente'";
			$objform->addControllo($de_tipo_cliente);

			$id_tipo_cliente = new hidden("id",$dati["id_tipo_cliente"]);
			$op = new hidden("op",$action);

			$html = loadTemplateAndParse("template/dettaglio.html");
			$html = str_replace("##STARTFORM##", $objform->startform(), $html);
			$html = str_replace("##id##", $id_tipo_cliente->gettag(), $html);
			$html = str_replace("##op##", $op->gettag(), $html);
			$html = str_replace("##de_tipo_cliente##", $de_tipo_cliente->gettag(), $html);
			$html = str_replace("##gestore##", $this->gestore, $html);
			$html = str_replace("##ENDFORM##", $objform->endform(), $html);
		} else {
			$html = "0";
		}
		return $html;
	}
	function getDati($id) {
		return execute_row("SELECT * from ".DB_PREFIX."ts_tipi_cliente where id_tipo_cliente='{$id}'");
	}

	function updateAndInsert($arDati) {
		// in:
		// arDati--> array POST del form
		// risultato:
		//	"" --> ok
		//  "0" --> il tuo profilo non ti consente l'inserimento/modifica
		global $session, $conn;
		if ($session->get("TSTIPICLIENTE")) {
			$de_tipo_cliente = addslashes($arDati["de_tipo_cliente"]);
			if ($arDati["id"]!="") {
				/*
					Modifica
				*/
				$id = (int)$arDati["id"];
				$sql="UPDATE ".DB_PREFIX."ts_tipi_cliente set de_tipo_cliente='{$de_tipo_cliente}' where id_tipo_cliente='{$id}'";
				$conn->query($sql) or (trigger_error($conn->error."<br>$sql='{$sql}'"));
				$html= "";
			} else {
				/*
					Inserimento
				*/
				$sql="INSERT into ".DB_PREFIX."ts_tipi_cliente (de_tipo_cliente) values('{$de_tipo_cliente}')";
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
		// id --> id tipo cliente da cancellare
		// result:
		//	"" --> ok
		//  "0" -->no permission
		global $session,$conn,$root;
		if ($session->get("TSTIPICLIENTE")) {

			$id = (int)$id;
			//scollega i clienti che usano questa tipologia
			$conn->query("UPDATE ".DB_PREFIX."ts_clienti set cd_tipo_cliente=0 where cd_tipo_cliente='{$id}'") or (trigger_error($conn->error));
			$sql = "delete from ".DB_PREFIX."ts_tipi_cliente where id_tipo_cliente='{$id}'";
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
		if ($session->get("TSTIPICLIENTE")) {
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
}
?>
