<?php
/*
	gestione reparti dei timesheet
*/
class Tipiora {
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
	var $arStati;
	function __construct ($tbdb="ts_tipiora",$ps=100,$oby="de_tipoora",$omode="asc",$start=0) {
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
		$this->linkeliminamarcate = "javascript:confermaDeleteCheck(document.datagrid);";
		$this->linkeliminamarcate_label = "{Delete selected items}";
		$this->linkmodifica = "$this->gestore?op=modifica&id=##id_tipoora##";
		$this->linkmodifica_label = "modifica";
		$this->linkelimina = "javascript:confermaDelete('##id_tipoora##');";
		$this->linkelimina_label = "elimina";
        checkAbilitazione("TSTIPIORE","TSTIPIORE");
		

	}
	function elenco($dati) {
		global $session;
		$html = "";
		if (isset($dati["comboreparti"])) $comboreparti=$dati["comboreparti"]; else $comboreparti="";
		if (isset($dati["keyword"])) $keyword=$dati["keyword"]; else $keyword="";
		if ($session->get("TSTIPIORE")) {
			$t=new grid(DB_PREFIX.$this->tbdb,$this->start, $this->ps, $this->oby, $this->omode);
			$t->checkboxFormAction=$this->gestore;
			$t->checkboxFormName="datagrid";
			$t->checkboxForm=true;
			$t->functionhtml = ""; //"myhtmlspecialchars";
			$t->mostraRecordTotali=true;
			$t->parametriDaPssare = "";
			if($comboreparti) $t->parametriDaPssare.="&comboreparti=".urlencode($comboreparti);
			if($keyword) $t->parametriDaPssare.="&keyword=".urlencode($keyword);
			//campi da visualizzare
			$t->campi="de_tipoora,reparti,quanti";
			//titoli dei campi da visualizzare
			$t->titoli="{Type},{Department},{Hours}";
			//id per fare i link
			$t->chiave="id_tipoora";
			//query per estrarre i dati
			// $t->debug = true;
			$t->query="select id_tipoora,de_tipoora,COUNT(cd_reparto) as reparti, IFNULL( (select sum(nu_ore) from ".DB_PREFIX."ts_ore where cd_tipoora=id_tipoora),0) as quanti 
			from ".DB_PREFIX."ts_tipiora 
			left outer join ".DB_PREFIX."ts_tbc_tipiore_reparti on cd_tipoora=id_tipoora
			#WHERE# group by id_tipoora,de_tipoora";
			$where = "";
			if($comboreparti) {
				if($comboreparti==-999) {
				} else {
					if($where!="") { $where.= " and "; }
					$where.=" cd_reparto='{$comboreparti}'";
				}
			}
			if($keyword) {
				if($where!="") { $where.= " and "; }
				$where.=" de_tipoora like '%$keyword%'";
			}
			if($where) {
				$t->query = str_replace("#WHERE#"," where {$where}",$t->query);
			} else {
				$t->query = str_replace("#WHERE#","",$t->query);
			}
			// $t->addComando($this->linkmodifica,$this->linkmodifica_label,"{Edit}");
			// $t->addComando($this->linkelimina,$this->linkelimina_label,"{Delete}");
			$t->addCampi('de_tipoora',"link",array("url"=>$this->linkmodifica));
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
		global $session,$root,$conn;
		if ($session->get("TSTIPIORE")) {
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
				$dati = array("id_tipoora"=>"",
					"de_tipoora"=>"","cd_reparto"=>"");
				$action = "aggiungiStep2";
			}

			//costruzione form
			$objform = new form();
			
			$de_tipoora = new testo("de_tipoora",($dati["de_tipoora"]),50,50);
			$de_tipoora->obbligatorio=1;
			$de_tipoora->label="'Nome del Tipo'";
			$objform->addControllo($de_tipoora);
			
            //------------------------------------------------
			// list reparti
			$sql = "select id_reparto,de_nomereparto from ".DB_PREFIX."ts_reparti order by de_nomereparto";
			$rs = $conn->query($sql) or trigger_error($conn->error." ".$sql);
			$arReparti2=array();
			while($riga = $rs->fetch_array()) $arReparti2[$riga['id_reparto']]=$riga['de_nomereparto'];
			//------------------------------------------------
			$dati["deps"] = concatenaId("select cd_reparto from ".DB_PREFIX."ts_tbc_tipiore_reparti WHERE cd_tipoora='{$dati["id_tipoora"]}'");
			$deps = new checkboxlist("deps",explode(",", $dati["deps"]), $arReparti2);
			$deps->obbligatorio=1;
			$deps->label="'{Departments}'";
			$objform->addControllo($deps);

			$id_tipoora = new hidden("id",$dati["id_tipoora"]);
			$op = new hidden("op",$action);
			$submit = new submit("invia","salva");
			$html = loadTemplateAndParse("template/dettaglio.html");
			$html = str_replace("##STARTFORM##", $objform->startform(), $html);
			$html = str_replace("##id##", $id_tipoora->gettag(), $html);
			$html = str_replace("##deps##", $deps->gettag(), $html);
			$html = str_replace("##op##", $op->gettag(), $html);
			$html = str_replace("##de_tipoora##", $de_tipoora->gettag(), $html);
			$html = str_replace("##gestore##", $this->gestore, $html);
			$html = str_replace("##ENDFORM##", $objform->endform(), $html);
		} else {
			$html = "0";
		}
		return $html;
	}
	function getDati($id) {
		return execute_row( "SELECT * from ".DB_PREFIX."ts_tipiora where id_tipoora='{$id}'" );
	}

