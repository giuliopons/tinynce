<?php
/*
    manages planning assignment and show planning
 
*/
class Planning {
	private $tbdb;	//tabella del database che contiene i dati
	private $gestore;
    private $shortDays = array("{SUN}","{MON}","{TUE}","{WED}","{THU}","{FRI}","{SAT}");
    private $editing_level = 10;
    private $USE_JOBS_DATES = false;    // if true, only jobs with dates inizio and fine matching the currentDate are shown

    private $SLOTS = 2;
    private $HOURS_PER_DAY = 8;
    private $ALLOW_JOBS_FOR_EVER = false;
        // if true the field "dt_week_or_ever" can be null and jobs will be shown for ever in planning job view.
        // there is no switch in UI and jobs could be manually set to "forever" by putting null in the dt_week_or_ever in db,
        // can be useful for "ferie", "malattie"...

	public function __construct () {
		$this->gestore = $_SERVER["PHP_SELF"];
		$this->tbdb = "ts_planning";
		checkAbilitazione("TSPLANNING","TSPLANNING");
	}

    public function peopleView($currentDate) : String {
        global $session,$conn;
        $html = "0";
        if ($session->get("TSPLANNING")) {
            $html = loadTemplateAndParse("template/elenco.html");
            
            $out = $this->showPeopleHtml( $currentDate );
            if( $out == "" ) {
                $msg = sprintf(translateHtml("{Use (+) button to add todos in week from %s}"), date("d/m/Y", strtotime($currentDate)));
                $out = "<div class='day-user' data-date='".$currentDate."'></div>" . "<p>".$msg."</p>";
            }
            $html = str_replace("##corpo##", $out, $html);


        }
        return $html;
    }

    public function jobsView($currentDate) : String {
        global $session,$conn;
        $html = "0";
        if ($session->get("TSPLANNING") && $session->get("idprofilo") >= $this->editing_level) {
            $html = loadTemplateAndParse("template/elenco.html");
            $out = $this->showTodosHtml( $currentDate );
            if( $out == "" ) {
                $msg = sprintf(translateHtml("{Use (+) button to add todos in week from %s}"), date("d/m/Y", strtotime($currentDate)));
                $out = "<div class='day-user' data-date='".$currentDate."'></div>" . "<p>".$msg."</p>";
            }
            $html = str_replace("##corpo##", $out, $html);
        }
        return $html;
    }




    private function getDatiTodo( $id ) : Array {
        global $conn;
        $sql = "SELECT ".DB_PREFIX."ts_todos.*, cd_cliente FROM ".DB_PREFIX."ts_todos INNER JOIN ".DB_PREFIX."ts_job ON cd_job=id_job WHERE id_todo='$id'";
        // echo $sql;
        // die;
        return execute_row($sql, []);
    }

    private function getDatiPeople( $id ) : Array {
        global $conn;
        return execute_row("SELECT ".DB_PREFIX."ts_planning.* FROM ".DB_PREFIX."ts_planning WHERE id_planning='$id'", []);
    }

    /**
     * get the form used to add a to do task
     */
    public function getDettaglioAddTodo( $requestData ) : String {
        global $session,$conn;
        $html = "0";
        if ($session->get("TSPLANNING") && $session->get("idprofilo") >= $this->editing_level) {

            // check if currentDate in yyyy-mm-dd is a valid date
            $currentDate = $requestData["currentDate"] ?? date("Y-m-d");
            if (!checkdate(substr($currentDate,5,2),substr($currentDate,8,2),substr($currentDate,0,4))) {
                $currentDate = date("Y-m-d");
            }
            
            $requestData["id"] = intval($requestData["id"] ?? 0);
            if ($requestData["id"] > 0) {
                $dati = $this->getDatiTodo($requestData["id"]);
            } else {
                $dati = getEmptyNomiCelleAr(DB_PREFIX."ts_todos");
                $dati["cd_cliente"] = "";
            }
            
            $requestData["id_job"] = intval($requestData["id_job"] ?? 0);
            if( $requestData["id_job"] > 0 ) {
                $dati["cd_job"] = $requestData["id_job"];
                $dati["cd_cliente"] = execute_scalar("select cd_cliente from ".DB_PREFIX."ts_job where id_job='{$requestData["id_job"]}'", 0 );
            }


            $html = "
            
                ##STARTFORM##
                ##id##
                ##op##
                ##currentDate##

                <table>
                <tr>
                    <td width=100 style='vertical-align:middle'>{Client} *</td>
                    <td>##cd_cliente##</td>
                </tr>
                <tr>
                    <td width=100 style='vertical-align:middle'>{Job}*</td>
                    <td>##cd_job##</td>
                </tr>
                <tr>
                    <td width=100 style='vertical-align:middle'>{Task}*</td>
                    <td>##de_label##</td>
                </tr>
                </table>
                </div>
                
                ##ENDFORM##
            
            ";

            //costruzione form
            $objform = new form();
            $objform->pathJsLib = '';
            $de_label = new areatesto("de_label",($dati["de_label"]),3,50);
            $de_label->maxlimit=255;
            $de_label->obbligatorio=1;
            $de_label->label="'{Task}'";
            $objform->addControllo($de_label);

            $id_todo = new hidden("id",$dati["id_todo"]);
            $op = new hidden("op","add");
            $currentDateField = new hidden("currentDate",$currentDate);
            $submit = new submit("invia","salva");

            //------------------------------------------------
            //combo lists
            $sql = "select distinct id_cliente,de_nomecliente from ".DB_PREFIX."ts_clienti A inner join ".DB_PREFIX."ts_job B on cd_cliente=id_cliente where fl_attivo=1 ".($this->USE_JOBS_DATES ? "and dt_inizio<='$currentDate' and dt_fine>='$currentDate'" : "")." order by de_nomecliente";
            $cd_cliente = new optionlist("cd_cliente",$dati["cd_cliente"]);
            $cd_cliente->loadSqlOptions($sql, "id_cliente", "de_nomecliente", "--{choose}--");
            $cd_cliente->obbligatorio=1;
            $cd_cliente->attributes="onchange=\"loadList('cd_cliente', 'cd_job')\"";
            $cd_cliente->label="'{Client}'";
            $objform->addControllo($cd_cliente);        

            $sql = "select distinct id_job,CONCAT(de_codice,' ',de_nomejob) as nomejob from ".DB_PREFIX."ts_job where (cd_cliente='{$dati["cd_cliente"]}' and fl_attivo=1 ".($this->USE_JOBS_DATES ? "and dt_inizio<='$currentDate' and dt_fine>='$currentDate'" : "").") or (id_job='{$dati["cd_job"]}') order by nomejob";
            $cd_job = new optionlist("cd_job",$dati["cd_job"]);
            $cd_job->loadSqlOptions($sql, "id_job", "nomejob", "--{choose}--");
            $cd_job->obbligatorio=1;
            $cd_job->label="'{Job}'";
            $objform->addControllo($cd_job);    
            
            $html = str_replace("##STARTFORM##", $objform->startform(), $html);
            $html = str_replace("##id##", $id_todo->gettag(), $html);
            $html = str_replace("##op##", $op->gettag(), $html);
            $html = str_replace("##currentDate##", $currentDateField->gettag(), $html);
            $html = str_replace("##cd_cliente##", $cd_cliente->gettag(), $html);
            $html = str_replace("##cd_job##", $cd_job->gettag(), $html);
            $html = str_replace("##de_label##", $de_label->gettag(), $html);
            
            $html = str_replace("##gestore##", $this->gestore, $html);
            $html = str_replace("##ENDFORM##", $objform->endform(), $html);

        }
        return $html;
    }



