<?php
/*
	class to handle notes
*/

class Notes extends CrudBase {

	var $linkaggiungi;
	var $linkmodifica;
	var $linkmodifica_label;
	var $linkeliminamarcate;

	/**
	 * class constructor
	 *
	 * @param  mixed $tbdb
	 * @param  mixed $ps
	 * @param  mixed $oby
	 * @param  mixed $omode
	 * @param  mixed $start
	 * @return void
	 */
	function __construct ($tbdb="ts_notes",$ps=25,$oby="dt_saved",$omode="desc",$start=0) {
		global $root;

		parent::__construct($tbdb,$ps,$oby,$omode,$start);

		// link above in the panel
		$this->linkaggiungi = "?op=aggiungi";
		$this->linkeliminamarcate = "javascript:confermaDeleteCheck(document.datagrid);";

		// link in table grid
		$this->linkmodifica = "$this->gestore?op=modifica&id=##id_note##";
		$this->linkmodifica_label = "modifica";

		checkAbilitazione("TSNOTES","SETTA_SOLO_SE_ESISTE");
	}

	/**
	 * get the list page
	 *
	 * @param  mixed $keyword            filtering by keyword
	 * @param  mixed $filtro             "archive" to show archived, "" for active
	 * @return void                      html
	 */
	function elenco($keyword="", $filtro="") {
		global $session;

		$html = "";

		if ($session->get("TSNOTES")) {

			$t = new grid(DB_PREFIX.$this->tbdb, $this->start, $this->ps, $this->oby, $this->omode);
			$t->checkboxFormAction = $this->gestore;
			$t->checkboxFormName = "datagrid";
			$t->checkboxForm = true;
			$t->functionhtml = "";
			$t->mostraRecordTotali = true;

			$t->parametriDaPssare = "";
			if($keyword) $t->parametriDaPssare .= "&keyword=".urlencode($keyword);
			if($filtro) $t->parametriDaPssare .= "&filtro=".urlencode($filtro);

			// fields to show
			$t->campi = "de_title,de_note,dt_saved";

			// titles to show
			$t->titoli = "{Note title},{Note text},{Date saved}";

			// key field id for links
			$t->chiave = "id_note";

			// query sql
			$t->query = "SELECT A.id_note, CONCAT(A.de_title,'|^',COALESCE(A.de_color,'')) AS de_title, A.de_note, A.dt_saved
				FROM ".DB_PREFIX.$this->tbdb." as A
				WHERE A.cd_author='".$session->get("idutente")."'";

			// archive filter
			if($filtro == "archive") {
				$t->query .= " AND A.fl_archived='1'";
			} else {
				$t->query .= " AND A.fl_archived='0'";
			}

			if($keyword) {
				$t->query .= " AND (A.de_title LIKE '%{$keyword}%' OR A.de_note LIKE '%{$keyword}%')";
			}

			$t->query .= " ORDER BY ".$this->oby." ".$this->omode;

			$t->addCampi("de_title","colornote");
			$t->addCampi("de_note","truncatenote");
			$t->addCampiDate("dt_saved",DATEFORMAT." hh:ii");

			$t->containerExtraClass = "masonry";

			$texto = $t->show();

			if (trim($texto)=="") $texto = "{No records found.}";

			$html .= $texto."<br/>";

			//
			// template filling
			$this->ambiente->setTemplate("template/elenco.html");
			$this->ambiente->setKey("##corpo##", $html);
			$this->ambiente->setKey("##keyword##", $keyword);
			$this->ambiente->setKey("##bottoni1##", "<a href=\"".$this->linkaggiungi."\" title=\"{Add new item}\" class='aggiungi'></a>");
			$this->ambiente->setKey("##bottoni2##", "<a href=\"$this->linkeliminamarcate\" title=\"{Delete selected items}\" class='elimina'></a>");
			$this->ambiente->setKey("##bottoni3##", "<a href=\"#\" onclick=\"toggleArchiveStatus(this,event);\" title=\"{Toggle archive}\" class='icon-folder toggable'></a>");
			$this->ambiente->setKey("##combotipo##", $this->getHtmlFiltroArchivio($filtro));

		} else {

			//
			// error template
			$this->ambiente->loadMsg("{You're not authorized.}","jsback", ERR_MSG);

		}
	}

