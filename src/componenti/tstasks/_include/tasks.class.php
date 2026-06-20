<?php
/*
	class to handle tasks
*/

class Tasks extends CrudBase {

	var $linkaggiungi;
	var $linkmodifica;
	var $linkmodifica_label;
	var $linkeliminamarcate;

	var $uploadDir;
	var $maxX;
	var $maxY;	
	var $max_files;

	/**
	 * class contructor
	 *
	 * @param  mixed $tbdb
	 * @param  mixed $ps
	 * @param  mixed $oby
	 * @param  mixed $omode
	 * @param  mixed $start
	 * @return void
	 */
	function __construct ($tbdb="ts_tasks",$ps=99999,$oby="id_task",$omode="desc",$start=0) {
		global $root;

		parent::__construct($tbdb,$ps,$oby,$omode,$start);

		// link above in the panel
		$this->linkaggiungi = "getAddTaskLink(event);";
		$this->linkeliminamarcate = "javascript:confermaDeleteCheck(document.datagrid);";

		// link in table grid
		$this->linkmodifica = "$this->gestore?op=modifica&id=##id_task##";
		$this->linkmodifica_label = "modifica";

		$this->uploadDir = $root."data/dbimg/tasks/";
		$this->maxX = 6000;
		$this->maxY = 6000;
		$this->max_files = 50;

		checkAbilitazione("THETASKS","SETTA_SOLO_SE_ESISTE");

	}
		