    /**
     * extract the pop up used to edit a planning of a people
     */
    public function getDettaglioAddPeopleAllInOne( $requestData ) : String {
        global $session,$conn;
        $html = "0";
        // op: getformall
        // id: 54    (planning)
        // cd_user: 177
        // currentDate: 2024-11-19
        // cd_todo: 8
        // nu_hours: 4
        if ($session->get("TSPLANNING") && $session->get("idprofilo") >= $this->editing_level) {

            // check if currentDate in yyyy-mm-dd is a valid date
            $currentDate = $requestData["currentDate"] ?? date("Y-m-d");
            if (!checkdate(substr($currentDate,5,2),substr($currentDate,8,2),substr($currentDate,0,4))) {
                $currentDate = date("Y-m-d");
            }


            $requestData["id"] = intval($requestData["id"] ?? 0);
            if ($requestData["id"] > 0) {
                $dati = $this->getDatiPeople($requestData["id"]);
            } else {
                $dati = getEmptyNomiCelleAr(DB_PREFIX."ts_planning");
            }
            if ($requestData["cd_todo"] > 0) {
                $dati2 = $this->getDatiTodo($requestData["cd_todo"]);
                $dati2["cd_cliente"] = execute_scalar("SELECT cd_cliente FROM ".DB_PREFIX."ts_todos INNER JOIN ".DB_PREFIX."ts_job ON cd_job=id_job WHERE id_todo='".$requestData["cd_todo"]."'",-1);
                // print_r($dati2);
                // die;
            } else {
                $dati2 = getEmptyNomiCelleAr(DB_PREFIX."ts_todos");
                $dati2["cd_cliente"] = 0;
            }
            $dati['cd_user'] = $requestData["cd_user"];
            $dati = array_merge($dati,$dati2);         

            $html = "
            
                ##STARTFORM##
                ##id##
                ##op##
                
                <table>
                <tr>
                    <td width=100 style='vertical-align:middle'>{Date}*</td>
                    <td>##currentDate## &nbsp; ##toDate##</td>
                </tr>
                <tr>
                    <td width=100 style='vertical-align:middle'>{Client}*</td>
                    <td>##cd_client##</td>
                </tr>
                <tr>
                    <td width=100 style='vertical-align:middle'>{Job}*</td>
                    <td>##cd_job##</td>
                </tr>
                <tr>
                    <td width=100 style='vertical-align:middle'>{Task}*</td>
                    <td>##cd_todo####de_label## <span id='pencil' class='icon-pencil' style='cursor:pointer' onclick='editTodo(this)'></span></td>
                </tr>
                <tr>
                    <td width=100 style='vertical-align:middle'>{Person}*</td>
                    <td>##cd_user##</td>
                </tr>
                <tr>
                    <td style='vertical-align:middle'>{Hours}*</td>
                    <td>##nu_hours##</td>
                </tr>
                </table>
                </div>
                
                ##ENDFORM##
            
            ";

            //costruzione form
            $objform = new form();
            $objform->pathJsLib = '';

            $hours = [];
            foreach( range(1, $this->SLOTS) as $k) {
                $hours[ $this->HOURS_PER_DAY / $this->SLOTS * $k] = "{" . ($this->HOURS_PER_DAY / $this->SLOTS * $k) . " hours}";
            }

            $nu_hours = new optionlist("nu_hours",$dati["nu_hours"], $hours);
			$nu_hours->obbligatorio=1;
			$nu_hours->label="'{Hours}'";
			$objform->addControllo($nu_hours);

            $id = new hidden("id",$requestData["id"]);
            $op = new hidden("op","add");
            $de_label = new areatesto("de_label","",3,50);
            $de_label->maxlimit=255;
            $de_label->attributes='style="display:none" placeholder="{Add a new task}"';
            $objform->addControllo($de_label, "checkCdTodoOrLabel()" ,"{Missing task}");

            $currentDateField = new data("currentDate",$currentDate,"aaaa-mm-gg");
            $toDateField = new data("toDate",$currentDate,"aaaa-mm-gg");
            $submit = new submit("invia","salva");

            //------------------------------------------------
            // combo list utenti (exclude people from AMMINISTRAZIONE/SUIPPORTO reparto 2) 
            $sql = "select CONCAT(cognome,' ',nome) as nome,id as cd_user from ".DB_PREFIX."frw_utenti 
                left outer join ".DB_PREFIX."frw_extrauserdata on cd_user=id
                where fl_attivo=1 
                and cd_reparto <> 2
                order by cognome,nome";
            $cd_user = new optionlist("cd_user",$dati["cd_user"]);
            $cd_user->loadSqlOptions($sql, "cd_user", "nome", "--{choose}--");
            $cd_user->obbligatorio=1;
            $cd_user->label="'{Person}'";
            $objform->addControllo($cd_user);    

            //------------------------------------------------
            //combo list clienti
            $sql = "select distinct id_cliente,de_nomecliente from ".DB_PREFIX."ts_clienti A inner join ".DB_PREFIX."ts_job B on cd_cliente=id_cliente 
                where 
                    (B.fl_attivo=1 ".($this->USE_JOBS_DATES ? " and  B.dt_inizio<='$currentDate' and B.dt_fine>='$currentDate'" : "").") 
                or (cd_cliente='{$dati["cd_cliente"]}') order by de_nomecliente";
            // print_r($dati);
            // die;
            $cd_cliente = new optionlist("cd_cliente",$dati["cd_cliente"]);
            $cd_cliente->loadSqlOptions($sql, "id_cliente", "de_nomecliente", "--{choose}--");
            $cd_cliente->obbligatorio=1;
            // $cd_cliente->attributes="onchange='loadJobs()'";
            $cd_cliente->attributes="onchange=\"loadList('cd_cliente', 'cd_job', '')\"";
            $cd_cliente->label="'{Client}'";
            $objform->addControllo($cd_cliente);        

            //------------------------------------------------
            //combo list progetti
            $sql = "select distinct id_job,CONCAT(de_codice,' ',de_nomejob) as nomejob from ".DB_PREFIX."ts_job where (cd_cliente='{$dati["cd_cliente"]}' and fl_attivo=1 
            ".($this->USE_JOBS_DATES ? " and  dt_inizio<='$currentDate' and dt_fine>='$currentDate'" : "")."
            ) or (id_job='{$dati["cd_job"]}') order by nomejob";
            $cd_job = new optionlist("cd_job",$dati["cd_job"]);
            $cd_job->loadSqlOptions($sql, "id_job", "nomejob", "--{choose}--");
            $cd_job->attributes="onchange=\"loadList('cd_job', 'cd_todo', 'de_label')\"";
            $cd_job->obbligatorio=1;
            $cd_job->label="'{Job}'";
            $objform->addControllo($cd_job);

            //------------------------------------------------
            //combo list todo (tasks)
            $sql = "select distinct id_todo,de_label from ".DB_PREFIX."ts_todos where (cd_job='{$dati["cd_job"]}') order by de_label";
            $cd_todo = new optionlist("cd_todo",$dati["id_todo"]);
            $cd_todo->loadSqlOptions($sql, "id_todo", "de_label", "--{choose}--");
            $cd_todo->obbligatorio=1;
            $cd_todo->label="'{Task}'";
            $objform->addControllo($cd_todo);

            
 
            // form objects replaces
            $html = str_replace("##STARTFORM##", $objform->startform(), $html);
            $html = str_replace("##id##", $id->gettag(), $html);
            $html = str_replace("##op##", $op->gettag(), $html);
            $html = str_replace("##currentDate##", $currentDateField->gettag(), $html);
            $html = str_replace("##toDate##", $toDateField->gettag(), $html);
            $html = str_replace("##cd_client##", $cd_cliente->gettag(), $html);
            $html = str_replace("##cd_job##", $cd_job->gettag(), $html);
            $html = str_replace("##cd_todo##", $cd_todo->gettag(), $html);
            $html = str_replace("##de_label##", $de_label->gettag(), $html);
            $html = str_replace("##cd_user##", $cd_user->gettag(), $html);
            $html = str_replace("##nu_hours##", $nu_hours->gettag(), $html);            
            $html = str_replace("##gestore##", $this->gestore, $html);
            $html = str_replace("##ENDFORM##", $objform->endform(), $html);

        }
        return $html;        
    }

    public function getJobListJSON($id_cliente) : String {

        global $session,$conn;

        $data = date("Y-m-d");

        $sql = "select * from ".DB_PREFIX."ts_job where cd_cliente='{$id_cliente}' and fl_attivo=1 
        ".($this->USE_JOBS_DATES ? " and  dt_inizio<='$data' and dt_fine>='$data'" : "")."
        order by de_codice";
        $rs = $conn->query($sql) or trigger_error($conn->error." ".$sql);
        $arCommesse = [];
        while($riga = $rs->fetch_array()) {
            $dalal = "";
            $d0 = strtotime(date("Y-m-d"));

            if($riga['dt_inizio'] != '0000-00-00') {
                $d1 = strtotime($riga['dt_inizio']);
                $dalal.="dal ".Todmy($riga['dt_inizio']);
                if($d0 < $d1) {
                    $dalal .= " ({Not started})";
                }
            }
            if($riga['dt_fine'] != '0000-00-00') {
                $d2 = strtotime($riga['dt_fine']);
                if(!$dalal) { $dalal.="...";} else {$dalal .=" ";}
                $dalal.="al ".Todmy($riga['dt_fine']);
                if($d0 > $d2) {
                    $dalal .= " ({Closed})";
                }
            }

            if($dalal!="") { $dalal = " ($dalal)";}

            // $arCommesse[$riga['id_job']]= $riga['de_codice']." ".$riga['de_nomejob'];
            $arCommesse[]= ['key'=>$riga['id_job'], 'value' => $riga['de_codice']." ".$riga['de_nomejob'] ];
        }
        
        return json_encode($arCommesse);

    }

    public function getTodoListJSON($id_job) : String {
        global $session,$conn;
        $data = date("Y-m-d");
        $sql = "select id_todo, de_label from ".DB_PREFIX."ts_todos where cd_job='{$id_job}' order by de_label";
        $rs = $conn->query($sql) or trigger_error($conn->error." ".$sql);
        $arTodos = [];
        while($riga = $rs->fetch_array()) {
            $arTodos[]= ['key'=>$riga['id_todo'], 'value'=>$riga['de_label'] ];
        }
        return json_encode($arTodos);
    }


    /** 
     * generate unique bg color
     */
    private function bgColor($str, $div = 4) : String {
        $hash = crc32($str);
        $r = min(round(200 + ($hash >> 16 & 0xFF) / $div ), 255);
        $b = min(round(200 + ($hash >> 8 & 0xFF) / $div), 255);
        $g = min(round(200 + ($hash & 0xFF) / $div), 255);
        return sprintf('#%02x%02x%02x', $r, $g, $b);
    }

    /**
     * day name in the format dd/mm above the grid
     */
    private function dayname($data) : String {
        $gg = date("d",strtotime($data));
        $mm = date("m",strtotime($data));
        $giorno = date("w",strtotime($data));
        $w = $this->shortDays[$giorno];
        return $gg."/".$mm."<br/>".$w;
    }

    public function getComment($dati) : String {
        global $session;
        if ($session->get("TSPLANNING")) {
            $id_planning = (int)($dati['id'] ?? '');
            return execute_scalar("select de_comment from ".DB_PREFIX."ts_planning where id_planning='".$id_planning."'","");
        }
        return "";
    }

    public function setComment($dati) : void {
        global $session,$conn;
        if ($session->get("TSPLANNING")) {
             $comment = strip_tags($dati['comment'] ?? '');
             $id_planning = (int)($dati['id'] ?? '');
             $conn->query("UPDATE ".DB_PREFIX."ts_planning SET de_comment='".$comment."' where id_planning='".$id_planning."'");
        }
    }

    private function showPeopleHtml($currentDate) : String {
        global $session,$conn;

        // check if $currentDate is a monday
        if( date('N', strtotime($currentDate)) == 1) {
            $monday = $currentDate;
        } else {
            // given a currentDate if it isn't a monday calculate the previous monday
            $monday = date("Y-m-d", strtotime('last monday', strtotime($currentDate)));
        }
        $sunday = date("Y-m-d", strtotime("next sunday",strtotime($monday)));

        // show people with planning in the week
        $sql = "SELECT distinct ".DB_PREFIX."frw_utenti.*,cd_reparto FROM `".DB_PREFIX."ts_planning` P inner join ".DB_PREFIX."frw_utenti on P.cd_user=id 
            left outer join ".DB_PREFIX."frw_extrauserdata B on B.cd_user=id
            where P.dt_date >= '".$monday."' and P.dt_date<='".$sunday."'
            order by cognome,nome";
        // show all people ON from tecnici and collaboratori
        $sql = "SELECT distinct ".DB_PREFIX."frw_utenti.*,cd_reparto FROM ".DB_PREFIX."frw_utenti 
            left outer join ".DB_PREFIX."frw_extrauserdata B on B.cd_user=id
            where cd_reparto <> 2
            and fl_attivo=1
            order by cognome,nome";

        $rs = $conn->query($sql) or trigger_error($conn->error." ".$sql);
        $o = "";
        $daynames = false;
        while($row = $rs->fetch_array()) {
            
            $o.="<div class='people'>";

                // user details
                $o.="<div class='peopledetails' style='background-color:" . $this->bgColor($row['cd_reparto'],10) . ";'>";
                    $command = "showAllInOne( 0, {$row['id']}, '{$monday}', 0, 0 )";
                    $o.="<div class='userlabel' onclick=\"".$command."\"><a>" . $row['cognome'] . " " . $row['nome'] . "</a></div>";
                $o.="</div>";

            
                $sql = "SELECT distinct * FROM `".DB_PREFIX."ts_todos` inner join ".DB_PREFIX."ts_job on cd_job=id_job 
                    INNER JOIN `".DB_PREFIX."ts_clienti` on cd_cliente=id_cliente
                order by de_codice";
                $rs2 = $conn->query($sql) or trigger_error($conn->error." ".$sql);
                

                // $i = week day number
                for($i = 0; $i < 7; $i++) { 
                    for ($k = 1; $k <= $this->SLOTS; $k++) {
                        $countTasks[$i][$k] = 0;
                    }
                }

                $o.="<div class='todo-container'>";
                $done = 0;
                while($row2 = $rs2->fetch_array()) {
                    
                    $q = execute_scalar("SELECT count(1) FROM `".DB_PREFIX."ts_planning` WHERE cd_user='{$row['id']}' and dt_date>='$monday' and dt_date<='" . date("Y-m-d", strtotime("next sunday",strtotime($monday))) . "' and cd_todo = '" . $row2['id_todo'] . "'",0);
                    
                    if($q > 0) {
                        
                        $o.="<div class='todo'>";
                        
                        $sunday = date("Y-m-d", strtotime("next sunday",strtotime($monday)));
                        
                        $sql3 = "select distinct ".DB_PREFIX."ts_todos.* from ".DB_PREFIX."ts_todos INNER JOIN ".DB_PREFIX."ts_planning ON cd_todo=id_todo where id_todo='{$row2['id_todo']}' and dt_date>='$monday' and dt_date<='".$sunday."' and cd_user='{$row['id']}'";
                        $rs3 = $conn->query($sql3) or trigger_error($conn->error." ".$sql3);
                        
                        $o.="<div class='days-container'>";
                        while($row3 = $rs3->fetch_array()) {
                            $o.="<div class='user-container'>";
                            for($i=0;$i<7;$i++) {
                                $data = date("Y-m-d", strtotime('+'.$i.' day', strtotime($monday)));
                                $sql4 = "select * from ".DB_PREFIX."ts_planning  where cd_todo='{$row2['id_todo']}' and cd_user='{$row['id']}' and dt_date='$data'";
                                $rs4 = $conn->query($sql4) or trigger_error($conn->error." ".$sql4);
                                $o.="<div class='day-user day-is-{$i}' data-date='$data'>";
                                $command = "showAllInOne( 0, {$row['id']}, '{$data}', 0, 0 )";
                                if(!$daynames) $o.="<span class='day' onclick=\"".$command."\">" . $this->dayname($data) . "</span>";
                                if($rs4->num_rows < $this->SLOTS ) {
                                    $command = "showAllInOne( 0, {$row['id']}, '{$data}', 0, 0)";
                                    $q2 = execute_scalar($sqlD = "SELECT sum(nu_hours) FROM `".DB_PREFIX."ts_planning` WHERE cd_user='{$row['id']}' and dt_date='$data'",0);

                                    // ------------------------------------------------------
                                    // @todo parametrization missing
                                    // this part is hardcoded for 2 slots of 4 hours
                                    // and should be extended to handle $this->SLOTS and $this->HOURS_PER_DAY parameters
                                    if($q2 == 0 && $countTasks[$i][1] == 0) {
                                        $o.="<div class='nome count1' onclick=\"".$command."\"></div><div class='nome count2 q2-{$q2}' onclick=\"".$command."\"></div>";
                                        $countTasks[$i][1] = 1;
                                        $countTasks[$i][2] = 1;
                                    }
                                    if($q2 == 4 && $countTasks[$i][2] == 0) {
                                        $o.="<div class='nome count2 count2 q2-{$q2}' onclick=\"".$command."\"></div>";
                                        $countTasks[$i][2] = 1;
                                    }
                                    // ------------------------------------------------------
                                }
                                while($row4 = $rs4->fetch_array()) {
                                    // user detail
                                    // ------------------------------------------------------
                                    // @todo parametrization missing
                                    if( $countTasks[$i][1] == 0 ) {
                                        $count = 1;
                                        $countTasks[$i][1] = 1;
                                    } else {
                                        $count = 2;
                                        $countTasks[$i][2] = 1;
                                    }
                                    // ------------------------------------------------------
                                    $command = "showAllInOne( {$row2['id_todo']}, {$row['id']}, '{$data}', {$row4['id_planning']}, {$row4['nu_hours']} )";
                                    $o.="<div class='nome count{$count}' data-hours='" . $row4['nu_hours'] . "' style='background-color:" . $this->bgColor($row2['id_todo']) . ";'  onclick=\"".$command."\">".$row2["de_codice"]." ".$row2["de_nomecliente"]."<br>".$row2["de_nomejob"]."</div><span id='comment_{$row4['id_planning']}' class='icon-comment".($row4['de_comment']==""?"-empty":"")." comment'></span>";
                                }
                                $o.="</div>";        
                            }    
                            $o.="</div>";
                            $daynames = true;
                        }
                        $o.="</div>";
                        
                        $done = 1;
                        
                        $o.="</div>";
                    }
                }
                
                if($done==0) {
                    // handle empty line
                    // never happens because in query select people only 
                    // with records in planning for the current week
                    $o.="<div class='todo'>";
                    $o.="<div class='days-container'>";
                    $o.="<div class='user-container'>";
                    for($i=0;$i<7;$i++) {
                        $data = date("Y-m-d", strtotime('+'.$i.' day', strtotime($monday)));
                        $o.="<div class='day-user day-is-{$i}' data-date='$data'>";
                        if(!$daynames) $o.="<span class='day'>" . $this->dayname($data) . "</span>";
                        $command = "showAllInOne( 0, {$row['id']}, '{$data}', 0, 0)";
                        $o.="<div class='nome' onclick=\"".$command."\"></div></div>";
                    }
                    $o.="</div>";
                    $o.="</div>";
                    $o.="</div>";
                    $daynames = true;
                }
                
                $o.="</div>";


            $o.="</div>";


        }

        return $o;
    }

    /**
     * extract the html for the planning scheme
     * just one task for job for week:
     * ALTER TABLE `ts_todos` ADD `dt_week_or_ever` DATE NULL DEFAULT NULL COMMENT 'NULL = forever, date = just that week' AFTER `fl_status`;
     */
    private function showTodosHtml($currentDate) : String {
        global $session,$conn;

        // check if $currentDate is a monday
        if( date('N', strtotime($currentDate)) == 1) {
            $monday = $currentDate;
        } else {
            // given a currentDate if it isn't a monday calculate the previous monday
            $monday = date("Y-m-d", strtotime('last monday', strtotime($currentDate)));

        }
        $sunday = date("Y-m-d", strtotime("next sunday",strtotime($monday)));

        // $sql = "SELECT distinct ".DB_PREFIX."ts_job.* FROM `".DB_PREFIX."ts_todos` inner join ".DB_PREFIX."ts_job on cd_job=id_job
        //     WHERE (dt_week_or_ever IS NULL or dt_week_or_ever = '$monday')
        //     order by de_codice";
        $sql = "SELECT distinct ".DB_PREFIX."ts_job.*,de_nomecliente FROM `".DB_PREFIX."ts_todos` inner join ".DB_PREFIX."ts_job on cd_job=id_job
            inner join ".DB_PREFIX."ts_clienti on cd_cliente=id_cliente
            WHERE ( (dt_week_or_ever IS NULL or dt_week_or_ever = '$monday') or (
                (SELECT count(1) FROM ".DB_PREFIX."ts_planning P WHERE P.cd_todo=id_todo and P.dt_date >= '$monday' and P.dt_date <= '$sunday') > 0
            ) )
            order by de_codice";

        $rs = $conn->query($sql) or trigger_error($conn->error." ".$sql);
        $o = "";
        $daynames = false;
        while($row = $rs->fetch_array()) {
            
            $o.="<div class='job'>";

                // job details
                $command = "showTodoPopUp(0, ".$row['id_job'].", '".$monday."')";
                $o.="<div class='jobdetails' onclick=\"".$command."\">";
                    // $o.="<div class='code'>" . $row['de_codice']." <span data-rel=\"" . $row['de_nomejob']."\" class=\"icon-help-circled\"></span></div>";
                    $o.="<div class='code'><a>" . $row['de_codice']."<br>". $row['de_nomecliente'] ."</a></div>";
                    $o.="<div class='name'>" . $row['de_nomejob']."</div>";
                $o.="</div>";
            
                $o.="<div class='todo-container'>";
                    $sql2 = "SELECT * FROM `".DB_PREFIX."ts_todos` where cd_job = '".$row['id_job']."' 
                        and ( (dt_week_or_ever IS NULL or dt_week_or_ever = '$monday') or
                        ( (SELECT count(1) FROM ".DB_PREFIX."ts_planning P WHERE P.cd_todo=id_todo and P.dt_date >= '$monday' and P.dt_date <= '$sunday') > 0 ) )
                        order by id_todo";
                    $rs2 = $conn->query($sql2) or trigger_error($conn->error." ".$sql2);
                    
                   while($row2 = $rs2->fetch_array()) {

                        $o.="<div class='todo'>";

                            // todo details
                            $o.="<div class='tododetails' style='background-color:" . $this->bgColor($row2['id_todo']) . ";'>";
                                $o.="<div class='todolabel'>";
                                if(!$daynames) {
                                    $command = "showTodoPopUp(0,0 , '".$monday."')";
                                    $o.="<span class='day' onclick=\"".$command."\">{Task}</span>";
                                 }
                                 $o.="<a onclick=\"showTodoPopUp(".$row2['id_todo'].",0 , '".$monday."')\">" . nl2br($row2['de_label'])."</a></div>";
                            $o.="</div>";

                            $sunday = date("Y-m-d", strtotime("next sunday",strtotime($monday)));

                            $sql3 = "select distinct P.cd_user,nome,cognome,cd_reparto from ".DB_PREFIX."ts_planning P inner join ".DB_PREFIX."frw_utenti on cd_user=id 
                                left outer join ".DB_PREFIX."frw_extrauserdata B on B.cd_user=id 
                                where cd_todo='{$row2['id_todo']}' and dt_date>='$monday' and dt_date<='".$sunday."' order by cd_user";
                            $rs3 = $conn->query($sql3) or trigger_error($conn->error." ".$sql3);
                    
                            $o.="<div class='days-container'>";
                            $i = 0;
                            while($row3 = $rs3->fetch_array()) {
                                $o.="<div class='user-container'>";
                                for($i=0;$i<7;$i++) {
                                    $data = date("Y-m-d", strtotime('+'.$i.' day', strtotime($monday)));
                                    $sql4 = "select * from ".DB_PREFIX."ts_planning  where cd_todo='{$row2['id_todo']}' and cd_user='{$row3['cd_user']}' and dt_date='$data'";
                                    $rs4 = $conn->query($sql4) or trigger_error($conn->error." ".$sql4);
                                    $o.="<div class='day-user day-is-{$i}' data-date='$data'>";
                                    if(!$daynames) {
                                        $command = "showAllInOne( 0, 0, '{$data}', 0, 0 )";
                                        $o.="<span class='day' onclick=\"".$command."\">" . $this->dayname($data) . "</span>";
                                    }
                                    if($rs4->num_rows == 0) {
                                        $command = "showAllInOne( {$row2['id_todo']}, {$row3['cd_user']}, '{$data}', 0, 0 )";
                                        $o.="<div class='nome' onclick=\"".$command."\"></div>";
                                    }
                                    while($row4 = $rs4->fetch_array()) {
                                        // user detail
                                        $command = "showAllInOne( {$row2['id_todo']}, {$row3['cd_user']}, '{$data}', {$row4['id_planning']}, {$row4['nu_hours']} )";
                                        $o.="<div class='nome' data-hours='" . $row4['nu_hours'] . "' style='background-color:" . $this->bgColor($row3['cd_reparto'],10) . ";' onclick=\"".$command."\">" . $row3['cognome'] . " " . $row3['nome'] . " " . $row4['nu_hours'] . "h </div><span id='comment_{$row4['id_planning']}' class='icon-comment".($row4['de_comment']==""?"-empty":"")." comment'></span>";
                                    }
                                    $o.="</div>";
                                    
                                }   
                                $daynames = true; 
                                $o.="</div>";
                            }
                            if($i==0) {
                                // handle empty line
                                $o.="<div class='user-container'>";
                                for($i=0;$i<7;$i++) {
                                    $data = date("Y-m-d", strtotime('+'.$i.' day', strtotime($monday)));
                                    $o.="<div class='day-user day-is-{$i}' data-date='$data'>";
                                    if(!$daynames) {
                                        $command = "showAllInOne( 0, 0, '{$data}', 0, 0 )";
                                        $o.="<span class='day' onclick=\"".$command."\">" . $this->dayname($data) . "</span>";
                                    }
                                    $command = "showAllInOne( {$row2['id_todo']}, 0, '{$data}', 0, 0)";
                                    $o.="<div class='nome' onclick=\"".$command."\"></div></div>";
                                }
                                $daynames = true;
                                $o.="</div>";
                            }
                            $o.="</div>";

                        $o.="</div>";
                    }
                $o.="</div>";
            $o.="</div>";
        }
        
        return $o;        

    }

	function updateAndInsertTodo($arDati) {
		// in:
		// arDati--> array POST del form
		// risultato:
		//	"" --> ok
		//	"1" --> nome gia' utilizzato da un altro componente
		//  "0" --> il tuo profilo non ti consente l'inserimento/modifica
		global $session, $conn;
		if ($session->get("TSPLANNING")) {
            
            $arDati["fl_status"] = $arDati["fl_status"] ?? 1;   // forzo a 1 se non specificato (per ora non lo è)
			if ($arDati["id"]!="") {
				/*
					Modifica
				*/
				$sql="UPDATE ".DB_PREFIX."ts_todos SET de_label='##de_label##',cd_job='##cd_job##',fl_status='##fl_status##'
                    ". ($this->ALLOW_JOBS_FOR_EVER ? "" : ",dt_week_or_ever='##currentDate##' ") . " WHERE id_todo='##id_todo##'";
                $sql= str_replace("##de_label##",$arDati["de_label"],$sql);
                $sql= str_replace("##cd_job##",$arDati["cd_job"],$sql);
                $sql= str_replace("##fl_status##",$arDati["fl_status"],$sql);
                $sql= str_replace("##id_todo##",$arDati["id"],$sql);
                if(!$this->ALLOW_JOBS_FOR_EVER) $sql= str_replace("##currentDate##",$arDati["currentDate"],$sql);
                
				$conn->query($sql) or (trigger_error($conn->error."<br>sql='{$sql}'"));
				$numero = $arDati["id"];
				$html= "ok";
			} else {
				/*
					Inserimento
				*/

                // limit to 1 task per job per week
                // 
                if( date('N', strtotime($arDati["currentDate"])) == 1) {
                    $monday = $arDati["currentDate"];
                } else {
                    // given a currentDate if it isn't a monday calculate the previous monday
                    $monday = date("Y-m-d", strtotime('last monday', strtotime($arDati["currentDate"])));
                }
                $q = execute_scalar("SELECT count(1) FROM
                    " . DB_PREFIX . "ts_todos 
                    WHERE cd_job = '{$arDati["cd_job"]}' and dt_week_or_ever = '{$monday}'", 0);
                if($q == 0) {
                    $sql="INSERT into ".DB_PREFIX."ts_todos (cd_job,cd_creator,de_label,dt_saved,fl_status,dt_week_or_ever) values('##cd_job##','##cd_creator##','##de_label##','##dt_saved##','##fl_status##','##dt_week_or_ever##')";
                    $sql= str_replace("##cd_job##",$arDati["cd_job"],$sql);
                    $sql= str_replace("##cd_creator##",$session->get("idutente"),$sql);
                    $sql= str_replace("##de_label##",$arDati["de_label"],$sql);
                    $sql= str_replace("##dt_saved##",date("Y-m-d H:i:s"),$sql);
                    $sql= str_replace("##fl_status##","1",$sql); // meaning?
                    $sql= str_replace("##dt_week_or_ever##",$monday,$sql);
                    $conn->query($sql) or (trigger_error($conn->error."<br>sql='{$sql}'"));
                    $numero = $conn->insert_id;
                    $html= "ok";
                } else {
                    $html= "{You already have a task this week for this job}";
                }
			}
		} else {
			$html="0";		//il tuo profilo non ti consente l'inserimento
		}
		return $html;
	}


    /**
     * insert a planning of a person in a day
     */
    function updateAndInsertPeople($arDati, $flagRecursive = false) {
		// in:
		// arDati--> array POST del form
		// risultato:
		//	"" --> ok
		//	"1" --> nome gia' utilizzato da un altro componente
		//  "0" --> il tuo profilo non ti consente l'inserimento/modifica
		global $session, $conn;
		if ($session->get("TSPLANNING")) {
            
            $arDati["nu_hours"] = intval($arDati["nu_hours"] ?? 0);
            if ($arDati["nu_hours"] <= 0) {
                return "ko|{invalid hours}";
            }
            $arDati["cd_user"] = intval($arDati["cd_user"] ?? 0);
            if ($arDati["cd_user"] <= 0) {
                return "ko|{invalid user}";
            }
            $arDati["cd_todo"] = intval($arDati["cd_todo"] ?? 0);
            if ($arDati["cd_todo"] <= 0) {

                // create new todo if label inserted
                if ($arDati["de_label"] != "") {
                    // $monday = date("Y-m-d", strtotime('last monday', strtotime($arDati["currentDate"])));
                    if( date('N', strtotime($arDati["currentDate"])) == 1) {
                        $monday = $arDati["currentDate"];
                    } else {
                        // given a currentDate if it isn't a monday calculate the previous monday
                        $monday = date("Y-m-d", strtotime('last monday', strtotime($arDati["currentDate"])));
                    }

                    // limit to 1 task per job per week
                    $q = execute_scalar("SELECT count(1) FROM
                        " . DB_PREFIX . "ts_todos 
                        WHERE cd_job = '{$arDati["cd_job"]}' and dt_week_or_ever = '{$monday}'", 0);
                    if($q == 0) {

                        $arDati["cd_job"] = intval($arDati["cd_job"] ?? 0);
                        $sql = "insert into ".DB_PREFIX."ts_todos (cd_job,cd_creator,de_label,dt_saved,fl_status,dt_week_or_ever) values('##cd_job##','##cd_creator##','##de_label##','##dt_saved##','##fl_status##','##dt_week_or_ever##')";
                        $sql = str_replace("##cd_job##", $arDati["cd_job"], $sql);
                        $sql = str_replace("##cd_creator##", $session->get("idutente"), $sql);
                        $sql = str_replace("##de_label##", $arDati["de_label"], $sql);
                        $sql = str_replace("##dt_saved##", date("Y-m-d H:i:s"), $sql);
                        $sql = str_replace("##dt_week_or_ever##", $monday, $sql);
                        $sql = str_replace("##fl_status##", 1, $sql);
                        $conn->query($sql) or (trigger_error($conn->error . "<br>sql='{$sql}'"));
                        $arDati["cd_todo"] = $conn->insert_id;
                    } else {
                        return "ko|{You already have a task this week for this job}";
                    }
                }
                if ($arDati["cd_todo"] <= 0) {
                    return "ko|{invalid todo}";        
                }
            }
            $arDati["id"] = intval($arDati["id"] ?? 0);
            if ($arDati["id"] < 0) {
                return "ko|{invalid planning}";
            }
            $arDati["currentDate"] = $arDati["currentDate"] ?? '';
            if (!checkdate(substr($arDati["currentDate"],5,2),substr($arDati["currentDate"],8,2),substr($arDati["currentDate"],0,4))) {
                return "ko|{invalid date}";
            }
            if ( $flagRecursive && ( date("w", strtotime($arDati["currentDate"])) == 6 || date("w", strtotime($arDati["currentDate"])) == 7 )) {
                return "ko|{invalid date}"; // saturday and sunday are not allowed
            }

            $q = $arDati["nu_hours"] + execute_scalar("select sum(nu_hours) from ".DB_PREFIX."ts_planning where dt_date='".$arDati["currentDate"]."' and cd_user='".$arDati["cd_user"]."' and id_planning!='".$arDati["id"]."'",0);
            if ($q > $this->HOURS_PER_DAY) {
                return "ko|" . sprintf(translateHtml("{You can't insert more hours than %s hours in a day}"),$this->HOURS_PER_DAY);
            }

            
            if ((int)$arDati["id"] > 0) {
                /*
                Modifica
				*/
				$sql="UPDATE ".DB_PREFIX."ts_planning set nu_hours='##nu_hours##',cd_user='##cd_user##',cd_todo='##cd_todo##', dt_date='".$arDati["currentDate"]."'  where id_planning='##id_planning##'";
				$sql= str_replace("##cd_user##",$arDati["cd_user"],$sql);
				$sql= str_replace("##cd_todo##",$arDati["cd_todo"],$sql);
                $sql= str_replace("##nu_hours##",$arDati["nu_hours"],$sql);
                $sql= str_replace("##id_planning##",$arDati["id"],$sql);
				$conn->query($sql);
                if( $conn->errno >0) {
                    if($conn->errno == 1062) {
                        return "ko|{Task already present, edit it instead}";
                    } else {
                        trigger_error($conn->errno."<br>sql='{$sql}'");
                    }
                } 
				$numero = $arDati["id"];
				$html= "ok";
			} else {
				/*
					Inserimento
				*/
                $sql="INSERT into ".DB_PREFIX."ts_planning (dt_date,nu_hours,cd_user,cd_todo,cd_creator) values('##dt_date##','##nu_hours##','##cd_user##','##cd_todo##','##cd_creator##')";
                $sql= str_replace("##cd_todo##",$arDati["cd_todo"],$sql);
                $sql= str_replace("##cd_creator##",$session->get("idutente"),$sql);
                $sql= str_replace("##dt_date##",$arDati["currentDate"],$sql);
				$sql= str_replace("##cd_user##",$arDati["cd_user"],$sql);
                $sql= str_replace("##nu_hours##",$arDati["nu_hours"],$sql);
                $conn->query($sql);
                if( $conn->errno >0) {
                    if($conn->errno == 1062) {
                        return "ko|{Task already present, edit it instead}";
                    } else {
                        trigger_error($conn->errno."<br>sql='{$sql}'");
                    }
                } 
				$numero = $conn->insert_id;
				$html= "ok";
			}

            if( $arDati["cd_todo"] > 0 && $arDati["de_label"] !="") {
                // rename task

                $sql="UPDATE ".DB_PREFIX."ts_todos set de_label='##de_label##' where id_todo='##cd_todo##'";
                $sql= str_replace("##de_label##",$arDati["de_label"],$sql);
                $sql= str_replace("##cd_todo##",$arDati["cd_todo"],$sql);
                $conn->query($sql) or (trigger_error($conn->errno."<br>sql='{$sql}'"));
                
            }

            if ($numero > 0 && $arDati["toDate"]>$arDati["currentDate"]) {
                // copia la task fino alla data selezionata

                // limit number of days to insert to 30 to prevent long execution
                $maxDays = 30;
                if ( date_diff2($arDati["currentDate"], $arDati["toDate"]) > $maxDays ) {
                    $arDati["toDate"] = date("Y-m-d", strtotime("+".$maxDays." day", strtotime($arDati["currentDate"])));
                }

                $newDati = $arDati;
                $newDati["currentDate"] = date("Y-m-d", strtotime("+1 day", strtotime($arDati["currentDate"]))); 
                
                if($newDati["currentDate"]<=$arDati["toDate"]) {
                    $newDati["de_label"] = "";
                    $newDati["id"] = 0;
                    $html = $this->updateAndInsertPeople($newDati, true);
                    if (!$flagRecursive) {
                        //se ci sono stati degli errori nella chiamata ricorsiva, fa niente, va alla prossima data
                        if (explode("|", $html)[0] == "ko") {
                            $html = "ok";
                        }
                    }
                }
            }

		} else {
			$html="0";		//il tuo profilo non ti consente l'inserimento
		}
		return $html;
	}


    public function deletePeople( $requestData ) : String {
        global $session,$conn;
        $html = "0";
        if ($session->get("TSPLANNING") && $session->get("idprofilo") >= $this->editing_level) {
            $requestData["id"] = intval($requestData["id"] ?? 0);
            if ($requestData["id"] > 0) {
                $sql="DELETE from ".DB_PREFIX."ts_planning where id_planning='##id_planning##'";
                $sql= str_replace("##id_planning##",$requestData["id"],$sql);
                $conn->query($sql) or (trigger_error($conn->error."<br>sql='{$sql}'"));
                $html= "ok";
            }
        }
        return $html;
    }

    public function deleteTodo( $requestData ) : String {
        global $session,$conn;
        $html = "0";
        if ($session->get("TSPLANNING") && $session->get("idprofilo") >= $this->editing_level) {
            $requestData["id"] = intval($requestData["id"] ?? 0);
            if ($requestData["id"] > 0) {
                $sql="DELETE from ".DB_PREFIX."ts_todos where id_todo='##id_todo##'";
                $sql= str_replace("##id_todo##",$requestData["id"],$sql);
                $conn->query($sql) or (trigger_error($conn->error."<br>sql='{$sql}'"));

                $sql="DELETE from ".DB_PREFIX."ts_planning where cd_todo='##id_todo##'";
                $sql= str_replace("##id_todo##",$requestData["id"],$sql);
                $conn->query($sql) or (trigger_error($conn->error."<br>sql='{$sql}'"));

                $html= "ok";
            }
        }
        return $html;
    }


}
