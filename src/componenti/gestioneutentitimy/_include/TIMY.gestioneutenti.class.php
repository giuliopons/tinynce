<?php


/*

	class to handle users, extends GestioneUtenti

*/

DEFINE("COSTO_ORARIO_DEFAULT",10);
DEFINE("ORE_SETTIMANA_DEFAULT",40);

class TIMY_GestioneUtenti extends GestioneUtenti
{


	function __construct ($tbdb="frw_utenti",$ps=40,$oby="name",$omode="asc",$start=0,$selectedLetter="") {
        global $session;

		parent::__construct($tbdb,$ps,$oby,$omode,$start,$selectedLetter);

		// save values in session
		if( gridResetStartPage($_GET) ) {
            // These are already done in the constructor, but not company
            // if(isset($_GET['combotipo'])) $session->register($this->tbdb."combotipo",$_GET['combotipo']);
			// if(isset($_GET['keyword'])) $session->register($this->tbdb."keyword",$_GET['keyword']);
		}	        
	}

	function elencoUtenti($combotipo="",$combotiporeset="",$keyword="",$params=array()) {
        $params['fields']="de_sigla,name,username,de_label,de_email,fl_attivo,dt_last_access";
        $params['labels']="{Shortname},{Name},{Username},{Profile},{Email address},{Status},{Last access}";
        $params['query'] ="SELECT distinct de_sigla,CONCAT(cognome,' ',nome) as name,".DB_PREFIX."frw_utenti.id,".
            DB_PREFIX."frw_utenti.username,".DB_PREFIX."frw_profili.de_label,fl_attivo,password,de_email,dt_last_access 
            from ".DB_PREFIX."frw_utenti join ".DB_PREFIX."frw_profili on ".DB_PREFIX."frw_utenti.cd_profilo=".DB_PREFIX."frw_profili.id_profilo 
            left outer join ".DB_PREFIX."frw_extrauserdata on cd_user=id 
            ";

		return parent::elencoUtenti($combotipo,$combotiporeset,$keyword,$params);
	}



	/*
		show user detail form, both insert and update
	*/
	function getDettaglioNew(int $id=0, array $params=[]) {
        global $session;

        if ($id > 0) {
            /*
                modify
            */
            $dati = $this->getDati($id);
            if(empty($dati)) return "0";

        } else {
            /*
                insert
            */
            $dati1 = getEmptyNomiCelleAr(DB_PREFIX.$this->tbdb) ;
            $dati2 = getEmptyNomiCelleAr(DB_PREFIX."frw_extrauserdata"); ;
            $dati = $dati1 + $dati2;

        }

        $params['template'] = 'template/TIMY_dettaglio_new.html';

        $de_sigla = new testo("de_sigla",htmlspecialchars($dati["de_sigla"]),10,10);
        $de_sigla->obbligatorio=1;
        $de_sigla->label="'Sigla'";

        if($dati["nu_costo"]=="") $dati["nu_costo"] = COSTO_ORARIO_DEFAULT;
        if($dati["nu_oresettimanali"]=="") $dati["nu_oresettimanali"] = ORE_SETTIMANA_DEFAULT;

        $sql = "select * from ".DB_PREFIX."ts_reparti order by id_reparto,de_nomereparto";
        $cd_reparto = new optionlist("cd_reparto",($dati["cd_reparto"]),array());
        $cd_reparto->loadSqlOptions( $sql, "id_reparto", "de_nomereparto", "{choose}");
        $cd_reparto->obbligatorio=1;
        $cd_reparto->label="'{Department}'";

        $nu_costo = new testo("nu_costo",($dati["nu_costo"]),5,5);
        $nu_costo->obbligatorio=0;
        $nu_costo->label="'{Hour cost}'";
        $nu_costo->attributes.=" style='text-align:right'";

        $nu_oresettimanali = new testo("nu_oresettimanali",($dati["nu_oresettimanali"]),2,2);
        $nu_oresettimanali->obbligatorio=0;
        $nu_oresettimanali->label="'{Weekly hours}'";
        
        $params['fieldsObjects'] = array( $de_sigla, $cd_reparto, $nu_costo, $nu_oresettimanali);

        $params['stringsObjects']['elencoanni'] = $this->getAnniGrid($id);

        return parent::getDettaglioNew($id,$params);

	}


