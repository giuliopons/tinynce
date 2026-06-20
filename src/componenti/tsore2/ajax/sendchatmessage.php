<?php
//
// return the answer for a message
//
$root="../../../../";
include($root."src/_include/config.php");
include("../_include/tsore.class.php");
include($root."src/componenti/tsreport/_include/tsreport.class.php");
$obj = new Ore();
$msg = strip_tags($_POST['msg']);
echo translateHtml($obj->sendChatMessage($msg));
?>