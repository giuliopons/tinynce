<?php
/*

	ics calendar exporter (BETA)
    https://www.barattalo.it/timy2/src/componenti/tstasks/calendar.php?uid=36
*/
$public = true;

$root="../../../";
include($root."src/_include/config.php");
include($root."src/_include/grid.class.php");
include($root."src/_include/formcampi.class.php");
include($root."src/_include/crudbase.class.php");
include("_include/tasks.class.php");
// include("_include/grid_callbacks.php");

$user_id = (int)getpost("uid", 0);
$list_id = (int)getpost("lid", 0);

$obj = new Tasks();

if ($user_id == 0) {
    die("No user and list");
}


$events = $obj->getEventsCalendarRS($user_id, $list_id);

$userObj = execute_row("select * from ".DB_PREFIX."frw_utenti where id='{$user_id}'");

header('Content-Type: text/calendar; charset=utf-8');
header('Content-Disposition: inline; filename=calendar.ics');
echo "BEGIN:VCALENDAR\r\n";
echo "VERSION:2.0\r\n";
echo "PRODID:-//TodoApp//IT\r\n";
echo "CALSCALE:GREGORIAN\r\n";
echo "X-WR-CALNAME:Tasks for {$userObj['nome']} {$userObj['cognome']}\r\n";
echo "X-WR-CALDESC:Events exported from Timy tasks".($list_id > 0 ? ", with list id #{$list_id}" : "")."\r\n";

while($e = $events->fetch_array()) {

    $uid = $e['id_task']."_".$e['cd_list']."@timy.".$_SERVER["HTTP_HOST"];
    $dtstamp = gmdate('Ymd\THis\Z');
    $dtstart = gmdate('Ymd\THis\Z', strtotime($e['dt_expiration']));
    $dtend   = gmdate('Ymd\THis\Z', strtotime($e['dt_expiration']) + 3600);
    $summary = preg_replace('/\s+/', ' ', trim($e['de_taskname']));
    $desc    = preg_replace('/\s+/', ' ', trim(html_entity_decode(strip_tags($e['de_text']))));
    $desc    = str_replace("\xC2\xA0", ' ', $desc); // rimuovi NBSP

    echo "BEGIN:VEVENT\r\n"; 
    echo "UID:$uid\r\n";
    echo "DTSTAMP:$dtstamp\r\n";
    echo "DTSTART:$dtstart\r\n";
    echo "DTEND:$dtend\r\n";
    echo "SUMMARY:" . $summary . "\r\n";
    if ($desc !== '') {
        echo "DESCRIPTION:" . $desc . "\r\n";
    }
    echo "DESCRIPTION:$desc\r\n";
    echo "END:VEVENT\r\n";
}

echo "END:VCALENDAR\r\n";