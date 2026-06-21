<?php
/**
 * Shared endpoint: list of jobs in json format for the cd_job autocomplete field.
 * Used by tsricavi, tscosti (and any component needing a job picker).
 *   ?term=<text>  -> JSON array of {id, value}
 *   ?id=<id_job>  -> JSON string "CODE - Name" (to prefill on edit)
 */
header('Content-Type: application/json');

$root="../../../../";
include($root."src/_include/config.php");

$term = postget("term","");
$id   = postget("id","0");

if($term!="") {
	$stmt = $conn->prepare("SELECT id_job, CONCAT(de_codice,' - ',de_nomejob) AS nome FROM ".DB_PREFIX."ts_job WHERE de_codice LIKE ? OR de_nomejob LIKE ? ORDER BY de_codice LIMIT 30");
	$search = "%".$term."%";
	$stmt->bind_param("ss", $search, $search);
	$stmt->execute();
	$jobs = array();
	$res = $stmt->get_result();
	while($row = $res->fetch_assoc()) {
		$jobs[] = array("id"=>$row['id_job'], "value"=>$row['nome']);
	}
	echo json_encode($jobs);
} else {
	if($id!="0") {
		echo json_encode(execute_scalar("SELECT CONCAT(de_codice,' - ',de_nomejob) FROM ".DB_PREFIX."ts_job WHERE id_job='".(int)$id."'",""));
	}
}
