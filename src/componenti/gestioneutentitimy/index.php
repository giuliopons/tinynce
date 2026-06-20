<?php
/*

	controller for the users manager

*/


$root="../../../";
include($root."src/_include/config.php");
include($root."src/_include/grid.class.php");
include($root."src/_include/formcampi.class.php");
include("../gestioneutenti/_include/user.class.php");
include("../gestioneutenti/_include/gestioneutenti.class.php");
include("../gestioneutenti/_include/grid_callbacks.php");
include("_include/TIMY.gestioneutenti.class.php");

print $ambiente->setPosizione( "{Users}" );

$gu = new Timy_gestioneutenti("frw_utenti",40,"name","asc",0);

if (isset($ARRAY_EXTRA_USER_LABELS)) $gu->scegliDaInsiemeLabelProfili=$ARRAY_EXTRA_USER_LABELS;

$html="";

$command = postget("op","");
$parameter = (int)postget("id","");
$keyword = postget("keyword",$session->get($gu->seskey."keyword"));
$combotipo = postget("combotipo",$session->get($gu->seskey."combotipo") ?: "-999");
$combocompany = postget("combocompany",$session->get($gu->seskey."combocompany") ?: "-999");



if (isset($command)) {

	switch ($command) {

	case "modificaAnno":
		$risultato = $gu->getDettaglioAnno( $_GET['cd_user'],$_GET['nu_anno'] );
		if ($risultato=="0") {
			$html = returnmsg("{You're not authorized.}","jsback");
		} else $html = $risultato;
		break;
	case "modificaAnnoStep2" :
	case "aggiungiAnnoStep2" :
		$risultato = $gu->updateAndInsertAnno($_POST);
		if ($risultato=="0") {
			$html = returnmsg("{You're not authorized.}","jsback");
		} elseif(str_replace(strstr($risultato,"|"),"",$risultato)=="-1") {
			$html = returnmsg(str_replace("|","",strstr($risultato,"|")),"jsback");
		} else {
			$id_user = str_replace( "|","",stristr( $risultato, "|")) ;
			$html = returnmsgok("{Done.}","load ".$_SERVER['SCRIPT_NAME']."?op=modifica&id={$id_user}");
		}
		break;
	case "aggiungiAnno":
		$risultato = $gu->getDettaglioAnno( $_GET['cd_user'], '');
		if ($risultato=="0") {
			$html = returnmsg("{You're not authorized.}","jsback");
		} else $html = $risultato;
		break;
	case "modifica":
		$risultato = $gu->getDettaglioNew( $parameter );
		if ($risultato=="0") {
			$html = returnmsg("{You're not authorized.}","jsback");
		} else $html = $risultato;
		break;
	case "modificaStep2" :
		$risultato = $gu->updateAndInsert($_POST);
		if ($risultato=="0") {
			$html = returnmsg("{You're not authorized.}","jsback");
		} elseif(str_replace(strstr($risultato,"|"),"",$risultato)=="-1") {
			$html = returnmsg(str_replace("|","",strstr($risultato,"|")),"jsback");
		} else {
			if ($command != "modificaStep2reload") $html = returnmsgok("{Done.}","reload");
				else $html = returnmsgok("{Done.}","load ".$_SERVER['SCRIPT_NAME']."?op=modifica&id={$parameter}");
		}
		break;
	case "eliminaSelezionati":
		$risultato = $gu->eliminaSelezionati($_POST);
		if ($risultato=="0") {
			$html = returnmsg("{You're not authorized.}","jsback");
		} else $html = returnmsgok("{Deleted.}","load ".$_SERVER['SCRIPT_NAME']."");
		break;

	case "aggiungi":
		$risultato = $gu->getDettaglioNew();
		if ($risultato=="0") {
			$html = returnmsg("{You're not authorized.}","jsback");
		} else $html = $risultato;
		break;
	case "aggiungiStep2":
		$risultato = $gu->updateAndInsert($_POST);
		if ($risultato=="0") {
			$html = returnmsg("{You're not authorized.}","jsback");
		} elseif(str_replace(strstr($risultato,"|"),"",$risultato)=="-1") {
			$html = returnmsg(str_replace("|","",strstr($risultato,"|")),"jsback");
		} else {
			$id = str_replace( "|","",stristr( $risultato, "|")) ; 
			if ($command != "aggiungiStep2reload") $html = returnmsgok("{Done.}","reload");
				else $html = returnmsgok("{Done.}","load ".$_SERVER['SCRIPT_NAME']."?op=modifica&id=".$id."");
		}
		break;
	case "personifica":
		$user = execute_row("SELECT username, password FROM frw_utenti where id='$parameter'");
		if($user && in_array( $session->get("idprofilo"), array(20,999999) )) {
			$cr = new cryptor();
			$login->actionurl = $root."src/login.php";
			$out = $login->getLoginForm("Autologin");
			$out = str_replace('<input name="password" type="password"','<input name="password" type="password" value="'.$cr->decrypta($user['password']).'"',$out);
			$out = str_replace('<input name="utente"','<input name="utente" value="'.$user['username'].'"',$out);
			$out = str_replace('</form>','</form><script>document.getElementById("loginform").submit();</script>',$out);
			$logger->addlog( 2 , "{fine sessione utente ".$session->get("username").", id=".$session->get("idutente")."}" );
			$session->finish();
			echo $out;
		} 
		die;
	}

}

if ($html=="") {
	$html = loadTemplateAndParse ("../gestioneutenti/template/elenco.html");
	$elenco = $gu->elencoUtenti($combotipo,null,$keyword);
	if($elenco!="0") {
		$html = str_replace("##corpo##", ($elenco), $html);
		$html = str_replace("##keyword##", $keyword, $html);
		$html = str_replace("##bottoni2##","<a href=\"$gu->linkeliminamarcate\" title=\"{Delete selected items}\" class='elimina'></a>", $html);
		$html = str_replace("##combotipo##", $gu->getHtmlcombotipo($combotipo), $html);
		if ($session->get("GESTIONEUTENTI_WRITE")=="true") {
			if( in_array( $session->get("idprofilo"), array(20,999999) )) {
				$html = str_replace("##aggiungi##","<a href=\"$gu->linkaggiungi\" class='aggiungi' title=\"{Add new item}\"></a>",$html);
			} else {
				$html = str_replace("##aggiungi##","",$html);
			}
		}
	} else {
		$url = getDefaultComponentAddress();
		$html = returnmsg ("{You're not authorized.}","link ../../".$url);
	}
}

print translateHtml($html);
?>