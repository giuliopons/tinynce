<?php
/*
	Handle log times
*/



class Ore {

	var $gestore;
	var $mesi;
	var $giorni;
	var $shortDays;

	function __construct () {
		global $session,$root,$conn;

		$this->mesi = array("","{January}","{February}","{March}","{April}","{May}","{June}","{July}","{August}","{September}","{October}","{November}","{December}");
		$this->giorni = array("{Sunday}","{Monday}","{Tuesday}","{Wednesday}","{Thursday}","{Friday}","{Saturday}");
		checkAbilitazione("TSORE2","TSORE2");
		$this->shortDays = array("{SUN}","{MON}","{TUE}","{WED}","{THU}","{FRI}","{SAT}");

		
	}
    
    /**
     * format the job based on the color field
     *
     * @param  mixed $colore
     * @return string     style properties
     */
    function getJobStyle($colore) {
        if ($colore=="") { $bg = "#eee"; $fg = "#111";}
        if ($colore!="") { $bg = $colore; $fg = "#fff";}
        if ($colore=="yellow") { $bg = $colore; $fg = "#111";}
        if ($colore=="white") { $bg = $colore; $fg = "#111";}
        if ($colore=="orange") { $bg = $colore; $fg = "#111";}
       return "style='text-decoration:underline;cursor:pointer;background-color:".$bg.";color:".$fg.";'" ;
    }

    function getJobActionUrl($idjob,$from,$to,$client) {
        global $session;
        if($session->get("idprofilo") < 20) 
            return "onclick=\"document.location='../tschehofatto/index.php?op=cerca&dal=".$from."&al=".$to."&cliente=".$client."&job=".$idjob."'\"";
        else
            return "onclick=\"document.location='../tsjob/index.php?op=modifica&id=".$idjob."'\"";
    }


