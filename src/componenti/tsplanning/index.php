<?php
/*
	manages planning assignment and show planning for the user
*/


$root="../../../";
include($root."src/_include/config.php");
include("_include/planning.class.php");


// update position in title tag
print $ambiente->setPosizione("{Planning}");

$obj = new Planning();

$html="";

$command = postget("op");
$parameter = postget("id");
$date = postget("date",date("Y-m-d"));
// check if valid date
if (!checkdate((int)substr($date,5,2),(int)substr($date,8,2),(int)substr($date,0,4))) {
    $date = date("Y-m-d");
}
$showEmpty = (int)postget("showEmpty", "1");


// if user is simple, show only people mode which will filter only his projects
if($session->get("idprofilo") >= 20 && $command=="") {
    // $command = "jobs";
    $command = isset($_COOKIE['planmode']) ? $_COOKIE['planmode'] : "jobs";
    die("<script>location.href='index.php?op=".$command."&date=".$date."';</script>");
}
if($session->get("idprofilo") < 20 && $command!="people") {
    // $command = "people";
    die("<script>location.href='index.php?op=people&date=".$date."';</script>");
}


if($command=="jobs") {
    // jobs mode show the list of projects
	setcookie("planmode","jobs",time()+3600,"/");
} 
if($command=="people") {
    // people mode show the list of people
	setcookie("planmode","people",time()+3600,"/");
}

// execute command
if (isset($command)) {

	switch ($command) {
		case 'jobs' :
            $html = $obj->jobsView( $date, $showEmpty );
			break;
        case 'people' :
            $html = $obj->peopleView( $date );
            break;
	}

}


print translateHtml($html);