    function getAnniGrid($id) {

        if(!$id) return "<i>This section is available after saving the user</i>";

        $t=new grid(DB_PREFIX."ts_users_annual_cost",0, 999999, "nu_anno", "ASC");
        $t->mostraRecordTotali = false;
        $t->functionhtml="";

        $t->campi="nu_anno,nu_cost";
        $t->titoli="{Year},{Hour cost}";
        $t->chiave="nu_anno";
        $t->query="SELECT nu_anno,CONCAT(nu_cost,' ".addslashes(MONEY)."')as  nu_cost,cd_user from ".DB_PREFIX."ts_users_annual_cost WHERE cd_user='{$id}'";
        
        $t->addCampi('nu_anno',"link",array("url"=>"?op=modificaAnno&cd_user={$id}&nu_anno=##nu_anno##"));
        $t->addComando("deleteAnno(event,this,'##nu_anno##','{$id}');","elimina","{Delete}","onclick");
        
        $tab = $t->show();

        if(trim(strip_tags($tab))=="") $tab = "{No records found.}";

        $header = '<div class="panel2 internal contract"><div class="titlecontainer"><a href="?op=aggiungiAnno&cd_user='.$id.'" class="aggiungi" title="{Add cost}"></a></div></div>';

        return $header . $tab;

    }

	function updateAndInsert($arDati) {

        global $conn;

        $result = parent::updateAndInsert($arDati);

        if(stristr($result,"ok|")) {

            $id = (integer)str_replace( "|","",stristr( $result, "|")) ;
            
            if($id > 0) {

                $sql="UPDATE ".DB_PREFIX."frw_extrauserdata SET nu_costo='##nu_costo##',cd_reparto='##cd_reparto##',nu_oresettimanali='##nu_oresettimanali##',de_sigla='##de_sigla##' WHERE cd_user='##id_user##'";
                $sql= str_replace("##cd_reparto##",$arDati["cd_reparto"],$sql);
                $sql= str_replace("##nu_costo##",$arDati["nu_costo"],$sql);
                $sql= str_replace("##de_sigla##",$arDati["de_sigla"],$sql);
                $sql= str_replace("##nu_oresettimanali##",$arDati["nu_oresettimanali"],$sql);
                $sql= str_replace("##id_user##",$id,$sql);

               $conn->query($sql) or trigger_error($conn->error."sql='$sql'<br>");
                

            }
            
        }

		return $result;
	}


	function updateAndInsertAnno($arDati) {
		// in:
		// arDati--> array POST del form
		// risultato:
		//	"" --> ok
		//	"1" --> nome gia' utilizzato da un altro componente
		//  "0" --> il tuo profilo non ti consente l'inserimento/modifica
		global $session, $conn;
		if ($session->get("GESTIONEUTENTI_WRITE")=="true") {
            $userObj = $this->getDati($arDati["cd_user"]);
            if(empty($userObj)) return "0";

            $arDati["nu_anno"] = (integer)$arDati["nu_anno"];
            $arDati["nu_cost"] = (integer)$arDati["nu_cost"];

            $conn->query("DELETE FROM ".DB_PREFIX."ts_users_annual_cost where cd_user='{$arDati["cd_user"]}' AND nu_anno='{$arDati["nu_anno"]}'") or trigger_error($conn->error);

            $sql="INSERT into ".DB_PREFIX."ts_users_annual_cost (cd_user,nu_anno,nu_cost) values('##cd_user##','##nu_anno##','##nu_cost##')";
            $sql= str_replace("##cd_user##",$arDati["cd_user"],$sql);
            $sql= str_replace("##nu_anno##",$arDati["nu_anno"],$sql);
            $sql= str_replace("##nu_cost##",$arDati["nu_cost"],$sql);
            $conn->query($sql) or (trigger_error($conn->error."<br>$sql='{$sql}'"));
            $html= "ok|".$arDati["cd_user"];


		} else {
			$html="0";		//il tuo profilo non ti consente l'inserimento
		}
		return $html;
	}
	function eliminaSelezionati($dati) {
		// result:
		//	"" --> ok
		//  "0" --> can't
        global $conn;

        $result = parent::eliminaSelezionati($dati);
		if ($result =="") {
            // extra deletes

            $p=$dati['gridcheck'];
            for ($i=0;$i<count($p);$i++) {
                $sql="DELETE FROM ".DB_PREFIX."ts_ore where cd_utente='".(integer)$p[$i]."'";
                $conn->query($sql) or trigger_error($conn->error."sql='$sql'<br>");
            }

        }
		return $result;
	}