	/**
	 * Retrieve the weekly view 
	 * 
	 * @param string $datainizio        starting date in format Y-m-d
	 * @param string $utente            user id
	 * 
	 * @return string                   the HTML of the view
	 */
	function elenco($datainizio="",$utente='') {
		global $session, $conn;

		if($datainizio == "") $datainizio = date("Y-m-d");
		
		// check for valid date
		if( !checkdate(date("m",strtotime($datainizio)),date("d",strtotime($datainizio)),date("Y",strtotime($datainizio))) ) {
			$datainizio = date("Y-m-d");
		}



		$NUMEROGIORNI = 6;
		$html = "";
		if ($session->get("TSORE2") ) {
			$giorno = date("w",strtotime($datainizio));
			if($giorno==0) $giorno = 7;

			if($giorno!=1) $lunedi = dayadd(1-$giorno,$datainizio); else $lunedi = $datainizio;
			$prevlunedi = dayadd(-7,$lunedi);

			$th = "<th>{Weekly view}</th>";
			$giorni = $this->shortDays;


			//HEADER
			for($i=0;$i<=$NUMEROGIORNI;$i++) {
				$class="";
				$data = dayadd($i,$lunedi);
				$giorno = date("w",strtotime($data));
				if(date("Y-m-d")==$data) {
					$class=" class='oggi'";
				} elseif ($giorno==0 || $giorno ==6) {
					$class=" class='weekend'";
				}

				$th.="<th $class>";
				$gg = date("d",strtotime($data));
				$mm = date("m",strtotime($data));
				$w = $giorni[$giorno];
				$th.= $gg."/".$mm."<br/>".$w;
				$th.="</th>";
			}

			$domenica = dayadd(6,$lunedi);

			$th .= "<th></th>";

			$rs2 = null;

			//JOBS
			$sql = "SELECT distinct id_job,de_nomecliente,de_codice,de_nomejob,cd_tipoora,de_tipoora,de_color,id_cliente FROM `".DB_PREFIX."ts_ore` inner join ".DB_PREFIX."ts_job on cd_job=id_job inner join ".DB_PREFIX."ts_clienti on id_cliente=cd_cliente 
			left outer join ".DB_PREFIX."ts_tipiora on cd_tipoora=id_tipoora
			where cd_utente='".$utente."' and dt_giorno<='$data' and dt_giorno>='$lunedi' order by de_nomecliente,de_nomejob";

			//return $sql;
			$rs=$conn->query($sql) or trigger_error($conn->error . " ".$sql);
			$c=0;
			$tr = "";
			$righe=0;
			$AND = "";
			while($jr = $rs->fetch_array()){
				// per aggiungi recenti velocemente
				$AND.=" AND NOT (cd_job='{$jr['id_job']}' AND cd_tipoora='{$jr['cd_tipoora']}') ";

				// $jr['en_tipo2'] = str_replace(" ","_",$jr['en_tipo']);
				$td= array();
				$c++;
				// $jobtype = $jr['en_tipo'];

                // get the color css
                $colore = $this->getJobStyle($jr['de_color']);

                // get the action
                $action = $this->getJobActionUrl($jr['id_job'],$lunedi,$domenica,$jr['id_cliente']);
                
				// if (strtolower($jobtype)=='ferie') $jobtype = strtoupper($jobtype);
				$tr .= "<tr onmouseover=\"row_highlight(this,'on');\" onmouseout=\"row_highlight(this,'off');\" onclick=\"row_highlight(this,'on');\">
					<th class='jobs'>
						<span data-rel=\"{Code}:<br>".htmlspecialchars($jr['de_codice'].";<br>{Client}:<br>".$jr['de_nomecliente'])."\" class=\"icon-help-circled\"></span>
                        <span class='name' ".$colore." ".$action.">".$jr['de_nomejob']."</span>
                        <span class='desc'>".$jr['de_tipoora']."</span>
                    </th>";
				//preparo array vuoto
				for($i=0;$i<=$NUMEROGIORNI;$i++) {
					$data = dayadd($i,$lunedi);
					$td[date("Ymd",strtotime($data))]['val']="";
					$td[date("Ymd",strtotime($data))]['note']="";
				}
				$sql2 = "select * from ".DB_PREFIX."ts_ore O
					where (O.dt_giorno<='$data' and O.dt_giorno>='$lunedi')
						and O.cd_utente='".$session->get("idutente")."'
						and O.cd_job = '{$jr['id_job']}'
						 and O.cd_tipoora='{$jr['cd_tipoora']}'";
				//scorro risultati e riempio array td
				//return ($sql2);
				$rs2=$conn->query($sql2) or trigger_error($conn->error . " ".$sql2);
				while($r = $rs2->fetch_array()){
					$idar = date("Ymd",strtotime($r['dt_giorno']));
					$td[$idar]['val'] = str_replace(".",",",$r['nu_ore']);
					$td[$idar]['note'] = $r['de_nota'];
					$righe++;
				}

				//scorro array td e buttofuori html
				for($i=0;$i<=$NUMEROGIORNI;$i++) {
					$data = dayadd($i,$lunedi);
					$giorno = date("w",strtotime($data));
					$class="";
					if(date("Y-m-d")==$data) {
						$class="oggi";
					} elseif ($giorno==0 || $giorno ==6) {
						$class="weekend";
					}
					$class.= ($class?" ":"").($datainizio == date("Y-m-d",strtotime($data)) ? "sel" : "");
					$class = " class='".$class."'";
					$idar = date("Ymd",strtotime($data));
					$valore = ($td[$idar]['val']?$td[$idar]['val']:"0");
					if($valore!="0") {
                        $classenota = "icon-comment-empty";

                        if ($td[date("Ymd",strtotime($data))]['note']!="") {
                            $classenota = "icon-comment";
                        }
						$note = "<a title='edita la nota' href=\"javascript:editNote('{$jr['id_job']}','".$idar."','".$jr['cd_tipoora']."')\" class='".$classenota." nota'></a><div id='nota{$jr['id_job']}_{$idar}_".$jr['cd_tipoora']."' style='display:none;' class='notina'>{$td[$idar]['note']}</div> ";
					} else {
						$note = "";
					}

					$tr.="<td $class id='td{$jr['id_job']}_".$idar."_".$jr['cd_tipoora']."'>
					{$note}<a id='a{$jr['id_job']}_".$idar."_".$jr['cd_tipoora']."' href=\"javascript:editThis('{$jr['id_job']}','$idar','{$jr['cd_tipoora']}')\">".$valore."</a>
					</td>";
				}
				$tr.="<td id='jobtot{$jr['id_job']}_".$jr['cd_tipoora']."' class='tot'>tot</td>";


				$tr.="</tr>";
			}
			$tr.="<tr><th class='jobs last'>Totali:</th>";
			for($i=0;$i<=$NUMEROGIORNI;$i++) {
				$data = dayadd($i,$lunedi);
				$idar = date("Ymd",strtotime($data));
				$tr.="<td id='daytot".$idar."' class='tot'>tot</td>";
			}
			$tr.="</tr>";
			//if ($rs2==null) $html .= "<a href='javascript:caricaOreSettimanaPrecedente();' class='prevweek'>IMPORTA LE COMMESSE DELLA SETTIMANA PRECEDENTE</a><br/><br/>";
			if($righe>0) $html .= "<table id='timesheet' class='timesheet'><tr>".$th."</tr>".$tr."</table>";

			/*
				suggerisci righe da aggiungere
			*/
			// aggiungi recenti velocemente
			$sql = "SELECT DISTINCT cd_tipoora, de_nomejob, cd_job, de_codice, de_tipoora FROM 
				( SELECT de_nomejob,de_codice,cd_job,cd_tipoora,de_tipoora,dt_giorno FROM `".DB_PREFIX."ts_ore` 
					inner join ".DB_PREFIX."ts_job on cd_job=id_job
					left outer join ".DB_PREFIX."ts_tipiora on id_tipoora=cd_tipoora
					WHERE cd_utente='".$session->get("idutente")."'
					".$AND."
					AND dt_giorno>'".dayadd(-30,$datainizio)."'
				) as t
			LIMIT 0,9
			";
			$rs = $conn->query($sql) or trigger_error($conn->error . " ".$sql);
			$scadd="";
			while($r=$rs->fetch_array()) {
				$scadd.= "<a class='scadd' href='javascript:;' onclick=\"goSalvaOraNote ('".$r['cd_tipoora']."','".$datainizio."','".$session->get("idutente")."',0,'',".$r['cd_job'].",true,function(){getSettimana('');})\" title=\"".$r['de_nomejob']."\">".$r['de_codice']."<span>".$r['de_nomejob']."</span> <span class='tipo'>".$r['de_tipoora']."</span></a> ";
			}
			if($scadd) {
				$html.="<a class='scadd-title' href='javascript:;' onclick=\"recenti()\" title=\"{Add recent picks}\">+<span>{recent}</span></a> " .$scadd;
			}
			$html.="";

				$html.="<script language='javascript'>idutente = ".$session->get("idutente").";</script>";


		} else {
			$html = "0";
			//$html = "<a href='javascript:caricaOreSettimanaPrecedente();' class='prevweek'>CARICA JOBS SETTIMANA PRECEDENTE</a>";
		}
		return $html;
	}
	
