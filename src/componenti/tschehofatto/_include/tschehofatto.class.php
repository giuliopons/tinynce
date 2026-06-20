<?php
/*
	class to show my timesheet details
*/

class MyOwnTimesheet {

	var $gestore;

	var $arStati;

	function __construct() {
		global $session,$root,$conn;
		$this->gestore = $_SERVER["PHP_SELF"];

		checkAbilitazione("TSCHEHOFATTO","TSCHEHOFATTO");


	}

	function getPannello($dati) {
		global $session,$root,$conn;
		$html = "";

		if ($session->get("TSCHEHOFATTO")) {

			// building the form to query db
			$objform = new form();
			
			$datainizio = date("Y-m-d",strtotime("-7 days"));
			$giorno = date("w",strtotime($datainizio));
			

			if($giorno!=1 && $giorno!=0) {
				// find the first monday
				$lunedi = todayadd(1-$giorno);
			} else {
				$lunedi = $datainizio;
			}


			$valore = isset($dati["dal"])?$dati["dal"]:"";
			if ($valore=="") $valore = $lunedi;
			$dal = new data("dal",$valore,"aaaa-mm-gg",$objform->name);
			$dal->obbligatorio=1;
			$dal->label="'Dal'";
			$objform->addControllo($dal);

			$valore = isset($dati["al"])?$dati["al"]:"";
			if ($valore=="") $valore = date("Y-m-d");
			$al = new data("al",$valore,"aaaa-mm-gg",$objform->name);
			$al->obbligatorio=1;
			$al->label="'Al'";
			$objform->addControllo($al);

			//------------------------------------------------
			//combo clients
			$sql = "select id_cliente,de_nomecliente from ".DB_PREFIX."ts_clienti order by de_nomecliente";
			$rs = $conn->query($sql) or die($conn->error.$sql);
			$arClienti[""]="{All}";
			while($riga = $rs->fetch_array()) {
				$arClienti[$riga['id_cliente']]=$riga['de_nomecliente'];
			}
			//------------------------------------------------
			$cliente = new optionlist("cliente",( (isset($dati["cliente"])?$dati["cliente"]:"") ) ,$arClienti);
			$cliente->obbligatorio=0;
			$cliente->label="'Client'";
			$cliente->attributes=" onchange=\"loadjobs(this)\" class='filter'";
			$objform->addControllo($cliente);



			//------------------------------------------------
			//combo jobs
			$sql = "select id_job,de_nomejob from ".DB_PREFIX."ts_job where cd_cliente='".(isset($dati["cliente"])?$dati["cliente"]:"")."' order by de_nomejob";
			$rs = $conn->query($sql) or trigger_error($conn->error." ".$sql);
			$arJob[""]="{All}";
			while($riga = $rs->fetch_array()) {
				$arJob[$riga['id_job']]=$riga['de_nomejob'];
			}
			//------------------------------------------------
			$job = new optionlist("job",((isset($dati["job"])?$dati["job"]:"")),$arJob);
			$job->obbligatorio=0;
			$job->label="'Job'";
			$job->attributes=" class='filter'";
			$objform->addControllo($job);

			$persona = new hidden("persona", $session->get("idutente"));

			$submit = new submit("cerca","cerca");
			$op = new hidden("op","cerca");

			$html = loadTemplateAndparse ("template/elenco.html");

			$html = str_replace("##STARTFORM##", $objform->startform(), $html);
			$html = str_replace("##op##", $op->gettag(), $html);
			$html = str_replace("##SUBMIT##", "<a href='javascript:checkForm();' class='btn'>{Find}</a>", $html);
			$html = str_replace("##cliente##", $cliente->gettag(), $html);
			$html = str_replace("##job##", $job->gettag(), $html);
			$html = str_replace("##persone##", $persona->gettag(), $html);
			$html = str_replace("##dal##", $dal->gettag(), $html);
			$html = str_replace("##al##", $al->gettag(), $html);
			$html = str_replace("##gestore##", $this->gestore, $html);
			$html = str_replace("##ENDFORM##", $objform->endform(), $html);

			
			if(isset($dati['op']) && $dati['op']=='cerca') {
				$html = str_replace("##corpo##", $this->eseguiRicerca($dati), $html);
			} else {
				$html = str_replace("##corpo##", "", $html);
			}


		} else {
			$html = "0";
		}
		return $html;
	}