    function deleteAnno($anno,$user) {
        global $session, $conn;
        $anno = (integer)$anno;
        $user = (integer)$user;
        if ($session->get("GESTIONEUTENTI_WRITE")=="true") {
            $sql = "DELETE FROM ".DB_PREFIX."ts_users_annual_cost WHERE cd_user='{$user}' AND nu_anno='{$anno}'";
            $conn->query($sql) or trigger_error($conn->error."sql='$sql'<br>");
        }
        return "1";
    }

    function getDatiAnno($cd_user,$nu_anno) {
        return execute_row("select * from ".DB_PREFIX."ts_users_annual_cost where cd_user='{$cd_user}' and nu_anno='{$nu_anno}'");
    }

    function getDettaglioAnno($cd_user="",$nu_anno="") {
		global $session,$root;
		if ($session->get("GESTIONEUTENTI_WRITE")=="true") {
            $user = $this->getDati($cd_user);
            
			if(empty($user)) return "0";

			if ($nu_anno!="") {
				/*
					modifica
				*/
				$dati = $this->getDatiAnno($cd_user,$nu_anno);
				if(empty($dati)) return "0";
				$action = "modificaAnnoStep2";
			} else {
				/*
					inserimento
				*/
				$dati = array("cd_user"=>$cd_user,
                    "nu_anno"=> date("Y"),
					"nu_cost"=> COSTO_ORARIO_DEFAULT);
				$action = "aggiungiAnnoStep2";
			}

			//costruzione form
			$objform = new form();

            $nu_cost = new testo("nu_cost",($dati["nu_cost"]),5,5);
            $nu_cost->obbligatorio=1;
            $nu_cost->label="'{Hour cost}'";
            $nu_cost->attributes.=" style='text-align:right'";
            $objform->addControllo($nu_cost);

            $nu_anno = new testo("nu_anno",($dati["nu_anno"]),4,4);
            $nu_anno->obbligatorio=1;
            $nu_anno->label="'{Year}'";
            $objform->addControllo($nu_anno);

			$cd_userObj = new hidden("cd_user",$dati["cd_user"]);
			$op = new hidden("op",$action);
			// $submit = new submit("invia","salva");
			$html = loadTemplateAndParse("template/TIMY_anno.html");
			$html = str_replace("##STARTFORM##", $objform->startform(), $html);
			$html = str_replace("##id##", $cd_userObj->gettag(), $html);
			$html = str_replace("##op##", $op->gettag(), $html);
			//$html = str_replace("##submit##", $submit->gettagimage($root."images/salva.gif"," Salva"), $html);
			$html = str_replace("##nu_anno##", $nu_anno->gettag(), $html);
            $html = str_replace("##nu_cost##", $nu_cost->gettag(), $html);
            $html = str_replace("##MONEY##", MONEY, $html);

            $html = str_replace("##NOMEUTENTE##", $user['nome'].' '.$user['cognome'], $html);
			$html = str_replace("##gestore##", $this->gestore."?op=modifica&id=".$cd_user, $html);
			$html = str_replace("##ENDFORM##", $objform->endform(), $html);
		} else {
			$html = "0";
		}
		return $html;
	}


}

?>