	/**
	 * get the html of the archive filter select
	 *
	 * @param  string $def   current selected value ("" = active, "archive" = archived)
	 * @return string        the html select
	 */
	function getHtmlFiltroArchivio($def="") {
		global $session;

		$countActive  = execute_scalar("SELECT COUNT(1) FROM ".DB_PREFIX.$this->tbdb." WHERE cd_author='".$session->get("idutente")."' AND fl_archived='0'", 0);
		$countArchive = execute_scalar("SELECT COUNT(1) FROM ".DB_PREFIX.$this->tbdb." WHERE cd_author='".$session->get("idutente")."' AND fl_archived='1'", 0);

		$opts = array(
			""        => array("label" => "{Notes}",           "count" => $countActive),
			"archive" => array("label" => "{Archive}",          "count" => $countArchive),
		);

		$out = "";
		foreach($opts as $k => $v) {
			$selected = ($k === $def) ? "selected" : "";
			$out .= "<option value='{$k}' {$selected} data-label=\"".htmlspecialchars($v["label"])."\" data-count='{$v['count']}'>"
				. $v["label"] . ($v['count'] > 0 ? " ({$v['count']})" : "") . "</option>";
		}

		return "<select onchange='aggiornaGriglia()' name='filtro' id='filtro' class='filter'>{$out}</select>";
	}

	/**
	 * get the form to handle the update or insert of the record
	 *
	 * @param  int $id
	 * @return void
	 */
	function getDettaglio(int $id=0) : void {
		global $session,$conn;

		if ($session->get("TSNOTES")) {

			if ($id > 0) {
				/*
					modify
				*/
				$dati = $this->getDati($id);
				if(empty($dati)) {
					$this->ambiente->loadMsg("{You're not authorized.}","jsback", ERR_MSG);
					return;
				}
				$action = "modificaStep2";
			} else {
				/*
					insert
				*/
				$dati = getEmptyNomiCelleAr(DB_PREFIX.$this->tbdb);
				$action = "aggiungiStep2";
				$dati['cd_author'] = $session->get("idutente");
				$dati['dt_saved'] = date("Y-m-d H:i:s");
			}

			// form construction
			$objform = new form();

			$de_title = new testo("de_title", $dati["de_title"], 255, 60);
			$de_title->obbligatorio = 1;
			$de_title->label = "'{Note title}'";
			$objform->addControllo($de_title);

			$de_note = new richtext("de_note", ($dati["de_note"]), "'100%'", "'50vh'");
			$de_note->paste_data_images = true;
			$de_note->obbligatorio = 0;
			$de_note->label = "'{Note text}'";
			$objform->addControllo($de_note);

			$de_color = new colorlist("de_color", $dati["de_color"] ?? '');
			$de_color->obbligatorio = 0;
			$de_color->label = "'{Color}'";
			$objform->addControllo($de_color);

			$id_obj = new hidden("id", $dati["id_note"]);
			$cd_author = new hidden("cd_author", $dati["cd_author"]);
			$op = new hidden("op", $action);

			//
			// template filling
			$this->ambiente->setTemplate("template/dettaglio.html");
			$this->ambiente->setKey("##STARTFORM##", $objform->startform());
			$this->ambiente->setKey("##id##", $id_obj->gettag());
			$this->ambiente->setKey("##op##", $op->gettag());
			$this->ambiente->setKey("##de_title##", $de_title->gettag());
			$this->ambiente->setKey("##de_note##", $de_note->gettag());
			$this->ambiente->setKey("##de_color##", $de_color->gettag());
			$this->ambiente->setKey("##cd_author##", $cd_author->gettag());
			$this->ambiente->setKey("##dt_saved##", $dati["dt_saved"]);
			$this->ambiente->setKey("##gestore##", $this->gestore);
			$this->ambiente->setKey("##ENDFORM##", $objform->endform());

		} else {
			//
			// error template
			$this->ambiente->loadMsg("{You're not authorized.}","jsback", ERR_MSG);
		}
	}

	/**
	 * return a row of a record
	 *
	 * @param mixed $id
	 *
	 * @return array
	 */
	function getDati($id) {
		global $session;
		$sql = "SELECT A.* FROM ".DB_PREFIX.$this->tbdb." A 
			WHERE id_note='".(int)$id."' AND cd_author='".$session->get("idutente")."'";
		return execute_row($sql);
	}