	function eseguiRicerca($dati) {

		global $conn,$session;

		// $job= getVarSetting('JOB_NON_ATTRIBUIBILE');
		
		$sql="SELECT id_job,(select g.de_nomereparto from ".DB_PREFIX."ts_reparti g  where g.id_reparto=h.cd_reparto) as reparto, b.nome as nome,b.cognome as cognome,c.dt_giorno,c.nu_ore as ore, e.de_nomecliente as cliente,d.de_nomejob as commessa ,  f.de_tipoora as tipologia,c.de_nota as descrizione,h.nu_costo*c.nu_ore as costo
		FROM ".DB_PREFIX."frw_utenti b,".DB_PREFIX."ts_job d, ".DB_PREFIX."ts_clienti e,".DB_PREFIX."frw_extrauserdata h,".DB_PREFIX."ts_ore c
		LEFT JOIN ".DB_PREFIX."ts_tipiora f on f.id_tipoora=c.cd_tipoora
		where c.cd_utente=b.id 
		and d.id_job=c.cd_job
		and d.cd_cliente=e.id_cliente #altriwhere# 
		and h.cd_user=b.id order by dt_giorno,1,2,3,4,5,6,7,8,9";
		$altriwhere = "";
		if($dati['cliente']!='') {
			$altriwhere.=" and e.id_cliente = '{$dati['cliente']}'";
		}
		if($dati['job']) {
			$altriwhere.=" and d.id_job='".$dati['job']."' ";
		}
		// if($dati['persona']) {
			$altriwhere.=" and b.id='".$session->get("idutente")."' ";
		// }
		if($dati['dal']) {
			$altriwhere.=" and c.dt_giorno>='".$dati['dal']."' ";
		}
		if($dati['al']) {
			$altriwhere.=" and c.dt_giorno<='".$dati['al']."' ";
		}
		$sql = str_replace("#altriwhere#",$altriwhere,$sql);

		$rs = $conn->query($sql) or trigger_error($conn->error." ".$sql);
		$out = "";
		//id_cliente 	de_nomecliente 	id_job 	de_codice 	de_nomejob 	dt_inizio 	dt_fine 	cd_cliente 	id_ora 	cd_utente 	de_nota 	cd_job 	nu_ore 	dt_giorno 	id 	username 	password 	nome 	cognome 	fl_attivo 	cd_profilo

		//$job = "";
		$sommaore = 0;
		$sommacosto = 0;
		

		$header = "";
		$c = 0;
		$sommatutteore = 0;
		        $out="<tr>";
			$out.="<th>{Department}</th>";
			$out.="<th>{Name}</th>";
			$out.="<th>{Date}</th>";
			$out.="<th>{Hours}</th>";
			$out.="<th>{Client}</th>";
			$out.="<th>{Job}</th>";
			//$out.="<th>CAUSALE</th>";
			$out.="<th>{Hour type}</th>";
			$out.="<th>{Description}</th>";
			// $out.="<th>{Cost}</th>";
			$out.="</tr>";
		while($r=$rs->fetch_array()) {

			$out.="<tr>";
			$out.="<td>".$r['reparto']."</td>";
			$out.="<td>".$r['nome']." ".$r['cognome']."</td>";
			$out.="<td>".datef($r['dt_giorno'])."</td>";
			$out.="<td class='n'>".numberf($r['ore'],1)."</td>";
			$out.="<td>".$r['cliente']."</td>";
			// $out.="<td>".($job == $r['id_job'] ? "" : $r['commessa'])."</td>";
			$out.="<td>".$r['commessa']."</td>";
			// $out.="<td>".($job == $r['id_job'] ? $r['tipo'] : $r['tipologia'])."</td>";
			$out.="<td>".$r['tipologia']."</td>";
			//$out.="<td>".$r['tipologia']."</td>";
			$out.="<td>".$r['descrizione']."</td>";
			// $out.="<td>".numberf($r['costo'],2).MONEY."</td>";
			$out.="</tr>";
			
			
//			if($header=="") {
//				$header = "<tr><th class='cli' colspan='4'>".$r['de_nomecliente']."</th></tr>";
//			}
			/*if($job!= $r['de_codice']." ".$r['de_nomejob']) {
				if($c>0) {
					$out.="<tr><th>Totale*</th>";
					$out.="<td class='n'>".number_format($sommaore,1)."h "."</td>";
					$out.="<td class='n'>".number_format($sommagiorni,0)."g "."</td>";
					$out.="<td class='n'>".number_format($sommaeuri,2,',','.')."&euro; "."</td></tr>";
					$sommaore = 0;
					$sommagiorni = 0;
					$sommaeuri = 0;
				}
				$job = $r['de_codice']." ".$r['de_nomejob'];
				$out.="<tr><th colspan='4' class='sep'>&nbsp;</th></tr>
					<tr><th colspan='4' class='job'>$job - <b>".$r['de_nomecliente']."</b></th></tr>";
				$out.="<tr><th>Persona</th><th>Ore*</th><th>Giornate</th><th>Costo</th></tr>";
			}
			$out.="<tr><td>".$r['cognome']." ".$r['nome']."</td>";
			$out.="<td class='n'>".number_format($r['tot'],1)." "."</td>";
			$out.="<td class='n'>".number_format($r['tot']/8,0)." "."</td>";
			$out.="<td class='n'>".number_format($r['euri'],2,',','.')." "."</td></tr>";*/

			$sommaore += $r['ore'];
			
			// $sommacosto+= $r['costo'];
			$c++;
		}
		if($c>0) {
			$out.="<tr>";
		
			$out.="<th class='n' colspan='3'>{Total time}</th>";
			$out.="<td class='n' >".numberf($sommaore,1)."h "."</td>";
            $out.="<th class='n' colspan='4'></th>";
			// $out.="<th class='n' colspan='4'>{Total cost}*</th>";
			// $out.="<td class='n' >".numberf($sommacosto,2).MONEY."</td>";
			$out.="</tr>";
			$sommaore = 0;
			$sommagiorni = 0;
			$sommaeuri = 0;
		}
		return "<table id='report' class='griglia'>".$header.$out."</table>";
	}

