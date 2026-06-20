<?php


 

 function showMoney($p) {
	$p2 = explode("|^",$p);
	if(count($p2) == 1) return $p;
	$out= numberf((float)$p2[0],0).MONEY;
	$t = $out2 = "";
	if($p2[1]> 0 && (float)$p2[0]>$p2[1]) {
		$t="color:red";
	}
	if($p2[1]> 0) {
		$out2 = "<div class='small' style='".$t."'>{Max} ".numberf((float)$p2[1],0).MONEY."</div>";
	}
	return "<div style='".$t."'>".$out.$out2."</div>";
}


function showNumberOre($p) {
	$p2 = explode("|^",$p);
	if(count($p2) == 1) return $p;
	$out= numberf((float)$p2[0] / 8,1);
	$t = $out2 = "";
	if($p2[1]> 0 && (float)$p2[0]>$p2[1]) {
		$t="color:red";
	}
	if($p2[1]> 0) {
		$out2 .= "<div class='small' style='".$t."'>{Max} ".numberf((float)$p2[1]/8,1)."</div>";
	}
	return "<div style='".$t."'>".$out.$out2."</div>";
}


function showCode($s) {
	return "<div style='white-space:nowrap'>".$s."</div>";
}


function toggleStato($p) {
	$p2 = explode("|",$p);
	$ar = array(
		"0"=>"<a class='label labelred' href=\"javascript:;\" onclick=\"setStato(this,'1',".$p2[0].")\">{OFF}</a></span>",
		"1"=>"<a class='label labelgreen' href=\"javascript:;\" onclick=\"setStato(this,'0',".$p2[0].")\">{ON}</a></span>"
	);
	return $ar[$p2[1]];
}

function show_job_name($p,$id) {
    global $obj;
    $link = str_replace("##id_job##",$id,$obj->linkmodifica);
	$p2 = explode("|^",$p);

	$colore = $p2[1];
	if ($colore=="") { $bg = "#eee"; $fg = "#111";}
	if ($colore!="") { $bg = $colore; $fg = "#fff";}
	if ($colore=="yellow") { $bg = $colore; $fg = "#111";}
	if ($colore=="white") { $bg = $colore; $fg = "#111";}
	if ($colore=="orange") { $bg = $colore; $fg = "#111";}
       return "<a href=\"".$link."\"><span style='text-decoration:underline;padding:0 .2rem;background-color:".$bg.";color:".$fg.";'</span>".$p2[0]."</span></a>";

}



 ?>