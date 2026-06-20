<?php
/*
	gestione reparti dei timesheet
*/
class Reparti {
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
	// var $linkeliminamarcate;	//link utilizzato per il comando "elimina" sui record checked
	// var $linkeliminamarcate_label;
	var $gestore;
	var $arStati;
	function __construct ($tbdb="ts_reparti",$ps=100,$oby="de_nomereparto",$omode="asc",$start=0) {
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
		// $this->linkeliminamarcate = "javascript:confermaDeleteCheck(document.datagrid);";
		// $this->linkeliminamarcate_label = "{Delete}";
		$this->linkmodifica = "$this->gestore?op=modifica&id=##id_reparto##";
		$this->linkmodifica_label = "modifica";
		$this->linkelimina = "javascript:confermaDelete('##id_reparto##');";
		$this->linkelimina_label = "elimina";

		checkAbilitazione("TSREPARTI","TSREPARTI");
		

	}
	function elenco($dati) {
		global $session;
		$html = "";
		//echo $dati["combostato"]."///";
		if (isset($dati["comboreparti"])) $comboreparti=$dati["comboreparti"]; else $comboreparti="";
		if (isset($dati["keyword"])) $keyword=$dati["keyword"]; else $keyword="";
		if ($session->get("TSREPARTI")) {
			$t=new grid(DB_PREFIX.$this->tbdb,$this->start, $this->ps, $this->oby, $this->omode);
			$t->checkboxFormAction=$this->gestore;
			$t->checkboxFormName="datagrid";
			$t->checkboxForm=false;
			$t->functionhtml = ""; //"myhtmlspecialchars";
			$t->mostraRecordTotali=true;
			$t->parametriDaPssare = "";
			if($comboreparti) $t->parametriDaPssare.="&comboreparti=".urlencode($comboreparti);
			if($keyword) $t->parametriDaPssare.="&keyword=".urlencode($keyword);
			//campi da visualizzare
			$t->campi="de_nomereparto,quanti";
			//titoli dei campi da visualizzare
			$t->titoli="{Department},{People}";
			//id per fare i link
			$t->chiave="id_reparto";
			//query per estrarre i dati
			$t->query="select id_reparto,de_nomereparto, (select count(*) from ".DB_PREFIX."frw_extrauserdata where cd_reparto=id_reparto) as quanti from ".DB_PREFIX."ts_reparti #WHERE# group by id_reparto,de_nomereparto";
			$where = "";
			if($comboreparti) {
				if($comboreparti==-999) {
				} else {
					if($where!="") { $where.= " and "; }
					$where.=" id_reparto='{$comboreparti}'";
				}
			}
			if($keyword) {
				if($where!="") { $where.= " and "; }
				$where.=" de_nomereparto like '%$keyword%'";
			}
			if($where) {
				$t->query = str_replace("#WHERE#"," where {$where}",$t->query);
			} else {
				$t->query = str_replace("#WHERE#","",$t->query);
			}
			// $t->addComando($this->linkmodifica,$this->linkmodifica_label,"{Edit}");
			$t->addComando($this->linkelimina,$this->linkelimina_label,"{Delete}");
			$t->addCampi('de_nomereparto',"link",array("url"=>$this->linkmodifica));
			$texto = $t->show();
			if (trim($texto)=="") $texto="{No records found.}";
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
		if ($session->get("TSREPARTI")) {
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
				$dati = getEmptyNomiCelleAr(DB_PREFIX.$this->tbdb) ;
				$action = "aggiungiStep2";
			}

			//costruzione form
			$objform = new form();
			$de_nomereparto = new testo("de_nomereparto",($dati["de_nomereparto"]),50,50);
			$de_nomereparto->obbligatorio=1;
			$de_nomereparto->label="'{Department name}'";
			$objform->addControllo($de_nomereparto);
			$fl_default = new checkbox("fl_default",$dati["fl_default"],$dati["fl_default"]==1);
			$id_reparto = new hidden("id",$dati["id_reparto"]);
			$op = new hidden("op",$action);
			$submit = new submit("invia","salva");
			$html = loadTemplateAndParse("template/dettaglio.html");
			$html = str_replace("##STARTFORM##", $objform->startform(), $html);
			$html = str_replace("##id##", $id_reparto->gettag(), $html);
			$html = str_replace("##op##", $op->gettag(), $html);
			$html = str_replace("##submit##", $submit->gettagimage($root."images/salva.gif"," Salva"), $html);
			$html = str_replace("##de_nomereparto##", $de_nomereparto->gettag(), $html);
			$html = str_replace("##fl_default##", $fl_default->gettag(), $html);
			$html = str_replace("##gestore##", $this->gestore, $html);
			$html = str_replace("##ENDFORM##", $objform->endform(), $html);
		} else {
			$html = "0";
		}
		return $html;
	}
	function getDati($id) {
		return execute_row("SELECT * from ".DB_PREFIX."ts_reparti where id_reparto='{$id}'");
	}

