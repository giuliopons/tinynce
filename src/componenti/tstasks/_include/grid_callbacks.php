<?php
/*
    function for grid list
*/

function togglePriority($p,$id) {
	global $obj;

	$p2 = explode("|",$p);
	
	if(($p2[0]) == "") { $p2[0] = 0; }
		else { $p2[0] = 1; }
	$ar = $obj->getTogglePriorityOptions($id);
	return isset($ar[$p2[0]]) ? $ar[$p2[0]] : "";
}

function toggleStato($p) {
	global $obj;
	$p2 = explode("|",$p);
	$ar = $obj->getToggleOptions($p2[0]);
	return isset($ar[$p2[1]]) ? $ar[$p2[1]] : "";
}
function colortask($p,$id,$params) {
    global $obj;
	// TO DO
	// third parameter not so nice
	// $params = json_decode(str_replace("&quot;","\"",$json_params));
	$p2 = explode("|^",$p);
    $link = str_replace("##id_task##",$id,$obj->linkmodifica);
	// $linkExt = $p2[2]!='' ? '<a href="'.$p2[2].'" style="float:right" target="_blank" title="{Open}" class="icon-link"></a>' : '';

	$colore = $p2[1];
	if ($colore=="") { $bg = "inherit"; $fg = "inherit";}
	if ($colore!="") { $bg = $colore; $fg = "#fff";}
	if (in_array($colore, array("yellow","orange","khaki","greenyellow","tomato","orangered","violet","pink","turquoise"))) { $bg = $colore; $fg = "#111";}
	if ($colore=="orange") { $bg = $colore; $fg = "#111";}

	$list_link="";
	if($params["params"]["showall"] == 1) {
		$list_name = execute_scalar("select de_namelist from ".DB_PREFIX."ts_lists WHERE id_list=".$p2[2]);
		$list_link = "<a href=\"?combotipo=".$p2[2]."&combotiporeset=reset&keyword=\" class='tagname'><span>".$list_name."</span></a>";
	
	}
	

    return '<a href="'. $link.'" title="{Edit}">'.
		"<span style='padding:0 .2rem;background-color:".$bg.";color:".$fg.";'>".
		$p2[0]."</span></a>" . $list_link;
		// .$linkExt;
}
function linktask($linkfield,$id) {
    $linkExt = $linkfield!='' ? '<a href="'.$linkfield.'" target="_blank" title="{Open}" class="icon-link"></a>' : '';
    return $linkExt;
}

function quantoManca($dataScadenza) {
    $scadenza = new DateTime($dataScadenza);
    $now = new DateTime();
    $interval = $now->diff($scadenza);

    $result = '';

    if ($interval->y > 0) {
        $result = $interval->format('%y anni, %m mesi e %d giorni');
    } elseif ($interval->m > 0) {
        $result = $interval->format('%m mesi, %d giorni, %h ore e %i minuti');
    } elseif ($interval->d > 0) {
        $result = $interval->format('%d giorni, %h ore e %i minuti');
    } elseif ($interval->h > 0) {
        $result = $interval->format('%h ore e %i minuti');
    } elseif ($interval->i > 0) {
        $result = $interval->format('%i minuti');
    } else {
        $result = "meno di un minuto";
    }

    return $result;
}

function show_scadenza($p,$id) {
	if($p) {
		$log = datef($p, true);
		if($p>date("Y-m-d H:i:s")) $class ="green";
			else $class="red";

			$stato = execute_scalar("select en_status from ".DB_PREFIX."ts_tasks where id_task=".$id);
			if ($stato != "done") {
				$log .= "<span class='piccolo ".$class."'>".($class=='red'?"-":""). quantoManca($p)."</span>";
			}
		
		
		return $log;
	} else {
		return "";
	}

}



?>