	/**
	 * Manage update and insert of a record
	 *
	 * @param mixed $arDati
	 *
	 * @return integer     0 on error, id when ok
	 */
	function updateAndInsert($arDati) {
		global $session,$conn;

		$res = 0;

		if ($session->get("TSNOTES")) {

			if ($arDati["id"]!="") {

				$id = (integer)$arDati["id"];

				// check ownership
				$check = execute_scalar("SELECT cd_author FROM ".DB_PREFIX.$this->tbdb." WHERE id_note=".$id, 0);
				if($check != $session->get("idutente")) {
					$this->ambiente->loadMsg("{You're not authorized.}","jsback", ERR_MSG);
					return 0;
				}

				/*
					Modify
				*/
				$sql = "UPDATE ".DB_PREFIX.$this->tbdb." SET
					de_title='##de_title##',
					de_note='##de_note##',
					de_color='##de_color##',
					dt_saved=NOW()
					WHERE id_note='##id##' AND cd_author='##cd_author##'";
				$sql = str_replace("##de_title##", $conn->real_escape_string(stripslashes($arDati["de_title"])), $sql);
				$sql = str_replace("##de_note##", $conn->real_escape_string(stripslashes($arDati["de_note"])), $sql);
				$sql = str_replace("##de_color##", $conn->real_escape_string($arDati["de_color"] ?? ''), $sql);
				$sql = str_replace("##cd_author##", (integer)$arDati["cd_author"], $sql);
				$sql = str_replace("##id##", $id, $sql);
				$conn->query($sql) or die($conn->error.$sql);
				$res = $id;

			} else {

				/*
					Insert
				*/
				$sql = "INSERT INTO ".DB_PREFIX.$this->tbdb." (de_title, de_note, de_color, dt_saved, cd_author)
					VALUES('##de_title##', '##de_note##', '##de_color##', NOW(), '##cd_author##')";
				$sql = str_replace("##de_title##", $conn->real_escape_string(stripslashes($arDati["de_title"])), $sql);
				$sql = str_replace("##de_note##", $conn->real_escape_string(stripslashes($arDati["de_note"])), $sql);
				$sql = str_replace("##de_color##", $conn->real_escape_string($arDati["de_color"] ?? ''), $sql);
				$sql = str_replace("##cd_author##", (integer)$session->get("idutente"), $sql);

				$conn->query($sql) or die($conn->error.$sql);
				$id = $conn->insert_id;
				$res = $id;
			}

			if($res > 0) {
				//
				// ok response
				$this->ambiente->loadMsg("{Done.}","load ".$_SERVER['SCRIPT_NAME'], OK_MSG);
			} else {
				//
				// error template
				$this->ambiente->loadMsg("{You're not authorized.}","jsback", ERR_MSG);
			}

		} else {
			//
			// error template
			$this->ambiente->loadMsg("{You're not authorized.}","jsback", ERR_MSG);
		}
		return $res;
	}

	/**
	 * archive/unarchive selected notes by toggling fl_archived
	 *
	 * @param  array $arIds   array of note ids
	 * @return int            1 on success, 0 on no permission
	 */
	function toggleArchive($arIds) {
		global $session,$conn;
		if ($session->get("TSNOTES")) {
			foreach($arIds as $id) {
				$id = (int)$id;
				// only allow the author to archive their own notes
				$auth = execute_scalar("SELECT cd_author FROM ".DB_PREFIX.$this->tbdb." WHERE id_note='".$id."'", 0);
				if($auth == $session->get("idutente")) {
					$sql = "UPDATE ".DB_PREFIX.$this->tbdb." SET fl_archived = 1 - fl_archived WHERE id_note='".$id."'";
					$conn->query($sql) or (trigger_error($conn->error."<br>$sql='{$sql}'"));
				}
			}
			return 1;
		}
		return 0;
	}

	/**
	 * delete a note
	 *
	 * @param  mixed $id
	 * @return string
	 */
	function deleteItem($id) {
		global $session,$conn;
		if ($session->get("TSNOTES")) {
			$auth = execute_scalar("SELECT cd_author FROM ".DB_PREFIX.$this->tbdb." WHERE id_note='".(int)$id."'", 0);
			if($auth == $session->get("idutente")) {
				$sql = "DELETE FROM ".DB_PREFIX.$this->tbdb." WHERE id_note='".(int)$id."'";
				$conn->query($sql) or die($conn->error."sql='$sql'<br>");
			} else {
				return "0";
			}
			$res = "";		// ok
		} else {
			$res = "0";	// no permission
		}
		return $res;
	}

	/**
	 * delete selected notes
	 *
	 * @param  mixed $dati
	 * @return void
	 */
	function eliminaSelezionati($dati) {
		global $session;
		if ($session->get("TSNOTES")) {
			$res = "";
			for ($i=0; $i<count($dati['gridcheck']); $i++) {
				if($this->deleteItem($dati['gridcheck'][$i]) == "0") {
					$res = "0";
					break;
				}
			}
			//
			// fail/ok response
			if($res=="0") {
				$this->ambiente->loadMsg("{You can't delete some notes.}","jsback", ERR_MSG);
			} else {
				$this->ambiente->loadMsg("{Deleted.}","load ".$_SERVER['SCRIPT_NAME'], OK_MSG);
			}
		} else {
			//
			// no permission
			$this->ambiente->loadMsg("{You're not authorized.}","jsback", ERR_MSG);
		}
	}
}
