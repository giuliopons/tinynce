<?php

//gestione utenti component
$root="../../";
include($root."_include/config.php");
include($root."_include/grid.class.php");
include($root."_include/formcampi.class.php");
include("_include/tsjob.class.php");

//include("../gestioneutenti/_include/user.class.php");

if (!Connessione()) trigger_error(mysql_error());

$sql = "select * from ts_job where id_job=53";
$rs = mysql_query($sql) or die(mysql_error().$sql);

$r=mysql_fetch_array($rs);

echo $r['de_nomejob']."<hr/>";
echo myHtmlspecialchars($r['de_nomejob'])."<hr/>";
echo utf8_decode(myHtmlspecialchars($r['de_nomejob']))."<hr/>";
for($i=0;$i<strlen($r['de_nomejob']);$i++) {
	echo $r['de_nomejob'][$i]." , ";
	echo ord($r['de_nomejob'][$i])."<hr/>";
}

//echo htmlspecialchars($out);
?>