	function caricasettimanaprecedente($datainizio="", $utente="") {
		global $session;
		$html = "";
		$import = false;
		if ($session->get("TSORE2")) {
			if($datainizio == "") $datainizio = date("Y-m-d");
			
			$giornosettimana = date("w", strtotime($datainizio));
			if ($giornosettimana==0) $giornosettimana = 7;
			$datainiziosettimana = strtotime('-' . ($giornosettimana+1) . " day", strtotime($datainizio)) ;
			$dataprecedente = strtotime("-1 week", $datainiziosettimana);
			$datainiziosettimana = date("Y-m-d", $datainiziosettimana);
			$dataprecedente = date("Y-m-d", $dataprecedente);
			
			
			$sql = "SELECT distinct id_job,de_nomecliente,de_codice,de_nomejob,cd_tipoora FROM `".DB_PREFIX."ts_ore` inner join ".DB_PREFIX."ts_job on cd_job=id_job inner join ".DB_PREFIX."ts_clienti on id_cliente=cd_cliente where cd_utente='".$utente."' and dt_giorno<'$datainiziosettimana' and dt_giorno>='$dataprecedente'";

			$rs=$conn->query($sql) or trigger_error($conn->error . " ".$sql);
			while($jr = $rs->fetch_array()){
				$sql2 = "select SUM(nu_ore) as somma from ".DB_PREFIX."ts_ore O
					where (O.dt_giorno<'$datainiziosettimana' and O.dt_giorno>='$dataprecedente')
					and O.cd_utente='".$session->get("idutente")."'
					and O.cd_job = '{$jr['id_job']}' and  cd_tipoora='{$jr['cd_tipoora']}'";
					
				$rs2=$conn->query($sql2) or trigger_error($conn->error . " ".$sql2);
				while($r = $rs2->fetch_array()){
					$ore = floatval($r['somma']);
					if ($ore>0) {
						$this->salvaora($jr['id_job'],$utente,str_replace("-","",$datainizio),'0',$jr['cd_tipoora'],"FORZA_CREAZIONE");
						$import = true;
					}
				}
			}

			if ($import) {
				$html = $this->elenco($datainizio,$utente);
			} else {
				$html = "0";
			}
		} else {
			$html = "0";
		}
		return $html;
	}


	/**
	 * return the weekly hours expected for the logged in user
	 * 
	 * @return mixed
	 */
	function getDayOre() {
		global $session;
		$o =  execute_scalar("select nu_oresettimanali from ".DB_PREFIX."frw_extrauserdata where cd_user='".$session->get("idutente")."'");
		return number_format($o / 5,1);
	}

    /**
     * save default parameter of type of hour for a job for a user
     * 
     * @param int $job
     * @param int $ute
     * @param int $tipoora
     * 
     * @return void
     */
    function saveDefaultParams($job,$ute,$tipoora) {
        global $conn;
        $sql = "DELETE FROM ".DB_PREFIX."ts_default_job_tipi WHERE cd_job='$job' and cd_user='".$ute."'";
        $conn->query($sql) or trigger_error($conn->error . " ".$sql);
        $sql = "INSERT INTO ".DB_PREFIX."ts_default_job_tipi (cd_job,cd_user,cd_type) VALUES ('".($job)."','".($ute)."','".($tipoora)."')";
        $conn->query($sql) or trigger_error($conn->error . " ".$sql);
    }

	function salvaora($job,$ute,$data,$value,$tipoora,$forzacreazione='NON_FORZARE') {
		global $conn,$session;
		$data = substr($data, 0,4)."-".substr($data, 4,2)."-".substr($data, 6,2);
		$value = str_replace(",",".",$value);
		// 19/07/2011
		// il parametro $forzacreazione=='FORZA_CREAZIONE' viene passato solo
		// dalla chiamata "importa job settimana precdente"
		if($value>0 || $forzacreazione=='FORZA_CREAZIONE') {

			$quanti = execute_scalar("select count(*) from ".DB_PREFIX."ts_ore where cd_job='$job' and dt_giorno='$data' and cd_utente='$ute' and cd_tipoora='".($tipoora)."'");

			if($quanti>0) {
				//update
                $sql = "update ".DB_PREFIX."ts_ore set nu_ore='$value' where cd_job='$job' and dt_giorno='$data' and cd_utente='$ute' and cd_tipoora='".($tipoora)."'";
				$conn->query($sql) or trigger_error($conn->error . " ".$sql);
                
			} else {
				//insert
                $reparto_ora = execute_scalar("SELECT cd_reparto FROM ".DB_PREFIX."frw_extrauserdata where cd_user='".$session->get("idutente")."'");
				$sql = "insert into ".DB_PREFIX."ts_ore (nu_ore,cd_job,cd_utente,dt_giorno,cd_tipoora,cd_reparto_ora,de_nota) values ('$value','$job','$ute','$data','".($tipoora)."','".($reparto_ora)."','')";
				$conn->query($sql) or trigger_error($conn->error . " ".$sql);
			}

            $this->saveDefaultParams($job,$ute,$tipoora);

		} else {
			$sql = "delete from ".DB_PREFIX."ts_ore where cd_job='$job' and dt_giorno='$data' and cd_utente='$ute' and cd_tipoora='".addslashes($tipoora)."'";
			//echo $sql;
			$conn->query($sql) or trigger_error($conn->error . " ".$sql);
		}

	}


/**
 * save the text nota of an hour of a job of a person. some data are in the $id param
 *
 * @param  mixed $ute
 * @param  mixed $id         nota206_20220428  contains id job and date
 * @param  mixed $testo
 * @param  mixed $tipoora
 * @return void
 */
function salvanota($ute,$id,$testo,$tipoora) {
	global $conn;
	$idAr = explode("_",$id);
	$data = $idAr[1];
	$data = preg_replace("/[^0-9]/","",$data);
	$data = substr($data, 0,4)."-".substr($data, 4,2)."-".substr($data, 6,2);
	$job = preg_replace("/[^0-9]/","",$idAr[0]);
	$sql = "select count(*) from ".DB_PREFIX."ts_ore where cd_job='$job' and dt_giorno='$data' and cd_utente='$ute' and cd_tipoora='".($tipoora)."'";
	$quanti = execute_scalar($sql);
	if($quanti>0) {
		//update
		$sql = "update ".DB_PREFIX."ts_ore set de_nota='".($testo)."' where cd_job='$job' and dt_giorno='$data' and cd_utente='$ute' and cd_tipoora='".($tipoora)."'";
		$conn->query($sql) or trigger_error($conn->error . " ".$sql);
	}

}