	function updateAndInsert($arDati) {
		// in:
		// arDati--> array POST del form
		// risultato:
		//	"" --> ok
		//	"1" --> nome gia' utilizzato da un altro componente
		//  "0" --> il tuo profilo non ti consente l'inserimento/modifica
		global $session,$conn;
		if ($session->get("TSREPARTI")) {
			if(!isset($arDati["fl_default"])) $arDati["fl_default"] = 0; else $arDati["fl_default"] = 1;
			if($arDati["fl_default"] == 1) $conn->query("UPDATE ".DB_PREFIX."ts_reparti set fl_default='0' where fl_default='1'");
			if ($arDati["id"]!="") {
				/*
					Modifica
				*/
				$sql="UPDATE ".DB_PREFIX."ts_reparti set de_nomereparto='##de_nomereparto##',fl_default='##fl_default##' where id_reparto='##id_reparto##'";
				//";
				$sql= str_replace("##de_nomereparto##",$arDati["de_nomereparto"],$sql);
				$sql= str_replace("##fl_default##",$arDati["fl_default"],$sql);
				$sql= str_replace("##id_reparto##",$arDati["id"],$sql);
				$conn->query($sql) or trigger_error($conn->error." ".$sql);
				$numero = $arDati["id"];
				$html= "";
			} else {
				/*
					Inserimento
				*/
				$sql="INSERT into ".DB_PREFIX."ts_reparti (de_nomereparto,fl_default) values('##de_nomereparto##','##fl_default##')";
				$sql= str_replace("##fl_default##",$arDati["fl_default"],$sql);
				$sql= str_replace("##de_nomereparto##",$arDati["de_nomereparto"],$sql);
				$conn->query($sql) or trigger_error($conn->error." ".$sql);
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
		// risultato:
		//	"" --> ok
		//  "0" -->il tuo profilo non ti consente la cancellazione
		global $session,$conn;
		if ($session->get("TSREPARTI")) {
			$sql = "delete from ".DB_PREFIX."ts_reparti where id_reparto='{$id}'";
			$rs = $conn->query($sql) or trigger_error($conn->error." ".$sql);
			$sql = "delete from ".DB_PREFIX."ts_tbc_tipiore_reparti where cd_reparto='{$id}'";
			$rs = $conn->query($sql) or trigger_error($conn->error." ".$sql);
			$sql = "update ".DB_PREFIX."frw_extrauserdata SET cd_reparto=0 where cd_reparto='{$id}'";
			$rs = $conn->query($sql) or trigger_error($conn->error." ".$sql);
			$sql = "update ".DB_PREFIX."ts_lists SET cd_reparto=0 where cd_reparto='{$id}'";
			$rs = $conn->query($sql) or trigger_error($conn->error." ".$sql);
			$html = "";
		} else {
			$html="0";		//il tuo profilo non ti consente di cancellare
		}
		return $html;
	}

}
?>