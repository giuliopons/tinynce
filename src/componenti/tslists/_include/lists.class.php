<?php
/**
 * class to handle lists
 */
	
class Lists {
	var $tbdb;	//table
	var $start;	// start from...
	var $omode;	// order mode asc|desc
	var $oby;	// order by field
	var $ps;	// page size
	var $linkaggiungi;
	var $linkmodifica;
	var $linkmodifica_label;
	var $linkeliminamarcate;
	var $gestore;

	function __construct ($tbdb="ts_lists",$ps=20,$oby="id_list",$omode="desc",$start=0) {
		global $session,$root;
		$this->gestore = $_SERVER["PHP_SELF"];
		$this->tbdb = $tbdb;
		// setVariabile used GET > POST > SESSION > default value
		$this->start = setVariabile("gridStart",$start,$this->tbdb);
		$this->omode= setVariabile("gridOrderMode",$omode,$this->tbdb);
		$this->oby= setVariabile("gridOrderBy",$oby,$this->tbdb);
		$this->ps = setVariabile("gridPageSize",$ps,$this->tbdb);
		// save values in session
		if(isset($_GET['combotipo'])) $session->register($this->tbdb."combotipo",$_GET['combotipo']);
		if(isset($_GET['combotiporeset'])) $session->register($this->tbdb."combotiporeset",$_GET['combotiporeset']);
		// link above in the panel
		$this->linkaggiungi = "$this->gestore?op=aggiungi";
		$this->linkeliminamarcate = "javascript:confermaDeleteCheck(document.datagrid);";
		// link in table grid
		$this->linkmodifica = "$this->gestore?op=modifica&id=##id_list##";
		$this->linkmodifica_label = "modifica";
		checkAbilitazione("LISTS","SETTA_SOLO_SE_ESISTE");
	}
	/*
		lists grid
	*/
	function elenco($combotipo="",$combotiporeset="",$keyword="") {
		global $session;
		$html = "";
		if ($session->get("LISTS")) {
			if($combotiporeset=='reset') {
				// if changed with filter select
				// reset pagination
				$this->start = 0;
			}
			//
			// reactivate filter after save and cancel
			if($combotipo=="" && $combotiporeset=="") {
				$combotipo= setVariabile("combotipo",$combotipo,$this->tbdb);
				$combotiporeset=setVariabile("combotiporeset",$combotipo,$this->tbdb);
				$GLOBALS['combotipo']=$combotipo;
				$GLOBALS['combotiporeset']=$combotiporeset;
			}
			$t=new grid(DB_PREFIX.$this->tbdb,$this->start, $this->ps, $this->oby, $this->omode);
			$t->checkboxFormAction=$this->gestore;
			$t->checkboxFormName="datagrid";
			$t->checkboxForm=true;
			$t->functionhtml = "";
			$t->mostraRecordTotali = true;
			$t->parametriDaPssare = "";
			if($combotipo) {
				$t->parametriDaPssare.="&combotipo=".urlencode($combotipo);
			}
			if($keyword) $t->parametriDaPssare.="&keyword=".urlencode($keyword);
			// fields to show
			$t->campi="de_namelist,fl_private";
			// titles to show
			$t->titoli="{List name},{Visibility}";
			// key field id for links
			$t->chiave="id_list";
			// query sql
			// $t->debug = true;

            $t->query="SELECT DISTINCT L.id_list,L.de_namelist,L.fl_private FROM
                    ".DB_PREFIX.$this->tbdb." as L
                    INNER JOIN ".DB_PREFIX."frw_extrauserdata E ON L.cd_owner=E.cd_user
                    LEFT OUTER JOIN ".DB_PREFIX."ts_tbc_lists_reparti LR ON LR.cd_list=L.id_list
                    LEFT OUTER JOIN ".DB_PREFIX."ts_tbc_lists_users LU ON LU.cd_list=L.id_list
                ";
            $where = " (LR.cd_reparto = E.cd_reparto OR 
                LU.cd_user = '".$session->get("idutente")."' 
                OR L.cd_owner = '".$session->get("idutente")."' 
                OR L.fl_private = 0) ";
			
			
			if($combotipo==="0" || $combotipo) {
				if($combotipo=="-999") {
				} else {
					if($where!="") { $where.= " and "; }

					if($combotipo=="0") {
						$where.=" L.fl_private=0 ";	
					} 
					if($combotipo=="1") {
						$where.=" L.fl_private=1 ";	
					} 
				}
			}
			if($keyword) {
				if($where!="") { $where.= " and "; }
				$where.="  (L.de_namelist like '%{$keyword}%')";
			}
			if($where) {
				$t->query.=" where {$where}";
			}

			$t->addCampi('de_namelist',"link",array("url"=>$this->linkmodifica));
            $t->addCampi('fl_private',"show_visibility_groups");

			$texto = $t->show();
			if (trim($texto)=="") $texto="{No records found.}";
			$html .= $texto."<br/>";
		} else {
			$html = "0";
		}
		return $html;
	}

	/*
		show client detail from
	*/
	function getDettaglio($id="",$duplica='no') {
		global $session,$root,$conn;
		if ($session->get("LISTS")) {
			
			if ($id!="") {
				/*
					modify
				*/
				$dati = $this->getDati($id);

				// only the owner can edit its list
				if($dati['cd_owner']!=$session->get("idutente")) return "0";

                // @todo check user can edit this list
				// if( !$this->isMyList($id) ) return "0";
				$action = "modificaStep2";
			} else {
				/*
					insert
				*/
				$dati = getEmptyNomiCelleAr(DB_PREFIX.$this->tbdb) ;
				$action = "aggiungiStep2";
				$dati['cd_owner'] = $session->get("idutente");
				$dati["fl_private"] = 1;
			}

			$html = loadTemplateAndParse("template/dettaglio.html");
			// form construction
			$objform = new form();
			$de_namelist = new testo("de_namelist",$dati["de_namelist"],150,50);
			$de_namelist->obbligatorio=1;
			$de_namelist->label="'{List name}'";
			$objform->addControllo($de_namelist);
			if( $session->get("idprofilo") < 20) {
                // @todo only admin can set records on tbc tables


				if ($dati['cd_user'] != $session->get("idutente")) {
					return "0";
				}
				
			}
			$cd_owner = new hidden("cd_owner", $dati['cd_owner'] );
			$ar = array();
			$ar[0]="{Public}";
			$ar[1]="{Private}";
			$fl_private = new optionlist("fl_private",($dati["fl_private"]),$ar);
			$fl_private->obbligatorio=0;
			$fl_private->label="'{Visibility}'";
			$objform->addControllo($fl_private);


            //------------------------------------------------
			// reparti list
			$sql = "select id_reparto,de_nomereparto from ".DB_PREFIX."ts_reparti order by de_nomereparto";
			$rs = $conn->query($sql) or trigger_error($conn->error." ".$sql);
			$arReparti2=array();
			while($riga = $rs->fetch_array()) $arReparti2[$riga['id_reparto']]=$riga['de_nomereparto'];
			//------------------------------------------------
			$dati["deps"] = concatenaId("select cd_reparto from ".DB_PREFIX."ts_tbc_lists_reparti WHERE cd_list='{$dati["id_list"]}'");
			$deps = new checkboxlist("deps",explode(",", $dati["deps"]), $arReparti2);
			$deps->obbligatorio=0;
			$deps->label="'{Departments}'";
			$objform->addControllo($deps);

            //------------------------------------------------
			// user list
			$sql = "select id,nome,cognome from ".DB_PREFIX."frw_utenti where id<>'".$session->get("idutente")."' order by cognome,nome";
			$rs = $conn->query($sql) or trigger_error($conn->error." ".$sql);
			$arUsers=array();
			while($riga = $rs->fetch_array()) $arUsers[$riga['id']]=$riga['cognome']." ".$riga['nome'];
			//------------------------------------------------
			$dati["users"] = concatenaId("select cd_user from ".DB_PREFIX."ts_tbc_lists_users WHERE cd_list='{$dati["id_list"]}'");
            $users = new checkboxlist("users",explode(",", $dati["users"]), $arUsers);
			$users->obbligatorio=0;
			$users->label="'{Users}'";
			$objform->addControllo($users);

			$id_obj = new hidden("id",$dati["id_list"]);
			$op = new hidden("op",$action);
			$html = str_replace("##STARTFORM##", $objform->startform(), $html);
			$html = str_replace("##cd_owner##", $cd_owner->gettag(), $html);
			$html = str_replace("##id##", $id_obj->gettag(), $html);
			$html = str_replace("##op##", $op->gettag(), $html);
			$html = str_replace("##de_namelist##", $de_namelist->gettag(), $html);
			$html = str_replace("##deps##", $deps->gettag(), $html);
			$html = str_replace("##users##", $users->gettag(), $html);
			$html = str_replace("##fl_private##", $fl_private->gettag(), $html);
			$html = str_replace("##gestore##", $this->gestore, $html);
			$html = str_replace("##ENDFORM##", $objform->endform(), $html);

		} else {
			$html = "0";
		}
		return $html;
	}
	function getDati($id) {
		$sql = "SELECT * from ".$this->tbdb." where id_list='{$id}'";
		return execute_row($sql);
	}

	function updateAndInsert($arDati,$files) {
		// in:
		// arDati--> array _POST from the form
		// files --> array _FILES
		// result:
		//	"" --> ok
		//  "0" --> no permission
		//  "2|messaggio" --> error
		global $session,$conn;
		if ($session->get("LISTS")) {
			// if($arDati["fl_private"] == 1) {
			// 	// force something?
			// }
			if ($arDati["id"]!="") {
				$id = (integer)$arDati["id"];

				/*
					Modify
				*/
				$sql="UPDATE ".DB_PREFIX.$this->tbdb." set
					de_namelist='##de_namelist##',
					fl_private='##fl_private##',
					cd_owner='##cd_owner##'
					where id_list='##id##'";
				$sql= str_replace("##fl_private##",$arDati["fl_private"],$sql);
				$sql= str_replace("##de_namelist##",$arDati["de_namelist"],$sql);
				$sql= str_replace("##cd_owner##",(integer)$arDati["cd_owner"],$sql);
				$sql= str_replace("##id##",$arDati["id"],$sql);
				$conn->query($sql) or die($conn->error.$sql);
				$html= "ok|".$id;
			} else {
				/*
					Insert
				*/
				if($session->get("idprofilo") < 20) {
					$dati['cd_user']=$session->get("idutente");
					$dati['cd_owner']=$session->get("idutente");
				}
				$sql="INSERT into ".DB_PREFIX.$this->tbdb." (de_namelist,fl_private,cd_owner,dt_saved) values('##de_namelist##','##fl_private##','##cd_owner##','".date("Y-m-d")."')";
				$sql= str_replace("##fl_private##",$arDati["fl_private"],$sql);
				$sql= str_replace("##de_namelist##",$arDati["de_namelist"],$sql);
				$sql= str_replace("##cd_owner##",(integer)$arDati["cd_owner"],$sql);
				$conn->query($sql) or die($conn->error.$sql);
				$id = $conn->insert_id;
				$html= "ok|".$id;
			}

            if(isset($arDati['deps']) ) {
				$conn->query("DELETE FROM ".DB_PREFIX."ts_tbc_lists_reparti WHERE cd_list='{$id}'") or trigger_error($conn->error." ".$sql);
				foreach($arDati['deps'] as $dep) {
					$sql="INSERT INTO ".DB_PREFIX."ts_tbc_lists_reparti (cd_list,cd_reparto) values('{$id}','{$dep}')";
					$conn->query($sql) or trigger_error($conn->error." ".$sql);
				}
			}
            if(isset($arDati['users']) ) {
				$conn->query("DELETE FROM ".DB_PREFIX."ts_tbc_lists_users WHERE cd_list='{$id}'") or trigger_error($conn->error." ".$sql);
				foreach($arDati['users'] as $user) {
					$sql="INSERT INTO ".DB_PREFIX."ts_tbc_lists_users (cd_list,cd_user) values('{$id}','{$user}')";
                    $conn->query($sql) or trigger_error($conn->error." ".$sql);
				}
			}
            

		} else {
			$html="0";		//no permission
		}
		return $html;
	}

	function getHtmlcombotipo($def="") {
		//------------------------------------------------
		//combo filter
		$arFiltri = array("-999"=>"{All}","1"=>"{Private}","0"=>"{Public}");
		//------------------------------------------------
		$out = "";
		foreach ($arFiltri as $k => $v) { $out.="<option value='{$k}' ".(($k."x"==$def."x")?"selected":"").">{$v}</option>"; }
		return "<label><select onchange='aggiornaGriglia()' name='combotipo' id='combotipo' class='filter'>{$out}</select><input type='hidden' name='combotiporeset' id='combotiporeset'></label>";
	}

	function deleteItem($id) {
		// in:
		// id --> id tipo da cancellare
		// result:
		//	"" --> ok
		//  "0" -->no permission
		global $session,$conn,$root;
		if ($session->get("LISTS")) {
			$sql="DELETE FROM ".DB_PREFIX.$this->tbdb." where id_list='$id'";
			$conn->query($sql) or die($conn->error."sql='$sql'<br>");
			$sql="DELETE FROM ".DB_PREFIX."ts_tasks where cd_list='$id'";
			$conn->query($sql) or die($conn->error."sql='$sql'<br>");
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
		global $session;
		if ($session->get("LISTS")) {
			$html="0";
			$p=$dati['gridcheck'];
			for ($i=0;$i<count($p);$i++) {
				$out = $this->deleteItem($p[$i]);
				if($out == "0") return "0";
			}
			$html = "";
		} else {
			$html="0";		//no permission
		}
		return $html;
	}

	/**
	 * check if the user is owner or the creator of the list
	 *
	 * @param  mixed $id
	 * @return int       0 no, 1 yes
	 */
	function isMyList($id){
		global $session;

        // @todo add check for visibility with join rules like in tslists grid
        return execute_scalar("SELECT count(1) FROM
			".DB_PREFIX."ts_lists as L
			INNER JOIN ".DB_PREFIX."frw_extrauserdata E ON L.cd_owner=E.cd_user
			LEFT OUTER JOIN ".DB_PREFIX."ts_tbc_lists_reparti LR ON LR.cd_list=L.id_list
			LEFT OUTER JOIN ".DB_PREFIX."ts_tbc_lists_users LU ON LU.cd_list=L.id_list
			WHERE (LR.cd_reparto = E.cd_reparto OR 
			LU.cd_user = '".$session->get("idutente")."' 
			OR L.cd_owner = '".$session->get("idutente")."' 
			OR L.fl_private = 0) AND L.id_list=".(integer)$id,0);
	}

}
?>