	/**
	 * delete a specific id_ora record from the table log
	 * id user is checked
	 * 
	 * @param int $idora
	 * 
	 * @return void
	 */
	function deleteOra($idora) {
		global $session,$conn;
		if ($session->get("TSORE2") ) {
			$sql = "delete from ".DB_PREFIX."ts_ore where id_ora='$idora' and cd_utente='".$session->get("idutente")."'";
			$conn->query($sql) or trigger_error($conn->error . " ".$sql);
		}
	}


	/**
	 * given a dateinizio and a id utente, return the calendar
	 * with days filled when user has loaded hours
	 * 
	 * @param string $datainizio date YYYY-mm-dd
	 * @param int $idutente
	 * 
	 * @return string HTML
	 */
	function getcal($datainizio,$idutente) {
		if($datainizio == "") $datainizio = date("Y-m-d");
		$ar = preg_split("/-/",$datainizio);
		$data1 = $ar[0]."-".$ar[1]."-01";
		$giorno = date("w",strtotime($data1));
		$lunedi = ($giorno!=1) ? dayadd(1-$giorno,$data1) : $data1;
		
		$data1 = $lunedi;
		$data2 = dayadd(42,$ar[0]."-".$ar[1]."-01");
		$cal = "";
		for ($i=0;$i<42;$i++) {
			$current = dayadd($i,$data1);
			if ($i%7==0) { if ($cal) $cal.="</tr><tr>"; else $cal.="<tr>"; }
			if ($i==0) $cal.="<tr><th class='d'>{MON}</th><th class='d'>{TUE}</th><th class='d'>{WED}</th><th class='d'>{THU}</th><th class='d'>{FRI}</th><th class='d'>{SAT}</th><th class='d'>{SUN}</th></tr>";
			$cal.= "<td>";
			if ( date("m", strtotime( $current ) ) == date ( "m", strtotime( $datainizio) )  ) $class=""; else $class="g";
			$q = execute_scalar("select sum(nu_ore) from ".DB_PREFIX."ts_ore where cd_utente='".$idutente."' and dt_giorno='".$current."'");
			if ($q>= $this->getDayOre() ) $class=$class?$class." full":"full";
				else if ($q>0) $class=$class?$class." mfull":"mfull";
			if (date("Y-m-d")==$current) $class.=($class?" ":"")."oggi";
			if ( date("w",strtotime($current)) == 6 || date("w",strtotime($current)) == 0 ) $class.=($class?" ":"")."weekend";
			
			$cal.= "<a onclick=\"getFormClienteJob(this,event,'".$current."')\" ".($class?"class='$class'":"")." title='".(!$q?"":"$q ore")."' rel=\"".$current."\">";
			$cal.= date ( "d", strtotime( $current ) );
			$cal.= "</a>";
			$cal.= "</td>";
		}
		$cal.="</tr>";
		$locale = $this->mesi[(integer)date("m",strtotime($datainizio ))];
		$cal = "<table id='cal'><tr><th colspan='7'><a href=\"javascript:getcal('".dayadd(-2,$data1)."')\" class='l icon-angle-left'></a>". $locale ."<a href=\"javascript:getcal('".dayadd(1,$data2)."')\" class='r icon-angle-right'></a></th></tr>".$cal."</table>";
		return $cal;
	}

