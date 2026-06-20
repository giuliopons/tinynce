<?php
/*

	cron job

*/

$public = true;

$root="../../../";
include($root."src/_include/config.php");
include($root."src/_include/grid.class.php");
include($root."src/_include/formcampi.class.php");
include($root."src/_include/crudbase.class.php");
include("_include/tasks.class.php");
include("_include/grid_callbacks.php");


$obj = new Tasks();

$obj->doCronJob();
