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

});