	/**
	 * return the list of hours type for a specific id user (idutente)
	 * the output format is <option><option> list
	 * 
	 * @param int $idutente
	 * @return string HTML
	 */
	function getMieiTipi($idutente) {
		global $conn;
		$mieitipi = "";
		$rs = $conn->query($sql = "select id_tipoora,de_tipoora from ".DB_PREFIX."ts_tipiora A
            inner join ".DB_PREFIX."ts_tbc_tipiore_reparti B on A.id_tipoora = B.cd_tipoora
            inner join ".DB_PREFIX."frw_extrauserdata E on B.cd_reparto = E.cd_reparto
			where cd_user=$idutente") or die($sql." ".$conn->error);
		while($r = $rs->fetch_array()) {
			$mieitipi .= "<option value='".$r['id_tipoora']."'>".$r['de_tipoora']."</option>";
		}
		return $mieitipi;
	}

	/**
	 * return the form used in the Chat to enter hours, it's more compact then
	 * the form in the calendar view
	 * 
	 * @param string $data YYYY-mm-dd
	 * @param int $idutente
	 * @return string HTML
	 */
	function getFormClienteJobCompact($data,$idutente) {
		$locale = $this->giorni[(integer)date("w",strtotime($data ))] ." ". (integer)date("d",strtotime($data )) . " " .
		$this->mesi[(integer)date("m",strtotime($data ))]. " ". (integer)date("Y",strtotime($data ));

		$mieitipi = $this->getMieiTipi($idutente);		
		if($mieitipi=="") {
			return "{Ask your administrator to link your user to a department to use the timesheet.}";
		}

		$out = "<form id='formcliente'>
		<h2>".$locale."</h2>
		<input type='hidden' name='op' value='addore' />
		<input type='hidden' name='giorno' id='giorno' value='{$data}' />
		<input type='hidden' name='idutente' value='{$idutente}' />
		<input type='hidden' name='idora' id=idora value='' />
		<div>
			<div id='clientewrapper' style='display:none'>
				<select name='cliente' id='cliente' onchange='getComboJob(false)'></select>
			</div>
			<div id='jobwrapper' style='display:none'>
				<select name='job' id='job'>
					<option value=''>--{choose}--</option>
				</select>
			</div>
		</div>
		<div>
			<div id='orewrapper' style='display:none'>
				<select name='ore' id='ore'>
					<option value='0.5'>{Half hour}</option>
					<option value='1'>{1 hour}</option>
					<option value='1.5'>{1 hour and half}</option>
					<option value='4'>{Half day}</option>
					<option value='8'>{Whole day}</option>
					<option value='2'>{2 hours}</option>
					<option value='2.5'>{2 hours and half}</option>
					<option value='3'>{3 hours}</option>
					<option value='3.5'>{3 hours and half}</option>
					<option value='4'>{4 hours}</option>
					<option value='4.5'>{4 hours and half}</option>
					<option value='5'>{5 hours}</option>
					<option value='5.5'>{5 hours and half}</option>
					<option value='6'>{6 hours}</option>
					<option value='6.5'>{6 hours and half}</option>
					<option value='7'>{7 hours}</option>
					<option value='7.5'>{7 hours and half}</option>
					<option value='8'>{8 hours}</option>
				</select>
				<select name='tipoora' id='tipoora'>".$mieitipi."</select>
				<br/>
			</div>
		</div>
		<div>
			<div id='notewrapper'>		
				<a href='#' onclick='showNote(this)'><span class='icon-plus'></span> {Add notes}</a>
				<input type='text' name='note' value='' id='note' placeholder=\"{Insert your note here}\" size='40' maxlength='255' style='display:none'/><br/>
			</div>
		</div>
		<div>
			<div id='save'>
				<input type='button' name='invia' value='{Save}' onclick='return salvaOraNoteCompact()'/>
			</div>
		</div>
	</form>
	";
	return $out;
	}

	/**
	 * return the form used in the calendar view
	 * 
	 * @param string $data YYYY-mm-dd
	 * @param int $idutente
	 * @return string HTML
	 */
	function getFormClienteJob($data,$idutente) {

		$locale = $this->giorni[(integer)date("w",strtotime($data ))] ." ". (integer)date("d",strtotime($data )) . " " .
			$this->mesi[(integer)date("m",strtotime($data ))]. " ". (integer)date("Y",strtotime($data ));

		$mieitipi = $this->getMieiTipi($idutente);

		if($mieitipi=="") {
			return "{Ask your administrator to link your user to a department to use the timesheet.}";
		}


		$out = "<form id='formcliente'>
			<h2>".$locale."</h2>
			<h3>{Enter your working hours}</h3>
			<input type='hidden' name='op' value='addore' />
			<input type='hidden' name='giorno' id='giorno' value='{$data}' />
			<input type='hidden' name='idutente' value='{$idutente}' />
			<div id='tipowrapper'>
			</div>
			<div id='clientewrapper' style='display:none'>
				<b>{Client}:</b>
				<select name='cliente' id='cliente' onchange='getComboJob()'>
					<option value=''>--{choose}--</option>
				</select>
			</div>
			<div id='jobwrapper' style='display:none'>
				<b>{Job}:</b>
				<select name='job' id='job'>
					<option value=''>--{choose}--</option>
				</select>
			</div>
			<div id='orewrapper' style='display:none'>
				<b>{Hours}:</b>
				<input type='text' name='ore' value='' id='ore' size='5' maxlength='4'/>
				<select name='tipoora' id='tipoora'>".$mieitipi."</select>
				<br/>
			</div>
			<div id='notewrapper' style='display:none'>
				<b>{Notes}:</b>
				<input type='text' name='note' value='' id='note' size='40' maxlength='255'/><br/>
				<input type='button' name='invia' value='{Save}' onclick='return salvaOraNote()'/>
			</div>
		</form>";
		return $out;
	}

	function getComboCliente($data,$idutente) {
		global $conn;
		//combo filtro clienti
		$sql = "select distinct id_cliente,de_nomecliente from ".DB_PREFIX."ts_clienti inner join ".DB_PREFIX."ts_job on cd_cliente=id_cliente where fl_attivo=1 order by de_nomecliente ";
		$rs = $conn->query($sql) or trigger_error($conn->error . " ".$sql);
		$ar = array();
		while($riga = $rs->fetch_array()) $ar[$riga['id_cliente']]=$riga['de_nomecliente'];
		$out = ""; $def = "";
		foreach ($ar as $k => $v) $out.="<option value=\"{$k}\">{$v}</option>";
		return $out;
	}



	function getComboJob($data,$idutente,$cliente) {
		global $conn;
		//combo filtro job
		$sql = "select id_job,de_nomejob,de_codice,(SELECT cd_type FROM ".DB_PREFIX."ts_default_job_tipi WHERE id_job=cd_job and cd_user='".$idutente."' LIMIT 0,1) as cd_type from ".DB_PREFIX."ts_job 
            where cd_cliente='$cliente' 
            and fl_attivo=1 
			order by de_codice";
			$rs = $conn->query($sql) or trigger_error($conn->error . " ".$sql);
		$ar = array();
		$out = ""; $def = "";
		while($riga = $rs->fetch_array()) {
            $out.="<option value=\"".$riga['id_job']."\" data-rel=\"".$riga['cd_type']."\">".$riga['de_codice']." " . $riga['de_nomejob']."</option>";
        }
		return $out;
	}


	/**
	 * save hours and notes for a user on a job with a specific type of hour
	 * 
	 * @param int $job
	 * @param int $ute
	 * @param string $data    YYYY-mm-dd
	 * @param float $value
	 * @param string $nota
	 * @param int $tipoora
	 * @param bool $forceinsert
	 * 
	 * @return bool
	 * 
	 */
	function salvaoranote($job,$ute,$data,$value,$nota,$tipoora,$forceinsert=false) {
		global $conn,$session;

		$value = str_replace(",",".",$value);
		if (!$job) return;

		if($value>0 || ($forceinsert && $value==0)) {

			$quanti = execute_scalar("select count(*) from ".DB_PREFIX."ts_ore where cd_job='$job' and dt_giorno='$data' and cd_utente='$ute' and cd_tipoora='".$tipoora."'");

			if($quanti>0) {
				//update
				if(trim($nota)!="") {
					$nota_sql = ", de_nota=CONCAT(de_nota,'; ','".($nota)."')";
				} else  {
					$nota_sql="";
				}
				$sql = "update ".DB_PREFIX."ts_ore set nu_ore=nu_ore + '$value' ".$nota_sql." where cd_job='$job' and dt_giorno='$data' and cd_utente='$ute' and cd_tipoora='".$tipoora."'";
				$conn->query($sql) or trigger_error($conn->error . " ".$sql);
			} else {
				//insert
                $reparto_ora = execute_scalar("SELECT cd_reparto FROM ".DB_PREFIX."frw_extrauserdata where cd_user='".$session->get("idutente")."'");
				$sql = "insert into ".DB_PREFIX."ts_ore (nu_ore,cd_job,cd_utente,dt_giorno,de_nota,cd_tipoora,cd_reparto_ora) values ('$value','$job','$ute','$data','".($nota)."','".($tipoora)."','$reparto_ora')";
				//echo $sql;
				$conn->query($sql) or trigger_error($conn->error . " ".$sql);
			}

            $this->saveDefaultParams($job,$ute,$tipoora);

		} else {
			
			$sql = "delete from ".DB_PREFIX."ts_ore where cd_job='$job' and dt_giorno='$data' and cd_utente='$ute' and cd_tipoora='".$tipoora."'";
			$conn->query($sql) or trigger_error($conn->error . " ".$sql);
		}

	}


	/**
	 * returns the HTML with the compiled times for a specific date, used in the Chat form.
	 * 
	 * @param string $data  (date YYYY-mm-dd)
	 * @param int $ute  (iduser)
	 * 
	 * @return string
	 */
	function getCompiledHours($data,$ute) {
		global $session,$conn;
		if ($session->get("TSORE2") ) {
			$sql = "select * from ".DB_PREFIX."ts_ore 
			INNER JOIN ".DB_PREFIX."ts_job on cd_job=id_job
			INNER JOIN ".DB_PREFIX."ts_tipiora on cd_tipoora=id_tipoora
			where dt_giorno='$data' and cd_utente='$ute'";
			$rs = $conn->query($sql) or trigger_error($conn->error . " ".$sql);
			$out = "";
			while($row = $rs->fetch_array()) {
				$out.="<div><a href='#' onclick='editOraCompact(this)'
					data-job='".$row['cd_job'] ."'
					data-tipoora='".$row['cd_tipoora'] ."'
					data-cliente='".$row['cd_cliente'] ."'
					data-idora='".$row['id_ora'] ."'
					data-ore='".$row['nu_ore'] ."'
					data-nota=\"".htmlspecialchars($row['de_nota'])."\"				
					>".$row['de_nomejob']." <b>".$row['nu_ore']."{h}</b> ".$row['de_tipoora']." <span class='icon-pencil'></span></a> <a href='#' onclick='deleteOra(event,".$row['id_ora'] .")'><span class='icon-trash'></span></a></div>";
			}
			return $out;
		}
		return "";
	}

	/**
	 * create the first message of the chat
	 * 
	 * @return string HTML
	 */
	function firstMessage() {
		global $session;
		if ($session->get("TSORE2") ) {
			$saluto = sprintf(translateHtml("{Hi %s, what have you done today?}"), $session->get("nome"));
			return $saluto . "
			 <div>
				<div id='inputarea'></div><div id='compiled'></div>
			</div>";
		}
		return "";

	}

	/**
	 * wrap the message in a div with a class based on the speaker
	 * 
	 * @param array $riga
	 * 
	 * @return string HTML
	 */
	function getMessageHtml($riga) {
		global $session;
		return "<div class='msgwrap ".($riga['fl_bot']?"bot":"user")."'><div class='msg'>".$riga['de_msg']."</div></div>";
		
	}

	
	/**
	 * return the last $limit chat messages
	 * 
	 * @param int $limit
	 * @return string
	 */
	function getChatMessages($limit = 50) {
		global $session,$conn;
		$messages = "0";
		if ($session->get("TSORE2") ) {
			if( execute_scalar("SELECT  count(1) FROM ".DB_PREFIX."ts_chat WHERE cd_user='".$session->get('idutente')."' and dt_saved>='".date("Y-m-d 00:00:00")."'",0) == 0) {
				$msg = $this->firstMessage();
				$conn->query($sql = "INSERT INTO ".DB_PREFIX."ts_chat (cd_user,de_msg,dt_saved,fl_bot) VALUES ('".$session->get('idutente')."','".addslashes($msg)."','".date("Y-m-d H:i:s")."',1)") or trigger_error($conn->error . " ".$sql);
			}
			$sql = "SELECT  * FROM ".DB_PREFIX."ts_chat WHERE cd_user='".$session->get('idutente')."' and dt_saved>='".date("Y-m-d 00:00:00")."' ORDER BY id_chat_msg desc LIMIT 0,$limit";
			$rs = $conn->query($sql) or trigger_error($conn->error . " ".$sql);
			$messages = array();
			while($riga = $rs->fetch_array()) {
				array_unshift($messages, $this->getMessageHtml($riga));
			}
 		}
		return implode("\n",$messages);		
	}


	/**
	 * classify the message using Open AI
	 * 
	 * @param array $message
	 * 
	 * @return array of data and reason
	 */
	function getTypeOfQuestion($message) {
		global $session;

		$nome = $session->get("nome");

		$obj = $this->getAItext(
				"Classify this question of ".$nome.": ".$message['de_msg'], 

				"You are the Assistant of ".$nome.", your name is Timy and you help ".$nome." to fill in its timesheet in the company where he works. Classify ".$nome."'s question with these possibilities: \"INSERT REQUEST\",  \"VERIFY HOURS ALREADY INSERTED\", \"OTHER\". Always extract two values: {\"CLASSIFICATION\", \"DAY\"}. The \"DAY\" parameter refers to the ".$nome."'s question. In your answer first show your reasoning and then add a line with classification in this format: {\"CLASSIFICATION\", \"DAY\"}\n\nHere are some examples of correct answers:\n\n".
				
				"{\"INSERT REQUEST\", \"TODAY\"}\n".
				"{\"INSERT REQUEST\", \"FRIDAY\"}\n".
				"{\"INSERT REQUEST\", \"TOMORROW\"}\n".
				"{\"OTHER\", \"\"}\n".
				"Always use the format specified."
				
				);

		if(isset($obj->choices[0]) && isset($obj->choices[0]->message)) {
			$text = $obj->choices[0]->message->content;

			return $this->extractDataFromAI($text);
			// preg_match_all("/\{([^\}]*)\}/",$text,$matches);
			// $datiStr = (isset($matches[1][0]) ? $matches[1][0] : "" );
			// $ragionamentoStr = str_replace("{".$datiStr."}","", $text);

			// $dati = explode(",", $datiStr);
			// foreach($dati as $k=>$v) $dati[$k]=str_replace("\"", "", $v);

			// return array($dati, $ragionamentoStr);
		}

		return array( array(), "");

	}


	function extractDataFromAI($text) {
		preg_match_all("/\{([^\}]*)\}/",$text,$matches);
		$datiStr = (isset($matches[1][0]) ? $matches[1][0] : "" );
		$ragionamentoStr = str_replace("{".$datiStr."}","", $text);

		$dati = explode(",", $datiStr);
		foreach($dati as $k=>$v) $dati[$k]=str_replace("\"", "", $v);

		return array($dati, $ragionamentoStr);

	}



	/**
	 * this function handle some reasoning with Open.AI before sending an answer to the user.
	 * it could generate more than one request to Open.AI.
	 * 
	 * @param string $id_chat_msg
	 * 
	 * @return string HTML
	 * 
	 */
	function getAnswer($id_chat_msg){
		global $session;
		
		$message = execute_row("SELECT de_msg FROM ".DB_PREFIX."ts_chat WHERE id_chat_msg=$id_chat_msg");
		$res = $this->getTypeOfQuestion($message);

		if (empty($res[0])) {
			return "{Sorry, I don't understand.}";
		} else {
			$dati = $res[0];
			$nome = $session->get("nome");

			if($dati[0]=="INSERT REQUEST") {
				$data = date("Y-m-d",strtotime($dati[1]));
				return translateHtml("{Here is the form:}") . "<div>
					<div id='inputarea' data-rel='".$data."'></div><div id='compiled' data-rel='".$data."'></div>
				</div>";
			} else

			if($dati[0]=="VERIFY HOURS ALREADY INSERTED") {

				$obj2 = $this->getAItext(
					"Examine the question to extract the period request in two dates, FROM and TO. TODAY is ".date("l F j Y")." and the date format of your answer must be YYYY-MM-DD. For example, today is ".date("Y-m-d").". Always extract two values: {\"FROM\", \"TO\"}. Express both as dates in the format YYY-MM-DD. In your answer first show your reasoning and then add a line with classification in this format: {\"FROM\", \"TO\"}\n\nHere are some examples of correct answers:\n\n".
				
					"{\"2023-09-01\", \"2023-09-05\"}\n".
					"{\"2023-10-05\", \"2023-10-12\"}\n".
					"{\"2023-01-01\", \"2023-06-30\"}\n".
					"Always use the format specified.",
					
					"Extract the FROM and TO dates from the question of ".$nome.": ".$message['de_msg'],
				
					"You are the Assistant of ".$nome.", your name is Timy and you help ".$nome." to fill in its timesheet in the company where he works. You have already classified the question of ".$nome." as \"VERIFY HOURS ALREADY INSERTED\". Now you have to help to extract unstructured data from the question. TODAY is ".date("l F j Y")."."
				);

				$text2 = $obj2->choices[0]->message->content;

				
				
				$dati2 = $this->extractDataFromAI($text2)[0];			

				if (isset($dati2[0]) && isset($dati2[1])) {
					
					$dati['op'] = "cerca";
					$dati['dal'] = $dati2[0];
					$dati['al'] = $dati2[1];
					$dati['cliente'] = "";
					$dati['job'] = -2;
					$dati['reparto'] = "";
					$dati['persona'] = $session->get("idutente");
					$dati['gruppo'] = "cd_cliente";

					$objRep = new Report();
					
					$text2 = $objRep->eseguiRicerca($dati, array(
						"download_csv"=>false
					));

					if($text2!="") {
						
						$intro = translateHtml("{Here is your report from %d1 to %d2}:<br><br>");
						$intro = str_replace("%d1", $dati2[0], $intro);
						$intro = str_replace("%d2", $dati2[1], $intro);
						$text2 = $intro. $text2;
					}
				}
				
				return $text2;

			} else

			{
				$obj2 = $this->getAItext("You are the assistant of ".$nome.", your name is Timy. Answer this generic question.","The question of ".$nome." is: ".$message['de_msg'].". Be concise.");
				$text2 = $obj2->choices[0]->message->content;
				return $text2;

				// return "Dovrei rispondere a questa domanda... <b>".implode(", ",$dati)."</b><br>(<i>".$res[1]."</i>)";
			}
			

		}

	}



	/**
	 * receive a message from the user, store it to db chat and get the answer,
	 * then store the answer to db chat
	 * 
	 * @param string $msg
	 * 
	 * @return string HTML
	 */
	function sendChatMessage($msg) {
		global $session,$conn;
		if ($session->get("TSORE2") ) {
			$sql = "INSERT INTO ".DB_PREFIX."ts_chat (cd_user,de_msg,dt_saved,fl_bot) VALUES ('".$session->get('idutente')."','".addslashes($msg)."','".date("Y-m-d H:i:s")."',0)";
			$conn->query($sql) or trigger_error($conn->error . " ".$sql);

			$sql = "INSERT INTO ".DB_PREFIX."ts_chat (cd_user,de_msg,dt_saved,fl_bot) VALUES ('".$session->get('idutente')."','".addslashes( $this->getAnswer($conn->insert_id) )."','".date("Y-m-d H:i:s")."',1)";
			$conn->query($sql) or trigger_error($conn->error . " ".$sql);

			return $this->getChatMessages(2);
		}
	}

	/**
	 * get the chat html template
	 * 
	 * @return string HTML
	 */
	function chat() {
	
		global $session;
		$html = "0";
		if ($session->get("TSORE2") ) {
			$html = loadTemplateAndParse ("template/chat.html");
			$html = str_replace("##MESSAGES##", $this->getChatMessages(), $html);
			$html = str_replace("\$_param_idutente_", $session->get("idutente"), $html);
			$html = str_replace("\$_param_data_", date("Y-m-d"), $html);
		}
		return $html;

	}



	/**
	 * get AI generatd text from Open AI (ChatGPT)	
	 * 
	 * @param string $prompt
	 * @param string $context
	 * 
	 * @return object from API call
	 * 
	 */
	function getAItext($prompt, $context="") {
		
		$options["temperature"] = 70 / 100;
		$options["model"] = 'gpt-3.5-turbo';
		$options["maxtokens"] = 500;
		$options["top_p"] = 1;
		$options["apikey"] = OPENAI_API_KEY;

		//
		// create request object
		$obj = new stdClass();
		$obj->model = $options["model"];
		$message = array();
		$message[] = new stdClass();
		$message[0] = array("role" => "system", "content" => $context);
		$message[1] = array("role" => "user", "content" => $prompt);
		$obj->messages = $message;
		$obj->temperature = $options["temperature"];
		$obj->frequency_penalty = 0;
		$obj->presence_penalty = 0;
		$obj->max_tokens = (integer)$options["maxtokens"];
		$obj->top_p = $options["top_p"];

		$r = json_encode($obj);

		$ch = curl_init();
	
		curl_setopt($ch, CURLOPT_URL, 'https://api.openai.com/v1/chat/completions');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $r);

		$headers = array();
		$headers[] = 'Content-Type: application/json';
		$headers[] = 'Authorization: Bearer ' . $options["apikey"];
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

		$result = curl_exec($ch);

		if (curl_errno($ch)) {
			echo 'Error:' . curl_error($ch);
		}
	
		curl_close($ch);

		$obj = json_decode($result);

		return $obj;
	}


}

?>