	/**
	 * get the list page
	 *
	 * @param  mixed $combotipo          filtering by list
	 * @param  mixed $combotiporeset     reset the filter flag
	 * @param  mixed $keyword            filtering by keyword
	 * @return void                    html
	 */
	function elenco($combotipo="",$combotiporeset="",$keyword="") {
		global $session;

		$html = "";

		if ($session->get("THETASKS")) {
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

			if((int)$combotipo>0) {
				$LO = new Lists();
				if( $LO->isMyList((int)$combotipo)== 0) {
					$this->ambiente->loadMsg("{You're not authorized.}","jsback", ERR_MSG);
					return;
				}
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
			$t->campi="taskname,dt_expiration,de_link,stato,priority";

			// titles to show
			$t->titoli="{Task},{Expiration},,{Status},{Priority}";

			// key field id for links
			$t->chiave="id_task";

			// query sql
			$t->query="SELECT A.id_task,CONCAT(A.de_taskname,'|^',A.de_color,'|^',A.cd_list) AS taskname,dt_expiration, CONCAT(A.id_task,'|',en_status) as stato,
				de_link,dt_priority as priority FROM ".DB_PREFIX.$this->tbdb." as A
                INNER JOIN ".DB_PREFIX."ts_lists L on L.id_list=A.cd_list
				INNER JOIN ".DB_PREFIX."frw_extrauserdata E ON L.cd_owner=E.cd_user
				LEFT OUTER JOIN ".DB_PREFIX."ts_tbc_lists_reparti LR ON LR.cd_list=L.id_list
            	LEFT OUTER JOIN ".DB_PREFIX."ts_tbc_lists_users LU ON LU.cd_list=L.id_list
				WHERE (LR.cd_reparto = E.cd_reparto OR 
					LU.cd_user = '".$session->get("idutente")."' 
					OR L.cd_owner = '".$session->get("idutente")."' 
					OR L.fl_private = 0)
            ";


			$where = " AND 1=1 ";

			//
			// ARCHIVED
			if($where!="") { $where.= " and "; }
			if(stristr($combotipo,"_archive")) {
				$where.=" A.fl_archived='1'";
				// $combotipo = str_replace("_archive","",$combotipo);	
			} else {
				$where.=" A.fl_archived='0'";	
			}
	
			// LIST
			if($combotipo=="-999" || $combotipo=="-999_archive") {
				$where.="";	
				$show_all = true;
			} else {
				$show_all = false;
				if($where!="") { $where.= " and "; }
				$where.=" A.cd_list='".(integer)$combotipo."'";	
			}


			if($keyword) {
				if($where!="") { $where.= " and "; }
				$where.="  (A.de_taskname like '%{$keyword}%')";
			}
			if($where) {
				$t->query.=" {$where}";
			}

			if($this->oby == 'stato') {
				if($this->omode == 'asc') 
					$t->query.= " ORDER BY dt_priority desc, FIELD(en_status,'to do','in progress','done'), id_task";	
				else 
					$t->query.= " ORDER BY dt_priority desc, FIELD(en_status,'done','in progress','to do'), id_task";	
			} else {
				$t->query.= " ORDER BY dt_priority desc, ".$this->oby." ".$this->omode;	
			}
			
            // $t->debug = true;

			$t->addCampi("taskname","colortask",array("showall"=>$show_all));
			$t->addCampi("de_link","linktask");
			$t->addCampi("stato","toggleStato");
			$t->addCampi("priority","togglePriority");
			// $t->addCampiDate("dt_expiration",DATEFORMAT." hh:ii");
			$t->addCampi("dt_expiration","show_scadenza");
			
			$texto = $t->show();

			if (trim($texto)=="") $texto="{No records found.}";

			$html .= $texto."<br/>";

			//
			// template filling
			$this->ambiente->setTemplate("template/elenco.html");
			$this->ambiente->setKey("##corpo##", $html );
			$this->ambiente->setKey("##keyword##", $keyword);
			$this->ambiente->setKey("##bottoni1##","<a href=\"#\" onclick=\"".$this->linkaggiungi."\" title=\"{Add new item}\" class='aggiungi'></a>");
			$this->ambiente->setKey("##bottoni2##","<a href=\"$this->linkeliminamarcate\" title=\"{Delete selected items}\" class='elimina'></a>");
			$this->ambiente->setKey("##bottoni3##","<a href=\"#\" onclick=\"toggleArchiveStatus(this,event);\" title=\"{Toggle archive}\" class='icon-folder toggable'></a>");
			$this->ambiente->setKey("##combotipo##", $this->getHtmlcombotipo($combotipo));

		} else {

			//
			// error template
			$this->ambiente->loadMsg("{You're not authorized.}","jsback", ERR_MSG);

		}	
	}

	/**
	 * get the form to handle the update or insert of the record
	 *
	 * @param  int $id
	 * @param  int $cd_list
	 * @return void
	 */
	function getDettaglio(int $id=0, int $cd_list=0) : void {
		global $session,$conn;

		if($cd_list == 0 && $id>0) {
			$cd_list = execute_scalar("select cd_list from ".DB_PREFIX.$this->tbdb." where id_task=".$id."", 0);
		}

		$LO = new Lists();
		if ($session->get("THETASKS") && ( $LO->isMyList($cd_list) || $cd_list == -999)) {
			// check if list belongs to user

			if ($id > 0) {
				/*
					modify
				*/
				$dati = $this->getDati($id);
				if(empty($dati)) {
					// missing record
					$this->ambiente->loadMsg("{You're not authorized.}","jsback", ERR_MSG);
					return;
				}
				if($dati['dt_closed']==ZERODATE." 00:00:00") $dati['dt_closed'] = "{tbd}";
				$action = "modificaStep2";
			} else {
				/*
					insert
				*/

				$prevRecord = execute_row("select * FROM ".DB_PREFIX."ts_tasks where cd_author =".$session->get("idutente")." order by id_task desc limit 0,1");

				$dati = getEmptyNomiCelleAr(DB_PREFIX.$this->tbdb) ;
				$action = "aggiungiStep2";
				$dati['cd_author'] = $session->get("idutente");
				$dati['cd_list'] = $cd_list;
				$dati['dt_opened'] = "{Now}";
				$dati['dt_closed'] = "{tbd}";
				$dati['de_color'] = $prevRecord['de_color'] ?? '';

			}


			// form construction
			$objform = new form();

			$de_taskname = new testo("de_taskname",$dati["de_taskname"],150,50);
			$de_taskname->obbligatorio=1;
			$de_taskname->label="'{List name}'";
			$objform->addControllo($de_taskname);

			$de_link = new urllink("de_link",$dati["de_link"],255,50);
			$de_link->obbligatorio=0;
			$de_link->label="'{Link}'";
			$objform->addControllo($de_link);

            $de_color = new colorlist("de_color",$dati["de_color"]);
			$de_color->obbligatorio=0;
			$de_color->label="'{Color}'";
			$objform->addControllo($de_color);

			$valore = ($dati["dt_expiration"]=="") ? "" : $dati["dt_expiration"];
			$dt_expiration = new dataora("dt_expiration",$valore,"aaaa-mm-gg",$objform->name);
			$dt_expiration->obbligatorio=0;
			$dt_expiration->label="'{Expiration}'";
			$objform->addControllo($dt_expiration);

			$cd_owner = new hidden("cd_author", $session->get("idutente") );

			$ar = array('to do' => '{to do}', 
				'done'=> '{done}', 
				'in progress' => '{in progress}',);
			$en_status = new optionlist("en_status",($dati["en_status"]),$ar);
			$en_status->obbligatorio=0;
			$en_status->label="'{Status}'";
			$objform->addControllo($en_status);


			$de_text = new richtext("de_text",(($dati["de_text"])));
			$de_text->obbligatorio=0;
			$de_text->label="'Testo'";
			$objform->addControllo($de_text);


			//------------------------------------------------
			// images
			$file = new fileupload2('file',$id,[
				"uploadDir" => $this->uploadDir,
				"max_files" => $this->max_files,
				"accept" => array('gif','jpg','png','jpeg','webp','pdf','mp4','zip','svg'),
				"maxKB" => MAXSIZE_UPLOAD,
				"maxX" => $this->maxX,
				"maxY" => $this->maxY,
				"multiple" => true,
				"deleteFiles" => true,
				"callback" => "setupViewerThumbGallery"
			]);
			$file->obbligatorio=0;
			$file->label="'{Banner image file}'";
			$file->value="";
			$objform->addControllo($file);


			//------------------------------------------------
			//combo lists
			$sql = "SELECT id_list,de_namelist, (SELECT COUNT(1) FROM ".DB_PREFIX."ts_tasks WHERE cd_list=id_list AND fl_archived=0) as c 
				FROM ".DB_PREFIX."ts_lists L
				INNER JOIN ".DB_PREFIX."frw_extrauserdata E ON L.cd_owner=E.cd_user
				LEFT OUTER JOIN ".DB_PREFIX."ts_tbc_lists_reparti LR ON LR.cd_list=L.id_list
				LEFT OUTER JOIN ".DB_PREFIX."ts_tbc_lists_users LU ON LU.cd_list=L.id_list
				WHERE (LR.cd_reparto = E.cd_reparto OR 
					LU.cd_user = '".$session->get("idutente")."' 
					OR L.cd_owner = '".$session->get("idutente")."' 
					OR L.fl_private = 0)					
				ORDER BY de_namelist";
			$cd_list = new optionlist("cd_list",$dati["cd_list"]);
			$cd_list->loadSqlOptions($sql, "id_list", "de_namelist", "--{choose}--");
			$cd_list->obbligatorio=1;
			$cd_list->label="'{List}'";
			$objform->addControllo($cd_list);

			$id_obj = new hidden("id",$dati["id_task"]);
			$cd_author = new hidden("cd_author",$dati["cd_author"]);
			$op = new hidden("op",$action);

			//
			// template filling
			$this->ambiente->setTemplate("template/dettaglio.html");
			$this->ambiente->setKey("##STARTFORM##", $objform->startform());
			$this->ambiente->setKey("##cd_owner##", $cd_owner->gettag());
			$this->ambiente->setKey("##de_color##", $de_color->gettag());
			$this->ambiente->setKey("##dt_expiration##", $dt_expiration->gettag());
			$this->ambiente->setKey("##id##", $id_obj->gettag());
			$this->ambiente->setKey("##op##", $op->gettag());
			$this->ambiente->setKey("##de_taskname##", $de_taskname->gettag());
			$this->ambiente->setKey("##de_link##", $de_link->gettag());
			$this->ambiente->setKey("##cd_author##", $cd_author->gettag());
			$this->ambiente->setKey("##cd_list##", $cd_list->gettag());
			$this->ambiente->setKey("##dt_closed##", $dati["dt_closed"]);
			$this->ambiente->setKey("##dt_opened##", $dati["dt_opened"]);
			$this->ambiente->setKey("##en_status##", $en_status->gettag());
			$this->ambiente->setKey("##de_text##", $de_text->gettag());
			$this->ambiente->setKey("##file##", $file->gettag());	
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
		$sql = "SELECT A.* from ".DB_PREFIX.$this->tbdb." A 
			INNER JOIN ".DB_PREFIX."ts_lists B on B.id_list=A.cd_list
			where id_task='{$id}'";
		return execute_row($sql);
	}


	/**
	 * Manage update and insert of a record
	 * 
	 * @param mixed $arDati
	 * @param mixed $files
	 * 
	 * @return integer     0 on error, id n. xxx when ok
	 */
	function updateAndInsert($arDati,$files) {

		global $session,$conn;

		$res = 0;

		if ($session->get("THETASKS")) {
			
			if(!isset($arDati['de_color']))	$arDati['de_color']="";

			if($arDati["dt_expiration"]=="") $arDati['dt_expiration']= "NULL";
				else $arDati['dt_expiration']= "'".$arDati["dt_expiration"]."'";
				
			if ($arDati["id"]!="") {				
				
				$id = (integer)$arDati["id"];

				$LO = new Lists();
				if($LO->isMyList($arDati['cd_list'])) {

					$prevRecord = $this->getDati($id);
					$arDati['dt_closed'] = $prevRecord['dt_closed'];	
					$arDati['dt_opened'] = $prevRecord['dt_opened'];	
					if($prevRecord['en_status'] != $arDati['en_status'] && $arDati['en_status'] != "done") {
						$arDati['dt_closed'] = date("Y-m-d H:i:s");
						$arDati['dt_opened'] = $prevRecord['dt_opened'];	
					}
					if($prevRecord['en_status'] != $arDati['en_status'] && $arDati['en_status'] != "to do") {
						$arDati['dt_opened'] = date("Y-m-d H:i:s");
						$arDati['dt_closed'] = ZERODATE." 00:00:00";
					}
	
					/*
						Modify
					*/
	
					$sql="UPDATE ".DB_PREFIX.$this->tbdb." set
						de_taskname='##de_taskname##',
						en_status='##en_status##',
						de_color='##de_color##',
						dt_closed='##dt_closed##',
						dt_opened='##dt_opened##',
						de_link='##de_link##',
						cd_list='##cd_list##',
						de_text='##de_text##',
						dt_expiration=   ##dt_expiration##
						where id_task='##id##' and cd_author='##cd_author##'";
					$sql= str_replace("##en_status##",$arDati["en_status"],$sql);
					$sql= str_replace("##de_color##",$arDati["de_color"],$sql);
					$sql= str_replace("##de_taskname##",$arDati["de_taskname"],$sql);
					$sql= str_replace("##dt_expiration##",$arDati["dt_expiration"],$sql);
					$sql= str_replace("##de_link##",$arDati["de_link"],$sql);
					$sql= str_replace("##de_text##",$arDati["de_text"],$sql);
					$sql= str_replace("##cd_author##",(integer)$arDati["cd_author"],$sql);
					$sql= str_replace("##cd_list##",(integer)$arDati["cd_list"],$sql);
					$sql= str_replace("##id##",$arDati["id"],$sql);
					$sql= str_replace("##dt_closed##",$arDati["dt_closed"],$sql);
					$sql= str_replace("##dt_opened##",$arDati["dt_opened"],$sql);
					$conn->query($sql) or die($conn->error.$sql);
					$res= $id;
	

				}

			} else {

				/*
					Insert
				*/
				if($session->get("idprofilo") < 20) {
					$dati['cd_author']=$session->get("idutente");
				}
				$sql="INSERT into ".DB_PREFIX.$this->tbdb." (de_color,de_taskname,dt_expiration,en_status,cd_author,cd_list,dt_opened,dt_closed,de_link,de_text) values('##de_color##','##de_taskname##', ##dt_expiration##   ,'##en_status##','##cd_author##','##cd_list##','##dt_opened##','##dt_closed##','##de_link##','##de_text##')";
				$sql= str_replace("##en_status##",$arDati["en_status"],$sql);
				$sql= str_replace("##de_taskname##",$arDati["de_taskname"],$sql);
				$sql= str_replace("##dt_expiration##",$arDati["dt_expiration"],$sql);
				$sql= str_replace("##de_link##",$arDati["de_link"],$sql);
				$sql= str_replace("##cd_author##",(integer)$arDati["cd_author"],$sql);
				$sql= str_replace("##cd_list##",(integer)$arDati["cd_list"],$sql);
				$sql= str_replace("##de_color##",$arDati["de_color"],$sql);
				$sql= str_replace("##de_text##",$arDati["de_text"],$sql);
				$sql= str_replace("##dt_opened##",date("Y-m-d H:i:s"),$sql);
				$sql= str_replace("##dt_closed##",ZERODATE." 00:00:00",$sql);

				$conn->query($sql) or die($conn->error.$sql);
				$id = $conn->insert_id;
				$res= $id;
				
			}

			$imagesOutput = "";
			if($res > 0 && isset($files['file']['type'])) {

				for($i=0; $i<count($files['file']['type']); $i++) {

					if($res > 0 && $files['file']['type'][$i]!="") {

						// remap
						$onefile['file']['name'] = $files['file']['name'][$i];
						$onefile['file']['type'] = $files['file']['type'][$i];
						$onefile['file']['tmp_name'] = $files['file']['tmp_name'][$i];
						$onefile['file']['error'] = $files['file']['error'][$i];
						$onefile['file']['size'] = $files['file']['size'][$i];
						// $onefile['file']['full_path'] = $files['file']['full_path'][$i];

						$imagesOutput = uploadFile(
							$onefile,
							'file',
							$this->uploadDir.$id."_",
							array('gif','jpg','png','jpeg','webp','pdf','mp4','zip','svg'),
							$this->maxX,
							$this->maxY,
							MAXSIZE_UPLOAD,
							$this->max_files
						);		
					}
				}
			}	


			if($res > 0 ) {
				//
				// ok response

				if ($imagesOutput != "") {
					// ...but img upload error
					$imagesOutput = explode("|",$imagesOutput)[1];
					$this->ambiente->loadMsg("{".$imagesOutput."}","jsback", ERR_MSG);
				} else {
					if($arDati['op'] == "modificaStep2reload" || $arDati['op'] == "aggiungiStep2reload")
						$this->ambiente->loadMsg("{Done.}","load ".$_SERVER['SCRIPT_NAME']."?op=modifica&id={$id}", OK_MSG);
					else
						$this->ambiente->loadMsg("{Done.}","load ".$_SERVER['SCRIPT_NAME']."?combotipo=".(int)$arDati["cd_list"], OK_MSG);
				}


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


	function fastInsert( $arDati ) {
		global $session,$conn;
		
		if ($session->get("THETASKS")) {
			
			$cd_list = (int)$arDati["combotipo"] ?? 0;
			$prevRecord = execute_row("select * FROM ".DB_PREFIX."ts_tasks where cd_author =".$session->get("idutente")." order by id_task desc limit 0,1");
			
			if( $cd_list> 0) {
				$LO = new Lists();
				if( $LO->isMyList($cd_list)== 0 ) return;
			} else {
				if ($cd_list = -999) {
					$cd_list = $prevRecord["cd_list"];
				}
			}
			if($cd_list === 0) {
				return;
			}
			

			$sql="INSERT into ".DB_PREFIX.$this->tbdb." (de_color,de_taskname,dt_expiration,en_status,cd_author,cd_list,dt_opened,dt_closed,de_link,de_text,dt_priority) values('##de_color##','##de_taskname##', ##dt_expiration##   ,'##en_status##','##cd_author##','##cd_list##','##dt_opened##','##dt_closed##','##de_link##','##de_text##', ##dt_priority##)";
			$sql= str_replace("##en_status##",'to do',$sql);
			$sql= str_replace("##de_taskname##",$arDati["title"],$sql);
			$sql= str_replace("##dt_expiration##",'NULL',$sql);
			$sql= str_replace("##dt_priority##", isset($prevRecord["dt_priority"]) ? "'".$prevRecord["dt_priority"]."'": "NULL",$sql);
			$sql= str_replace("##de_link##",'',$sql);
			$sql= str_replace("##cd_author##", $session->get("idutente") ,$sql);
			$sql= str_replace("##cd_list##",$cd_list,$sql);
			$sql= str_replace("##de_color##",$prevRecord["de_color"] ?? '',$sql);
			$sql= str_replace("##de_text##",'',$sql);
			$sql= str_replace("##dt_opened##",date("Y-m-d H:i:s"),$sql);
			$sql= str_replace("##dt_closed##",ZERODATE." 00:00:00",$sql);


			$conn->query($sql) or die($conn->error.$sql);
			// $id = $conn->insert_id;
		}
	}


	/** 
	 * return the default list item which is the list last updated for the current user
	 * 
	 * @return int
	 */
	function getDefaultListItem() {
		global $session;
		// $sql = "select distinct id_list from ".DB_PREFIX."ts_lists 
		// 	inner join ".DB_PREFIX."ts_tasks on id_list=cd_list
		// 	WHERE cd_author='".$session->get("idutente")."'
		// 	order by dt_closed desc, dt_opened desc";

		$id_list=0;
		if (isset($_COOKIE["list_tasks"]) && $_COOKIE["list_tasks"]!="") {
			$default = (integer)$_COOKIE["list_tasks"];
			$sql = "select id_list from ".DB_PREFIX."ts_lists 
				where (cd_user='".$session->get("idutente")."' or cd_owner='".$session->get("idutente")."' or fl_private=0)
				and id_list=".$default." limit 0,1";
			if(execute_scalar($sql,0) == $default) {
				$id_list=$default;
			}
		}

		if($id_list==0) {
			$id_list = -999;
			/*$sql = "select id_list from ".DB_PREFIX."ts_lists 
			where (cd_user='".$session->get("idutente")."' or cd_owner='".$session->get("idutente")."' or fl_private=0)
			order by de_namelist limit 0,1";
			$id_list = execute_scalar($sql,0);
			if($id_list == 0) {
	
	
				// $sql="SELECT A.cd_list from ".DB_PREFIX.$this->tbdb." as A
				// 	inner join ".DB_PREFIX."ts_lists B on B.id_list=A.cd_list
				// 	WHERE (cd_user=".$session->get("idutente")." or cd_owner=".$session->get("idutente")." or B.fl_private=0) 
				// 	LIMTI 0,1";
				$sql = "select id_list from ".DB_PREFIX."ts_lists 
				where (cd_user='".$session->get("idutente")."' or cd_owner='".$session->get("idutente")."' or fl_private=0)
				order by de_namelist";
					
				$id_list = execute_scalar($sql,0);
				
			}*/
		}
			
		return $id_list;
	}
	
	/**
	 * get the html of the combo selector filter to choose the list
	 *
	 * @param  string $def            default selected value
	 * @return string                 the html
	 */
	function getHtmlcombotipo($def="", $filter = true) {
		global $conn,$session;


        // @todo check visibility with join rules like in tslists grid
		$totCount = execute_scalar($sql = "select count(1) from ".DB_PREFIX."ts_tasks TS
		INNER JOIN ".DB_PREFIX."ts_lists L ON TS.cd_list=id_list
		INNER JOIN ".DB_PREFIX."frw_extrauserdata E ON L.cd_owner=E.cd_user
			LEFT OUTER JOIN ".DB_PREFIX."ts_tbc_lists_reparti LR ON LR.cd_list=L.id_list
            LEFT OUTER JOIN ".DB_PREFIX."ts_tbc_lists_users LU ON LU.cd_list=L.id_list
			WHERE (LR.cd_reparto = E.cd_reparto OR 
                LU.cd_user = '".$session->get("idutente")."' 
                OR L.cd_owner = '".$session->get("idutente")."' 
                OR L.fl_private = 0)				
		 AND fl_archived=0", 0);


		$arFiltri["-999"] = array("label"=>'{All}', "count"=>$totCount);

		// show my lists
		$sql = "select id_list as a,de_namelist as b, (select count(1) from ".DB_PREFIX."ts_tasks where cd_list=a and fl_archived=0) as c 
			FROM ".DB_PREFIX."ts_lists AS L
			INNER JOIN ".DB_PREFIX."frw_extrauserdata E ON L.cd_owner=E.cd_user
			LEFT OUTER JOIN ".DB_PREFIX."ts_tbc_lists_reparti LR ON LR.cd_list=L.id_list
            LEFT OUTER JOIN ".DB_PREFIX."ts_tbc_lists_users LU ON LU.cd_list=L.id_list
			WHERE (LR.cd_reparto = E.cd_reparto OR 
                LU.cd_user = '".$session->get("idutente")."' 
                OR L.cd_owner = '".$session->get("idutente")."' 
                OR L.fl_private = 0)
			ORDER BY de_namelist";
		$rs = $conn->query($sql) or (trigger_error($conn->error."<br>$sql='{$sql}'"));
		while($riga = $rs->fetch_array()) {
			$arFiltri[$riga['a']]=array("label"=>$riga["b"], "count"=>$riga['c']);
		}

		
		$totCountArchive = execute_scalar("select count(1) from ".DB_PREFIX."ts_tasks TS
		INNER JOIN ".DB_PREFIX."ts_lists L ON TS.cd_list=id_list
		INNER JOIN ".DB_PREFIX."frw_extrauserdata E ON L.cd_owner=E.cd_user
			LEFT OUTER JOIN ".DB_PREFIX."ts_tbc_lists_reparti LR ON LR.cd_list=L.id_list
            LEFT OUTER JOIN ".DB_PREFIX."ts_tbc_lists_users LU ON LU.cd_list=L.id_list
			WHERE (LR.cd_reparto = E.cd_reparto OR 
                LU.cd_user = '".$session->get("idutente")."' 
                OR L.cd_owner = '".$session->get("idutente")."' 
                OR L.fl_private = 0)		
		AND fl_archived=1", 0);

		$arFiltri["-999_archive"] = array("label"=>'{All} - {Archive}', "count"=>$totCountArchive);
		// show my lists of archived tasks
		$sql = "select id_list as a,de_namelist as b, (select count(1) from ".DB_PREFIX."ts_tasks where cd_list=a and fl_archived=1) as c 
			FROM ".DB_PREFIX."ts_lists AS L
			INNER JOIN ".DB_PREFIX."frw_extrauserdata E ON L.cd_owner=E.cd_user
			LEFT OUTER JOIN ".DB_PREFIX."ts_tbc_lists_reparti LR ON LR.cd_list=L.id_list
            LEFT OUTER JOIN ".DB_PREFIX."ts_tbc_lists_users LU ON LU.cd_list=L.id_list
			WHERE (LR.cd_reparto = E.cd_reparto OR 
                LU.cd_user = '".$session->get("idutente")."' 
                OR L.cd_owner = '".$session->get("idutente")."' 
                OR L.fl_private = 0)
			ORDER BY de_namelist";
		$rs = $conn->query($sql) or (trigger_error($conn->error."<br>$sql='{$sql}'"));
		while($riga = $rs->fetch_array()) {
			if($riga['c'] > 0) $arFiltri[$riga['a']."_archive"]=array("label"=>$riga["b"]." - {Archive}", "count"=>$riga['c']);
		}

		// build the output
		$out = "";
		foreach ($arFiltri as $k => $v) { 
			$out.="<option value='{$k}' ".(($k."x"==$def."x")?"selected":"")." data-label=\"".htmlspecialchars($v["label"])."\" data-count='{$v['count']}'>{$v["label"]}".($v['count']>0 ? " ({$v['count']})" : "")."</option>"; 
		}

		return "<select onchange='aggiornaGriglia()' name='combotipo' id='combotipo' class='filter'>{$out}</select><input type='hidden' name='combotiporeset' id='combotiporeset'></label>";
	}



	
	/**
	 * delete a task from the list
	 *
	 * @param  mixed $id
	 * @return string
	 */
	function deleteItem($id) {
		global $session,$conn;
		if ($session->get("THETASKS")) {

			$auth =  execute_row ("SELECT * FROM ".DB_PREFIX."ts_lists INNER JOIN ".DB_PREFIX.$this->tbdb." ON ".DB_PREFIX.$this->tbdb.".cd_list=".DB_PREFIX."ts_lists.id_list where id_task='".$id."'");

			if(!isset($auth["cd_owner"])) return "0";
			
			// $auth = execute_scalar("SELECT cd_author FROM ".DB_PREFIX.$this->tbdb." where id_task='$id'");
			if($auth["cd_owner"] == $session->get("idutente") || $auth["cd_author"] == $session->get("idutente") ) {
				$sql="DELETE FROM ".DB_PREFIX.$this->tbdb." where id_task='$id'";
				$conn->query($sql) or die($conn->error."sql='$sql'<br>");
			} else {
				return "0";
			}
			$res = "";		// ok
		} else {
			$res="0";		//no permission
		}
		return $res;

	}
		
	/**
	 * delete selected tasks
	 *
	 * @param  mixed $dati
	 * @return void
	 */
	function eliminaSelezionati($dati) {
		global $session;
		if ($session->get("THETASKS")) {
			$res = "";
			for ($i=0;$i<count($dati['gridcheck']);$i++) {
				if( $this->deleteItem($dati['gridcheck'][$i]) == "0") {
					$res = "0";
					break;
				}
			}
			//
			// fail/ok response
			if($res=="0") {
				$this->ambiente->loadMsg("{You can't delete some tasks.}","jsback", ERR_MSG);
			} else 
				$this->ambiente->loadMsg("{Deleted.}","load ".$_SERVER['SCRIPT_NAME'], OK_MSG);

		} else {
			//
			// no permission
			$this->ambiente->loadMsg("{You're not authorized.}","jsback", ERR_MSG);
		}
	}

	/**
	 * get the list of options for the status of the task
	 * 
	 * @param int $id         the id of the task
	 * @return array 		  the options array with html and js actions
	 */
	function getToggleOptions($id) {
		return array(
		
			"to do"=>"<a class='label labelred' href=\"javascript:;\" onclick=\"setStato(this,'in progress',".$id.")\">{to do}</a></span>",
			"in progress"=>"<a class='label labelyellow' href=\"javascript:;\" onclick=\"setStato(this,'done',".$id.")\">{in progress}</a></span>",
			"done"=>"<a class='label labelgreen' href=\"javascript:;\" onclick=\"setStato(this,'to do',".$id.")\">{done}</a></span>",	
			
		);
	}

	/**
	 * get the list of options for the priority of the task
	 * 
	 * @param int $id         the id of the task
	 * @return array 		  the options array with html and js actions
	 */
	function getTogglePriorityOptions($id) {
		return array(
			0 => "<a class='dot_low' href=\"javascript:;\" onclick=\"setPriority(this,1,".$id.")\" title=\"{toggle priority}\"></a></span>" ,
			1 => "<a class='dot_high' href=\"javascript:;\" onclick=\"setPriority(this,0,".$id.")\" title=\"{toggle priority}\"></a></span>"
		);
	}	

	
	/**
	 * change the priority of the task, called with ajax when click on status label in task list
	 * 
	 * @param int $task         the id of the task
	 * @param string $op        the new status
	 */
	function togglePriority($task,$op) {
		global $session,$conn;
		if ($session->get("THETASKS")) {
			$sql = "update ".DB_PREFIX.$this->tbdb." set dt_priority = ". ($op == 0 ? "NULL" : "'".date("Y-m-d H:i:s")."'")."
				where id_task='{$task}'";
			$conn->query($sql) or (trigger_error($conn->error."<br>$sql='{$sql}'"));
			return $op==1 ? 0 : 1;
		}
		return -1;
	}


	/**
	 * change the status of the task, called with ajax when click on status label in task list
	 * updates also the open/close date
	 * 
	 * @param int $task         the id of the task
	 * @param string $op        the new status
	 */
	function toggleStato($task,$op) {
		global $session,$conn;
		if ($session->get("THETASKS")) {
			$dt_closed =""; $dt_opened=""	;
			if($op=="to do") {$dt_opened = date("Y-m-d H:i:s"); $dt_closed=ZERODATE." 00:00:00";}
			if($op=="done") {$dt_closed = date("Y-m-d H:i:s");}
			$sql = "update ".DB_PREFIX.$this->tbdb." set en_status='{$op}' 
				".($dt_opened!="" ? ",dt_opened='{$dt_opened}'" : "")."
				".($dt_closed!="" ? ",dt_closed='{$dt_closed}'" : "")."
				where id_task='{$task}'";
			$conn->query($sql) or (trigger_error($conn->error."<br>$sql='{$sql}'"));
			return $op;
		}
		return -1;
	}


	/**
	 * archive the task by changing the fl_archived flag
	 * 
	 * @param int $task         the id of the task
	 * @param string $op        the new flag status
	 */
	function toggleArchive($arIds) {
		global $session,$conn;
		if ($session->get("THETASKS")) {
			foreach($arIds as $task){
				$sql = "update ".DB_PREFIX.$this->tbdb." set fl_archived = 1 - fl_archived where id_task='{$task}'";
				$conn->query($sql) or (trigger_error($conn->error."<br>$sql='{$sql}'"));				
			}
			return 1;	
		}
		return 0;
	}

	function doCronJob() {

		global $conn;
		// select all open tasks with expiration day in the previous 10 min
		// and send email to user email

		$sql = "select * from ".DB_PREFIX.$this->tbdb." 
			inner join ".DB_PREFIX."frw_extrauserdata on cd_author=cd_user
			where now() > dt_expiration - INTERVAL 11 MINUTE and now() < dt_expiration and en_status='to do' and fl_archived=0 and de_email<>''";
			
		echo "<pre>";
		echo $sql."<br>";
		$result = $conn->query($sql) or (trigger_error($conn->error."<br>$sql='{$sql}'"));

		while($row = $result->fetch_array()) {
			// send email to the user
			$link = WEBURL."/src/componenti/tstasks/index.php?op=modifica&id={$row['id_task']}";
			$message = "<p>Expiration day: <b>{$row['dt_expiration']}</b><br>Remember to do the task n.%s: <b>{$row['de_taskname']}</b><br><br>More info:<br><a href=\"".$link."\" title=\"clicca qui per i dettagli\">".$link."</a></p>";
			$message = str_replace("%s", $row["id_task"], $message);
			$subject = "[".SERVER_NAME."] Memo: ". $row['de_taskname'];
			echo $row["de_email"]." - ".$subject."<br>";
			mail_utf8(
				$row["de_email"],
				$subject,
				$message);
		}
		echo "</pre>";
	}


	function getEventsCalendarRS($user_id, $list_id) : mysqli_result {

		global $conn;
		// if($list_id > 0) {
		// 	$sql = "select * from ".DB_PREFIX.$this->tbdb." where cd_author='{$user_id}' and id_list='{$list_id}'";
		// }
		$sql = "select * from ".DB_PREFIX.$this->tbdb."
			inner join ".DB_PREFIX."ts_lists on id_list=cd_list
			where dt_expiration > now() and en_status='to do' and fl_archived=0
			and (cd_author='{$user_id}' OR cd_owner='{$user_id}' OR cd_user='{$user_id}')
			". ($list_id > 0 ? " and id_list='{$list_id}'" : "");
			
		$result = $conn->query($sql) or (trigger_error($conn->error."<br>$sql='{$sql}'"));
		return $result;


	}


}