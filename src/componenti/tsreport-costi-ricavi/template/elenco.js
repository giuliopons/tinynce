function loadjobs(objCliente) {
	var valore
	valore = objCliente.options[objCliente.selectedIndex].value;
	$('#job').html("<option>...loading<option>");
    

	$.ajax({	'type' : 'GET',
		'url' : 'elencojob.php?id='+valore,
		'success' : function( $response ) { 
			if ($response) { 
			var strArrRow = new Array();
			strArrRow =$response.split('|');
			$('#job').html('');
			for (intLoop=0;intLoop<strArrRow.length-1;intLoop++) {
				var strItems = new Array();
				strItems = strArrRow[intLoop].split(',');
				var y=document.createElement('option'); 
				$('#job').html( $('#job').html() + '<option value="'+strItems[0]+'">' + strItems[1] + '</option>' );
			}
		} },
		'error' : function () { alert("errore"); }
	});

}


jQuery(document).ready(function($){

	$('#submit').on("click",function(e){
		e.preventDefault();
		var check = true;
		if( !$('input[name="col_pers"]').is(':checked')
			&& !$('input[name="col_forn"]').is(':checked')
			&& !$('input[name="col_ric"]').is(':checked') ) {
			check = false;
			alert(_e('Select at least one column'));
		}
		if($('#gruppo').val()=='worked') {
			if(!(parseInt($('#cliente').val()) > 0) ) {
				check = false;
				alert(_e('Please select a client'));
			}
		}
		if($('#gruppo').val()=='std') {
			// calculate total duration in months from the two dates "dal" and "al" in yyyy-mm-dd format
			// the two dates must not cover a range larger than 12 months
			var dal = $('#dal').val();
			var al = $('#al').val();

			var date1 = new Date(dal);
			var date2 = new Date(al);

			var timeDiff = Math.abs(date2.getTime() - date1.getTime());
			var diffDays = Math.ceil(timeDiff / (1000 * 3600 * 24));
			var diffMonths = Math.floor(diffDays / 30);
			if(diffDays > 366) {
				// @todo possible error if overlap of 1 day in non bisestile year
				check = false;	
				alert(_e('The range of dates must not cover a range larger than 12 months'));
			}

			
		}
		if(check) checkForm();
	});

	//
	// popup di dettaglio delle celle del report std (costi / ricavi / personale)
	//
	var $rcrPop = null;   // elemento popup corrente
	var rcrCell = null;   // cella (DOM) a cui e' associato il popup aperto

	function rcrClosePopup() {
		if($rcrPop) { $rcrPop.remove(); $rcrPop = null; }
		rcrCell = null;
	}

	function rcrOpenPopup(cell) {
		var $cell = $(cell);
		$rcrPop = $("<div class='rcr-popup'><a href='#' class='rcr-pop-close' title='"+_e('Close')+"'>&times;</a><div class='rcr-pop-body'>"+_e('Loading')+"...</div></div>");
		$("body").append($rcrPop);
		rcrCell = cell;

		// posizionamento sotto la cella, dentro il viewport
		var off = $cell.offset();
		var top = off.top + $cell.outerHeight() + 4;
		var left = off.left;
		var maxLeft = $(window).scrollLeft() + $(window).width() - $rcrPop.outerWidth() - 8;
		if(left > maxLeft) left = Math.max($(window).scrollLeft()+8, maxLeft);
		$rcrPop.css({ top: top+"px", left: left+"px" });

		$.ajax({
			'type': 'GET',
			'url': 'dettaglio.php',
			'data': {
				metric: $cell.data('metric'),
				job:    $cell.data('job'),
				ym:     $cell.data('ym'),
				stato:  $cell.data('stato')
			},
			'success': function(resp) {
				if($rcrPop) $rcrPop.find('.rcr-pop-body').html(resp && resp.trim()!="" ? resp : _e('No data'));
			},
			'error': function() {
				if($rcrPop) $rcrPop.find('.rcr-pop-body').html(_e('No data'));
			}
		});
	}

	$(document).on('click', 'td.rcr-cell', function(e){
		e.preventDefault();
		e.stopPropagation();
		var wasOpen = (rcrCell === this);
		rcrClosePopup();
		if(!wasOpen) rcrOpenPopup(this);   // toggle sulla stessa cella
	});

	// chiusura: pulsante x, click fuori, Esc
	$(document).on('click', '.rcr-pop-close', function(e){ e.preventDefault(); rcrClosePopup(); });
	$(document).on('click', function(e){
		if($rcrPop && !$(e.target).closest('.rcr-popup').length) rcrClosePopup();
	});
	$(document).on('keydown', function(e){ if(e.key === 'Escape') rcrClosePopup(); });

	//
	// matita di modifica dentro il popup: apre il dialog di modifica del ricavo/costo
	//
	$(document).on('click', '.rcr-edit', function(e){
		e.preventDefault();
		e.stopPropagation();
		var metric = $(this).data('metric');
		var id     = $(this).data('id');
		rcrClosePopup();
		rcrOpenEditDialog(metric, id, "", "");
	});

	//
	// cella vuota (ricavo/costo): apre il dialog di inserimento pre-compilato con job e mese
	//
	$(document).on('click', 'td.rcr-empty', function(e){
		e.preventDefault();
		e.stopPropagation();
		rcrOpenEditDialog($(this).data('metric'), "", $(this).data('job'), $(this).data('ym'));
	});

});


//
// Helpers per il dialog di modifica/inserimento (pattern replicato da tsplanning).
//