	/*
		mostra il dettaglio.
		ritorna 0 se l'utente non � abilitato, altrimenti restituisce l'html.
	
	function getDettaglio($id="") {
		global $session,$root;

		if ($session->get("TSCLIENTI")=="true") {
			if ($id!="") {
				//modifica
				
				$dati = $this->getDati($id);
				$action = "modificaStep2";
			} else {
				//inserimento
				
				$dati = array("id_cliente"=>"",
					"de_nomecliente"=>"");
				$action = "aggiungiStep2";
			}


			//costruzione form
			$objform = new form();
			$objform->pathJsLib = $root."template/controlloform.js";

			$de_nomecliente = new testo("de_nomecliente",($dati["de_nomecliente"]),50,50);
			$de_nomecliente->obbligatorio=1;
			$de_nomecliente->label="'Nome del cliente'";
			$objform->addControllo($de_nomecliente);

			$id_cliente = new hidden("id",$dati["id_cliente"]);
			$op = new hidden("op",$action);

			$submit = new submit("invia","salva");

			$html = loadTemplate("template/dettaglio.html");

			$html = str_replace("##STARTFORM##", $objform->startform(), $html);
			$html = str_replace("##id##", $id_cliente->gettag(), $html);
			$html = str_replace("##op##", $op->gettag(), $html);
			$html = str_replace("##submit##", $submit->gettagimage($root."images/salva.gif"," Salva"), $html);
			$html = str_replace("##de_nomecliente##", $de_nomecliente->gettag(), $html);
			$html = str_replace("##gestore##", $this->gestore, $html);
			$html = str_replace("##ENDFORM##", $objform->endform(), $html);

		} else {
			$html = "0";
		}
		return $html;
	}
	*/

