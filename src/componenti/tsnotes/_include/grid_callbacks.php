<?php
/*
    function for grid list
*/

function linknote($p, $id) {
	global $obj;
	$link = str_replace("##id_note##", $id, $obj->linkmodifica);
	return '<a href="'.$link.'" title="{Edit}">'.$p.'</a>';
}

function colornote($p, $id) {
	global $obj;
	$p2 = explode("|^", $p);
	$link = str_replace("##id_note##", $id, $obj->linkmodifica);
	$colore = $p2[1] ?? '';
	$bg = $colore !== '' ? $colore : 'inherit';
	$fg = fg_from_bg($colore);
	return '<a href="'.$link.'" title="{Edit}" class="stress" data-color="'.$colore.'" style="background-color:'.$bg.';color:'.$fg.';">'.$p2[0].'</a>';
}

function truncatenote($p, $id) {
	$text = strip_tags($p);
	$text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
	$text = trim(preg_replace('/\s+/', ' ', $text));
	if (mb_strlen($text, 'UTF-8') > 200) {
		$text = mb_substr($text, 0, 200, 'UTF-8') . '...';
	}
	return '<span class="note-preview">' . htmlspecialchars($text, ENT_QUOTES, 'UTF-8') . '</span>';
}
