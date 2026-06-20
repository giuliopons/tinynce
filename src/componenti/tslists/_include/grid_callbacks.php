<?php

function show_visibility_groups($p,$id,$params) {
    global $session;

    $ar = [
        "1"=>"<span class='labelred'>{Private}</span>",
        "0"=>"<span class='labelgreen'>{Public}</span>",
    ];

    if($p=="0") {
        return "<div class='visibility'>".$ar[$p]."</div>";
    }

    global $conn;
    $sql = "select id_reparto,de_nomereparto from ".DB_PREFIX."ts_tbc_lists_reparti INNER JOIN ".DB_PREFIX."ts_reparti 
        ON ".DB_PREFIX."ts_tbc_lists_reparti.cd_reparto=".DB_PREFIX."ts_reparti.id_reparto 
        where cd_list=".$id."    
        order by de_nomereparto";
    $rs = $conn->query($sql) or trigger_error($conn->error." ".$sql);
    $out = "";
    while($riga = $rs->fetch_array()) $out.= "<span class='dep'>".$riga["de_nomereparto"]."</span>";

    
    $sql = "select nome,cognome from ".DB_PREFIX."ts_tbc_lists_users INNER JOIN ".DB_PREFIX."frw_utenti 
        ON ".DB_PREFIX."ts_tbc_lists_users.cd_user=".DB_PREFIX."frw_utenti.id
        where cd_list=".$id."    
        order by cognome,nome";
    $rs = $conn->query($sql) or trigger_error($conn->error." ".$sql);
    while($riga = $rs->fetch_array()) $out.= "<span class='user'>".$riga["cognome"]." ".$riga["nome"]."</span>";

    $sql = "select nome,cognome from ".DB_PREFIX."ts_lists INNER JOIN ".DB_PREFIX."frw_utenti 
        ON ".DB_PREFIX."ts_lists.cd_owner=".DB_PREFIX."frw_utenti.id
        where id_list=".$id."";
    $rs = $conn->query($sql) or trigger_error($conn->error." ".$sql);
    while($riga = $rs->fetch_array()) $out.= "<span class='user'>".$riga["cognome"]." ".$riga["nome"]."</span>";


    return "<div class='visibility'>".$ar[$p].$out."</div>";
}