// estrae lo <script> con checkForm() dalla risposta, lo modifica per non inviare il form
// (submit() -> return true) e lo re-inietta nel #confirmBox, cosi' la validazione e' disponibile.
function moveCheckFormFunction($response) {
	const scripts = $response.match(/<script[\s\S]*?<\/script>/gi);
	if (scripts) {
		scripts.forEach(scriptTag => {
			const scriptContent = scriptTag.replace(/<script>|<\/script>/gi, '');
			const scriptElement = document.createElement('script');
			scriptElement.text = scriptContent.replace("submit();","return true;");
			document.getElementById("confirmBox").appendChild(scriptElement);
		});
	}
}

// evita che Invio in un campo di testo invii davvero il form (che navigherebbe a crud.php)
function avoidSubmitOnEnter( inputField ) {
	if(!inputField) return;
	inputField.addEventListener("keydown", function(event) {
		if (event.key === "Enter" && inputField.tagName!="TEXTAREA") {
			event.preventDefault();
		}
	});
}

// aggiunge il pulsante Elimina al dialog
function addDeleteButtonToConfirmBox( label, callback) {
	const a = document.createElement("a");
	a.setAttribute("id", "thirdBtn");
	a.setAttribute("href", "#");
	a.setAttribute("class", "btn");
	a.innerHTML = label;
	document.getElementById("confirmBox").appendChild(a);
	document.getElementById("thirdBtn").addEventListener("click", function(e) {
		e.preventDefault();
		callback();
	});
}

// aggiunge il quarto pulsante "Apri storico" (link a nuova finestra) dopo il pulsante Elimina
function addHistoryButtonToConfirmBox( label, url ) {
	const a = document.createElement("a");
	a.setAttribute("id", "fourthBtn");
	a.setAttribute("href", url);
	a.setAttribute("target", "_blank");
	a.setAttribute("class", "btn");
	a.innerHTML = label;
	document.getElementById("confirmBox").appendChild(a);
}

// invia i dati del form (save o delete) a crud.php
function rcrSaveImporto( data, callback ) {
	$.ajax({	'type' : 'POST',
		'url' : 'crud.php',
		'data' : data,
		'processData': false,
		'success' : function( $response ) {
			if($response == "ok") {
				callback();
			} else {
				alert( $response.split("|")[0] == 'ko' ? $response.split("|")[1] : $response );
			}
		},
		'error' : function () { alert("errore"); }
	});
}

// apre il dialog: metric = 'ric'|'forn'; id valorizzato = modifica, id vuoto = inserimento
function rcrOpenEditDialog( metric, id, job, ym ) {
	id  = id  || "";
	job = job || "";
	ym  = ym  || "";
	$.ajax({	'type' : 'GET',
		'url' : 'crud.php?op=getform&metric=' + metric + '&id=' + id + '&job=' + job + '&ym=' + ym,
		'success' : function( $response ) {
			if(!$response || $response.trim()=="0" || $response.trim()=="") {
				alert(_e("You're not authorized."));
				return;
			}

			// tolgo lo <script> dal markup visibile (verra' re-iniettato da moveCheckFormFunction)
			const htmlContent = $response.replace(/<script[\s\S]*?<\/script>/gi, '');

			var isEdit = (parseInt(id) > 0);
			// NB: gconfirm/createCustomConfirm traduce gia' il titolo con _e() al suo interno,
			// quindi qui passo la chiave grezza (altrimenti verrebbe tradotta due volte).
			var title  = metric == 'ric'
				? ( isEdit ? "Edit revenue" : "New revenue" )
				: ( isEdit ? "Edit cost"    : "New cost" );

			// il pulsante OK del dialog fa da Salva
			gconfirm( htmlContent, function(){
				var result = checkForm();
				if (result === true) {
					rcrSaveImporto( jQuery("#confirmBox form").serialize(), function(){
						removeCustomAlert();
						rcrRefreshReport();
					});
				}
				return result === true ? null : false;
			}, _e("OK"), _e("CANCEL"), function(){ /* annulla */ }, title );

			moveCheckFormFunction($response);
			jQuery('#confirmBox form').find('input[type=text]').each(function(){ avoidSubmitOnEnter(this); });

			// in modifica: pulsante Elimina + pulsante "Apri storico" (apre l'editor del record,
			// con lo storico completo, in una nuova finestra)
			if(isEdit) {
				addDeleteButtonToConfirmBox( _e("Delete"), function() {
					if(!confirm(_e("Are you sure?"))) return;
					jQuery("#confirmBox form [name=op]").val('delete');
					rcrSaveImporto( jQuery("#confirmBox form").serialize(), function(){
						removeCustomAlert();
						rcrRefreshReport();
					});
				});
				var histUrl = (metric == 'forn' ? '../tscosti' : '../tsricavi') + '/index.php?op=modifica&id=' + id;
				addHistoryButtonToConfirmBox( _e("Open history"), histUrl );
			}
		},
		'error' : function () { alert("errore"); }
	});
}

// ricarica la griglia del report ri-eseguendo la ricerca corrente (POST del form filtri)
function rcrRefreshReport() {
	var $form = $('.filters form').first();
	if(!$form.length) $form = $('form').first();
	var data = $form.serialize(); // contiene gia' l'hidden op=cerca
	$('.corpo').css('opacity', .5);
	$('<div>').load('index.php .corpo', data, function(){
		$('.corpo').html( $(this).find('.corpo').html() ).css('opacity', 1);
	});
}
