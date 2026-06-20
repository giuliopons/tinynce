<?php
/*
	Jobs (or Projects)
*/

class Job {

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

	var $defaultcombostato;
	var $defaultcomboanno;

	var $gestore;

	var $arStati;

	function __construct ($tbdb="ts_job",$ps=2000,$oby="de_codice",$omode="asc",$start=0) {
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

		$this->defaultcombostato=  setVariabile("cstato",1,$this->tbdb); 
		$this->defaultcomboanno= "-999"; //setVariabile("canno",date("Y"),$this->tbdb); 

		$this->linkaggiungi = "$this->gestore?op=aggiungi";
		$this->linkaggiungi_label = "{Add new item}";

		$this->linkeliminamarcate = "javascript:confermaDeleteCheck(document.datagrid);";
		$this->linkeliminamarcate_label = "{Delete selected items}";

		$this->linkmodifica = "$this->gestore?op=modifica&id=##id_job##";
		$this->linkmodifica_label = "modifica";

		$this->linkelimina = "javascript:confermaDelete('##id_job##');";
		$this->linkelimina_label = "elimina";



		checkAbilitazione("TSJOB","TSJOB");


	}

	function elenco($dati) {

		global $session;

		$html = "";

		if (isset($dati["combostato"])) $combostato=(integer)$dati["combostato"]; else $combostato=$this->defaultcombostato;
		if (isset($dati["comboanno"])) $comboanno=$dati["comboanno"]; else $comboanno=$this->defaultcomboanno;
		if (isset($dati["combocliente"])) $combocliente=$dati["combocliente"]; else $combocliente="";
		if (isset($dati["keyword"])) $keyword=$dati["keyword"]; else $keyword="";

		if ($session->get("TSJOB")) {
			$t=new grid(DB_PREFIX.$this->tbdb,$this->start, $this->ps, $this->oby, $this->omode);
			$t->checkboxFormAction=$this->gestore;
			$t->checkboxFormName="datagrid";
			$t->checkboxForm=true;
			$t->functionhtml = "";
			$t->mostraRecordTotali=true;
			$t->parametriDaPssare = "";
			if($combocliente) $t->parametriDaPssare.="&combocliente=".urlencode($combocliente);
			if($keyword) $t->parametriDaPssare.="&keyword=".urlencode($keyword);

			//campi da visualizzare
			$t->campi="de_codice,nomejob,de_nomecliente,euri,ore,persone,dt_inizio,dt_fine,stato";

			//titoli dei campi da visualizzare
			$t->titoli="{Code},{Job name},{Client},{Cost},{Days},{People},{Starting date},{Ending date},{Status}";

			//id per fare i link
			$t->chiave="id_job";

			// $t->debug = true;

			//query per estrarre i dati
			//(select sum(nu_ore * nu_costo) FROM ts_clienti C LEFT OUTER JOIN ts_ore O ON O.cd_job = x.id_job LEFT OUTER JOIN frw_extrauserdata E ON E.cd_user = O.cd_utente) as euri
			$t->query="SELECT x.id_job,y.de_nomecliente,
				CONCAT(x.de_nomejob,'|^',x.de_color) AS nomejob,
				CONCAT(
					SUM(CASE WHEN AC.nu_cost IS NOT NULL 
						THEN AC.nu_cost*O.nu_ore
						ELSE E.nu_costo*O.nu_ore
					END),'|^',nu_budget
				) AS euri,
				CONCAT(
					SUM(nu_ore),'|^',nu_budget_hours
				) AS ore,
				COUNT(DISTINCT E.cd_user) AS persone,
				x.de_codice,
				x.dt_inizio,
				x.dt_fine,
				CONCAT(x.id_job,'|',fl_attivo) as stato,
				x.id_job AS job
				FROM ".DB_PREFIX."ts_job x 
				INNER JOIN ".DB_PREFIX."ts_clienti y ON id_cliente=cd_cliente 
				LEFT OUTER JOIN ".DB_PREFIX."ts_ore O ON O.cd_job=x.id_job
				LEFT OUTER JOIN ".DB_PREFIX."frw_extrauserdata E ON E.cd_user=O.cd_utente
				LEFT OUTER JOIN ".DB_PREFIX."ts_users_annual_cost AC on AC.cd_user=O.cd_utente and AC.nu_anno=YEAR(O.dt_giorno)
			#WHERE# GROUP BY 
				x.id_job, 
				y.de_nomecliente
			";

			$where = "";
			if($combocliente) {
				if($combocliente==-999) {

				} else {

					if($where!="") { $where.= " and "; }
					$where.=" cd_cliente='{$combocliente}'";
				}
			}
			if($comboanno) {
				if($comboanno!=-999) {
					if($where!="") { $where.= " and "; }
					$where.=" (dt_inizio>='{$comboanno}-01-01' and dt_inizio<='{$comboanno}-12-31')";
				}
			}
			if($combostato>=0) {
				if($combostato!=-999) {
					if($where!="") { $where.= " and "; }
					$where.=" (fl_attivo='".(integer)$combostato."')";
				}
			}

			if($keyword) {
				if($where!="") { $where.= " and "; }
				$where.=" (de_nomejob like '%$keyword%' or de_codice like '%$keyword%') ";
			}
			// $t->debug = true;
			if($where) {
				$t->query = str_replace("#WHERE#"," where {$where}",$t->query);
			} else {
				$t->query = str_replace("#WHERE#","",$t->query);
			}


			// $t->addComando($this->linkmodifica,$this->linkmodifica_label,"Modifica questo record");
			// $t->addComando($this->linkelimina,$this->linkelimina_label,"Elimina questo record");
			$t->addComando("../tsreport/index.php?op=cerca&job=##id_job##&gruppo=worked",
                "icon-chart-bar",
                "{Report}"
            );

			//$t->addComando("index.php?op=associa&id=##id_job##","<img src='../tsore/images/user.png' border='0'>","Associa utenti a questo job.");

			$t->addCampiDate("dt_inizio",'dd/mm/yyyy');
			$t->addCampiDate("dt_fine",'dd/mm/yyyy');
			$t->addCampi("nomejob","show_job_name");
			$t->addCampi("ore","showNumberOre");
			$t->addCampi("euri","showMoney");
			$t->arFormattazioneTD=array(
				"persone"=>"numero", 
				"ore"=>"numero",
				"euri"=>"numero"
			);
			$t->addCampi("de_codice","showCode");
			$t->addCampi("stato","toggleStato");

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
		global $session,$root,$conn;

		if ($session->get("TSJOB")) {
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
				//$associa = "";
				$dati = array("id_job"=>"",
					"de_codice"=>"",
					"de_nomejob"=>"",
					"dt_inizio"=>date("Y-m-d"),
					"dt_fine"=>date("Y-m-d",strtotime("+1 year")),
					"cd_cliente"=>"",
					"fl_attivo"=>1,
					"nu_budget"=>"0",
					"nu_budget_hours"=>0,
					"de_note"=>"",
                    "de_color"=>""
					);
				$action = "aggiungiStep2";
			}


			//costruzione form
			$objform = new form();
			
			$de_nomejob = new testo("de_nomejob",($dati["de_nomejob"]),50,50);
			$de_nomejob->obbligatorio=1;
			$de_nomejob->label="'{Job name}'";
			$objform->addControllo($de_nomejob);

			$nu_budget = new numerointero("nu_budget",($dati["nu_budget"]),6,6);
			$nu_budget->obbligatorio=1;
			$nu_budget->attributes.=" style='text-align:right'";
			$nu_budget->label="'{Max budget}'";
			$objform->addControllo($nu_budget);

			$nu_budget_hours = new numerointero("nu_budget_hours",($dati["nu_budget_hours"]),6,6);
			$nu_budget_hours->obbligatorio=1;
			$nu_budget_hours->attributes.=" style='text-align:right'";
			$nu_budget_hours->label="'{Max hours}'";
			$objform->addControllo($nu_budget_hours);

			$de_codice = new testo("de_codice",($dati["de_codice"]),10,10);
			$de_codice->obbligatorio=1;
			$de_codice->label="'{Code}'";
			$objform->addControllo($de_codice);

			$valore = $dati["dt_inizio"];
			if ($valore=="") $valore = date("Y-m-d");
			$dt_inizio = new data("dt_inizio",$valore,"aaaa-mm-gg",$objform->name);
			$dt_inizio->obbligatorio=1;
			$dt_inizio->label="'{Starting date}'";
			$objform->addControllo($dt_inizio);

			$valore = $dati["dt_fine"];
			if ($valore=="") $valore = date("Y-m-d");
			$dt_fine = new data("dt_fine",$valore,"aaaa-mm-gg",$objform->name);
			$dt_fine->obbligatorio=1;
			$dt_fine->label="'{Ending date}'";
			$objform->addControllo($dt_fine);

            $de_color = new colorlist("de_color",$dati["de_color"],Array(
                "white"=>"white",
				"red"=>"red",
				"yellow"=>"yellow",
                "green"=>"green",
                "blue"=>"blue",
                "black"=>"black",
                "orange"=>"orange",
                "purple"=>"purple",
            ));
			$de_color->obbligatorio=0;
			$de_color->label="'{Color}'";
			$objform->addControllo($de_color);


			$fl_attivo = new optionlist("fl_attivo",$dati["fl_attivo"],Array(
				"0"=>"{OFF}",
				"1"=>"{ON}"
			));
			$fl_attivo->obbligatorio=1;
			$fl_attivo->label="'{Status}'";
			$objform->addControllo($fl_attivo);

			$de_note = new areatesto("de_note",($dati["de_note"]),5,50);
			$de_note->obbligatorio=0;
			$de_note->label="'{Notes}'";
			$objform->addControllo($de_note);

			$id_job = new hidden("id",$dati["id_job"]);
			$op = new hidden("op",$action);

			//------------------------------------------------
			//combo clienti
			$sql = "select id_cliente,de_nomecliente from ".DB_PREFIX."ts_clienti order by de_nomecliente";
			$rs = $conn->query($sql) or (trigger_error($conn->error."<br>$sql='{$sql}'"));
			$arClienti[""]="--{choose}--";
			while($riga = $rs->fetch_array()) {
				$arClienti[$riga['id_cliente']]=$riga['de_nomecliente'];
			}
			//------------------------------------------------


			$cd_cliente = new optionlist("cd_cliente",($dati["cd_cliente"]),$arClienti);
			$cd_cliente->obbligatorio=1;
			$cd_cliente->label="'Cliente'";
			$objform->addControllo($cd_cliente);

			$submit = new submit("invia","salva");

			$html = loadTemplateAndParse("template/dettaglio.html");

			$html = str_replace("##STARTFORM##", $objform->startform(), $html);
			$html = str_replace("##id##", $id_job->gettag(), $html);
			$html = str_replace("##op##", $op->gettag(), $html);
			$html = str_replace("##MONEY##", MONEY, $html);
			//$html = str_replace("##submit##", $submit->gettagimage($root."images/salva.gif"," Salva"), $html);
			$html = str_replace("##de_nomejob##", $de_nomejob->gettag(), $html);
			$html = str_replace("##de_codice##", $de_codice->gettag(), $html);
			$html = str_replace("##de_color##", $de_color->gettag(), $html);
			$html = str_replace("##nu_budget##", $nu_budget->gettag(), $html);
			$html = str_replace("##nu_budget_hours##", $nu_budget_hours->gettag(), $html);
			$html = str_replace("##cd_cliente##", $cd_cliente->gettag(), $html);
			$html = str_replace("##de_note##", $de_note->gettag(), $html);
			$html = str_replace("##dt_inizio##", $dt_inizio->gettag(), $html);
			$html = str_replace("##dt_fine##", $dt_fine->gettag(), $html);
			$html = str_replace("##fl_attivo##", $fl_attivo->gettag(), $html);
			$html = str_replace("##gestore##", $this->gestore, $html);
			//$html = str_replace("##associa##", $associa, $html);
			$html = str_replace("##associa##", "", $html);
			$html = str_replace("##ENDFORM##", $objform->endform(), $html);

		} else {
			$html = "0";
		}
		return $html;
	}