	function updateAndInsert($arDati) {
		// in:
		// arDati--> array POST del form
		// risultato:
		//	"" --> ok
		//	"1" --> nome gia' utilizzato da un altro componente
		//  "0" --> il tuo profilo non ti consente l'inserimento/modifica
		global $session,$conn;
		if ($session->get("TSTIPIORE")) {
			if ($arDati["id"]!="") {
				/*
					Modifica
				*/
				$sql="UPDATE ".DB_PREFIX."ts_tipiora set de_tipoora='##de_tipoora##' where id_tipoora='##id_tipoora##'";
				//";
				// $sql= str_replace("##cd_reparto##",$arDati["cd_reparto"],$sql);
				$sql= str_replace("##de_tipoora##",$arDati["de_tipoora"],$sql);
				$sql= str_replace("##id_tipoora##",$arDati["id"],$sql);
				$conn->query($sql) or trigger_error($conn->error." ".$sql);
				$numero = $arDati["id"];
				$html= "";
			} else {
				/*
					Inserimento
				*/
				$sql="INSERT into ".DB_PREFIX."ts_tipiora (de_tipoora) values('##de_tipoora##')";
				$sql= str_replace("##de_tipoora##",$arDati["de_tipoora"],$sql);
				// $sql= str_replace("##cd_reparto##",$arDati["cd_reparto"],$sql);
				$conn->query($sql) or trigger_error($conn->error." ".$sql);
				$numero = $conn->insert_id;
				$html= "";
			}
			
			if(isset($arDati['deps']) ) {
				$conn->query("DELETE FROM ".DB_PREFIX."ts_tbc_tipiore_reparti WHERE cd_tipoora='{$numero}'") or trigger_error($conn->error." ".$sql);
				foreach($arDati['deps'] as $dep) {
					$sql="INSERT into ".DB_PREFIX."ts_tbc_tipiore_reparti (cd_tipoora,cd_reparto) values('{$numero}','{$dep}')";
					$conn->query($sql) or trigger_error($conn->error." ".$sql);
				}
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
		if ($session->get("TSTIPIORE") && $id<>1) {
			$sql = "delete from ".DB_PREFIX."ts_ore where cd_tipoora = $id";
			$conn->query($sql) or trigger_error($conn->error." ".$sql);
			$sql = "delete from ".DB_PREFIX."ts_default_job_tipi where cd_type = $id";
			$conn->query($sql) or trigger_error($conn->error." ".$sql);
			$sql = "delete from ".DB_PREFIX."ts_tbc_tipiore_reparti where cd_tipoora = $id";
			$conn->query($sql) or trigger_error($conn->error." ".$sql);
			$sql = "delete from ".DB_PREFIX."ts_tipiora where id_tipoora='{$id}'";
			$conn->query($sql) or trigger_error($conn->error." ".$sql);
			$html = "";
		} else {
			$html="0";		//il tuo profilo non ti consente di cancellare
		}
		return $html;
	}

	function eliminaSelezionati($dati) {
		// in:
		// dati --> $_POST
		// risultato:
		//	"" --> ok
		//  "0" -->il tuo profilo non ti consente la cancellazione
		global $session,$conn;
		if ($session->get("TSTIPIORE")) {
			$html="0";
			$idx ="";
			$p=$dati['gridcheck'];
			for ($i=0;$i<count($p);$i++) {
				$this->deleteItem((integer)$p[$i]);
				if ($idx) $idx.=", ";
				$idx .= $p[$i];
			}
			$html = "";
		} else {
			$html="0";		//il tuo profilo non ti consente di cancellare
		}
		return $html;
	}

	function getHtmlComboReparti($def="") {
		global $conn;
		//------------------------------------------------
		//combo filtri
		$sql = "select id_reparto,de_nomereparto,(select count(*) from ".DB_PREFIX."ts_tbc_tipiore_reparti where id_reparto=cd_reparto) as c from ".DB_PREFIX."ts_reparti group by id_reparto,de_nomereparto order by de_nomereparto";
		$rs = $conn->query($sql) or trigger_error($conn->error." ".$sql);
		$arFiltri = array("-999"=>"--{choose}--");
		while($riga = $rs->fetch_array()) {
			if ($riga['id_reparto']=="") $riga['c']=0;
			$arFiltri[$riga['id_reparto']]=$riga['de_nomereparto']." (".$riga['c']." tipi)";
		}
		//------------------------------------------------
		$out = "";
		foreach ($arFiltri as $k => $v) { $out.="<option value='{$k}' ".(($k."x"==$def."x")?"selected":"").">{$v}</option>"; }
		return "<select onchange='aggiornaGriglia()' name='comboreparti' id='comboreparti' class='filter'>{$out}</select>";
	}
}
?>