	function getDati($id) {
		return execute_row("SELECT * from ".DB_PREFIX."ts_clienti where id_cliente='{$id}'");
	}


	/*function updateAndInsert($arDati) {
		// in:
		// arDati--> array POST del form
		// risultato:
		//	"" --> ok
		//	"1" --> nome gia' utilizzato da un altro componente
		//  "0" --> il tuo profilo non ti consente l'inserimento/modifica

		global $session, $conn;
		if ($session->get("TSCLIENTI")=="true") {
			if ($arDati["id"]!="") {
				//Modifica
				$sql="UPDATE ".DB_PREFIX."ts_clienti set de_nomecliente='##de_nomecliente##' where id_cliente='##id_cliente##'";
				//";
				$sql= str_replace("##de_nomecliente##",$arDati["de_nomecliente"],$sql);
				$sql= str_replace("##id_cliente##",$arDati["id"],$sql);
				$conn->query($sql) or (trigger_error($conn->error."<br>$sql='{$sql}'"));

				$numero = $arDati["id"];
				$html= "";

			} else {
				//Inserimento
				
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
	}*/

	/*function deleteItem($id) {
		// in:
		// id --> id tipo da cancellare
		// risultato:
		//	"" --> ok
		//  "0" -->il tuo profilo non ti consente la cancellazione

		global $session, $conn;
		if ($session->get("TSCLIENTI")=="true") {

			$jobs = concatenaId("select id_job from ".DB_PREFIX."ts_job where cd_cliente = '$id'");
			if($jobs<>"") {
				$sql = "delete from ".DB_PREFIX."ts_ore where cd_job in ($jobs)";
				$conn->query($sql) or (trigger_error($conn->error."<br>$sql='{$sql}'"));

				$sql = "delete from ".DB_PREFIX."ts_tbc_utenti_job where cd_job in ($jobs)";
				$conn->query($sql) or (trigger_error($conn->error."<br>$sql='{$sql}'"));

				$sql = "delete from ".DB_PREFIX."ts_job where cd_cliente='$id'";
				$conn->query($sql) or (trigger_error($conn->error."<br>$sql='{$sql}'"));

			}


			$sql = "delete from ".DB_PREFIX."ts_clienti where id_cliente='{$id}'";
			$conn->query($sql) or (trigger_error($conn->error."<br>$sql='{$sql}'"));

			$html = "";
		} else {
			$html="0";		//il tuo profilo non ti consente di cancellare
		}
		return $html;
	}*/


	/*function getHtmlComboClienti($def="") {
		//------------------------------------------------
		//combo filtri
		global $conn;
		$sql = "select id_cliente,cd_cliente,de_nomecliente,count(*) as c from ".DB_PREFIX."ts_clienti left outer join ".DB_PREFIX."ts_job on id_cliente=cd_cliente group by id_cliente,cd_cliente,de_nomecliente order by de_nomecliente";
 		$rs = $conn->query($sql) or (trigger_error($conn->error."<br>$sql='{$sql}'"));
		$arFiltri = array(""=>"--{all}--");
		while($riga = $rs->fetch_array()) {
			if ($riga['cd_cliente']=="") $riga['c']=0;
			$arFiltri[$riga['id_cliente']]=$riga['de_nomecliente']." (".$riga['c']." job)";
		}
		//------------------------------------------------
		$out = "";
		foreach ($arFiltri as $k => $v) { $out.="<option value='{$k}' ".(($k."x"==$def."x")?"selected":"").">{$v}</option>"; }
		return "<select onchange='aggiornaGriglia()' name='combocliente' id='combocliente' class='filter'>{$out}</select>";
	}*/

}

?>