	function getDati($id) {
		return execute_row("SELECT * from ".DB_PREFIX."ts_job where id_job='{$id}'");
	}


	function updateAndInsert($arDati) {
		// in:
		// arDati--> array POST del form
		// risultato:
		//	"" --> ok
		//	"1" --> nome gia' utilizzato da un altro componente
		//  "0" --> il tuo profilo non ti consente l'inserimento/modifica

		global $session,$conn;
		if ($session->get("TSJOB")) {
			if(!isset($arDati["de_color"])) $arDati["de_color"]="";
			if ($arDati["id"]!="") {
				/*
					Modifica
				*/
				$sql="UPDATE ".DB_PREFIX."ts_job set de_nomejob='##de_nomejob##',fl_attivo='##fl_attivo##',dt_inizio='##dt_inizio##',dt_fine='##dt_fine##',de_codice='##de_codice##',cd_cliente='##cd_cliente##',de_note='##de_note##',de_color='##de_color##',nu_budget_hours='##nu_budget_hours##',nu_budget='##nu_budget##'
				where id_job='##id_job##'";
				//";
				$sql= str_replace("##de_codice##",$arDati["de_codice"],$sql);
				$sql= str_replace("##dt_fine##",$arDati["dt_fine"],$sql);
				$sql= str_replace("##dt_inizio##",$arDati["dt_inizio"],$sql);
				$sql= str_replace("##cd_cliente##",$arDati["cd_cliente"],$sql);
				$sql= str_replace("##nu_budget##",$arDati["nu_budget"],$sql);
				$sql= str_replace("##nu_budget_hours##",$arDati["nu_budget_hours"],$sql);
				$sql= str_replace("##fl_attivo##",$arDati["fl_attivo"],$sql);
				$sql= str_replace("##de_nomejob##",$arDati["de_nomejob"],$sql);
				$sql= str_replace("##de_note##",$arDati["de_note"],$sql);
                $sql= str_replace("##de_color##",$arDati["de_color"],$sql);
				$sql= str_replace("##id_job##",$arDati["id"],$sql);
				$conn->query($sql) or (trigger_error($conn->error."<br>$sql='{$sql}'"));

				$numero = $arDati["id"];
				$html= "";

			} else {
				/*
					Inserimento
				*/
				$sql="INSERT into ".DB_PREFIX."ts_job (fl_attivo,de_nomejob,de_codice,dt_inizio,dt_fine,cd_cliente,de_note,de_color,nu_budget_hours,nu_budget) values('##fl_attivo##','##de_nomejob##','##de_codice##','##dt_inizio##','##dt_fine##','##cd_cliente##','##de_note##','##de_color##','##nu_budget_hours##','##nu_budget##')";
				$sql= str_replace("##de_nomejob##",$arDati["de_nomejob"],$sql);
				$sql= str_replace("##de_codice##",$arDati["de_codice"],$sql);
				$sql= str_replace("##fl_attivo##",$arDati["fl_attivo"],$sql);
				$sql= str_replace("##de_note##",$arDati["de_note"],$sql);
				$sql= str_replace("##nu_budget##",$arDati["nu_budget"],$sql);
				$sql= str_replace("##nu_budget_hours##",$arDati["nu_budget_hours"],$sql);
				$sql= str_replace("##dt_fine##",$arDati["dt_fine"],$sql);
				$sql= str_replace("##dt_inizio##",$arDati["dt_inizio"],$sql);
				$sql= str_replace("##cd_cliente##",$arDati["cd_cliente"],$sql);
                $sql= str_replace("##de_color##",$arDati["de_color"],$sql);

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
		// risultato:
		//	"" --> ok
		//  "0" -->il tuo profilo non ti consente la cancellazione

		global $session,$conn;
		if ($session->get("TSJOB")) {

			$sql = "delete from ".DB_PREFIX."ts_ore where cd_job='{$id}'";
			$conn->query($sql) or (trigger_error($conn->error."<br>$sql='{$sql}'"));

			$sql = "delete from ".DB_PREFIX."ts_job where id_job='{$id}'";
			$conn->query($sql) or (trigger_error($conn->error."<br>$sql='{$sql}'"));
        
            $sql = "delete from ".DB_PREFIX."ts_default_job_tipi where cd_job='{$id}'";
            $conn->query($sql) or (trigger_error($conn->error."<br>$sql='{$sql}'"));

            
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
		if ($session->get("TSJOB")) {

			$html="0";
			$idx ="";

			$p=$dati['gridcheck'];
			for ($i=0;$i<count($p);$i++) {
				$out = $this->deleteItem($p[$i]);
				if($out != "") return $out;
			}
			$html = "";
		} else {
			$html="0";	
		}
		return $html;
	}


	function getHtmlComboClienti($def="") {
		global $conn;
		//------------------------------------------------
		//combo filtri
		$sql = "SELECT id_cliente,cd_cliente,de_nomecliente,COUNT(1) AS c 
			FROM ".DB_PREFIX."ts_clienti 
			LEFT OUTER JOIN ".DB_PREFIX."ts_job ON id_cliente=cd_cliente 
			GROUP BY id_cliente,cd_cliente,de_nomecliente ORDER BY de_nomecliente";
		$rs = $conn->query($sql) or (trigger_error($conn->error."<br>$sql='{$sql}'"));
		$arFiltri = array("-999"=>"--{choose}--");
		while($riga = $rs->fetch_array()) {
			if ($riga['cd_cliente']=="") $riga['c']=0;
			$arFiltri[$riga['id_cliente']]=$riga['de_nomecliente']." (".$riga['c']." {Jobs})";
		}
		//------------------------------------------------
		$out = "";
		foreach ($arFiltri as $k => $v) { $out.="<option value='{$k}' ".(($k."x"==$def."x")?"selected":"").">{$v}</option>"; }
		return "<select onchange='aggiornaGriglia()' name='combocliente' id='combocliente' class='filter'>{$out}</select>";
	}


	function getHtmlComboAnni($def="", $filter = true) {
		global $conn;
		//------------------------------------------------
		//combo filtri
		$sql = "select distinct(year(dt_inizio)) as a,count(*) as c from ".DB_PREFIX."ts_job group by a order by a";
		$rs = $conn->query($sql) or (trigger_error($conn->error."<br>$sql='{$sql}'"));
		$arFiltri = array("-999"=>"--{choose}--");
		while($riga = $rs->fetch_array()) {
			if ($riga['a']=="") $riga['c']=0;
			$arFiltri[$riga['a']]=$riga['a']. ($filter ? " (".$riga['c']. " {Jobs})" : "");
		}
		//------------------------------------------------
		$out = "";
		foreach ($arFiltri as $k => $v) { $out.="<option value='{$k}' ".(($k."x"==$def."x")?"selected":"").">{$v}</option>"; }
		return "<select onchange='aggiornaGriglia()' name='comboanno' id='comboanno' class='filter'>{$out}</select>";
	}

	function getHtmlComboStati($def="", $filter = true) {
		global $conn;
		//------------------------------------------------
		//combo filtri
		$sql = "select distinct(fl_attivo) as a,count(*) as c from ".DB_PREFIX."ts_job group by a order by a";
		$rs = $conn->query($sql) or (trigger_error($conn->error."<br>$sql='{$sql}'"));
		$arFiltri = array("-999"=>"--{choose}--");
		while($riga = $rs->fetch_array()) {
			if ($riga['a']=="") $riga['c']=0;
			$arFiltri[$riga['a']]=($riga['a']==1?'{ON}':'{OFF}'). ($filter ? " (".$riga['c']." {Jobs})" : "");
		}
		//------------------------------------------------
		$out = "";
		foreach ($arFiltri as $k => $v) { $out.="<option value='{$k}' ".(($k."x"==$def."x")?"selected":"").">{$v}</option>"; }
		return "<select onchange='aggiornaGriglia()' name='combostato' id='combostato' class='filter'>{$out}</select>";
	}


	function getHtmlCercaBox($def="") {
		//------------------------------------------------
		return "<input type='text' name='keyword' id='keyword' value=\"{$def}\"/>";
	}

	function toggleStato($job,$op) {
		global $session,$conn;
		if ($session->get("TSJOB")) {
			if($op>1 || $op<0) $op = 0;
			$sql = "update ".DB_PREFIX."ts_job set fl_attivo='{$op}' where id_job='{$job}'";
			$conn->query($sql) or (trigger_error($conn->error."<br>$sql='{$sql}'"));
			return $op;
		}
		return -1;
	}

}

?>