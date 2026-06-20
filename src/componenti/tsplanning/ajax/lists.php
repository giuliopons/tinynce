<?php
header('Content-Type: application/json');
$root="../../../../";
include($root."src/_include/config.php");
include("../_include/planning.class.php");
include($root."src/_include/formcampi.class.php");

$obj = new Planning();

if(isset($_GET["cd_cliente"])) {
    $_GET["cd_cliente"] = intval($_GET["cd_cliente"]);
    $output = $obj->getJobListJSON($_GET["cd_cliente"]);
} elseif(isset($_GET["cd_job"])) {
    $_GET["cd_job"] = intval($_GET["cd_job"]);
    $output = $obj->getTodoListJSON($_GET["cd_job"]);
    
} else {
    $output = "[]";
}
echo translateHtml ( $output );