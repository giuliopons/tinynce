var idutente = -1;


function strToFloat(stringa){
	// riceve una stringa tipo 1.000,10 e la converte in un float.
	//
	if (stringa=="") stringa="0";
	stringa = stringa + ""
	stringa = stringa.replace(/\./g, ",")	// considero i punti come le ,
	stringa = stringa.replace(/,/g, ".")	// sostituisce la , con il . (perche' js capisce il .)
	return parseFloat(stringa)
}

function floatToStrOre(flot) {
	// riceve numero float ed esce stringa con 2 decimali con virgola.
	// Non mette il separatore delle migliaia.
	//
	var strFlot = ""
	flot = (Math.round(flot * 10)) / 10	//arrotondo a due decimali
	strFlot = flot.toString() + ""
	if (strFlot.indexOf(".") > -1)
		strFlot = strFlot.replace(/\.(\d)$/, ",$1")
	else
		strFlot = strFlot + ",0"
	return strFlot
}


function getcal(data) {
	// $('#loading').addClass("small").show();
	url = 'ajax/getcal.php?data='+encodeURIComponent(data)+'&utente='+encodeURIComponent(idutente);

	$.ajax({	'type' : 'GET',
		'url' : url,
		'success' : function( $response ) { 
			if ($response) { 
				$('#tabella').html($response);
				$('#inputarea').html(_e("Select a day from the calendar"));
			 } 
            //  $('#loading').hide().removeClass("small");
            },
		'error' : function () { alert("errore"); 
			// $('#loading').hide(); 
		}
	});
}

function getFormClienteJob(obj,event,data) {
	if(event!=null) {
		event.preventDefault();
	}
	if(obj!=null) {
		// select day in calendar
		$("#cal td").removeClass("selected");
		$(obj).parent().addClass("selected");
	}
	$('#inputarea').html( $('#loading').html());
	// $('#loading').show();
	// $('#inputarea').html('');

	$('#settimana').html('');
	url = 'ajax/getformcliente.php?data='+encodeURIComponent(data)+'&utente='+encodeURIComponent(idutente);
	$.ajax({	'type' : 'GET',
		'url':url,
		'success' : function( $response ) { 
			if ($response) { 
                $('#inputarea').html($response);
				mobileScrollToInput();
				getComboCliente();
				getSettimana(data);
			 } 
            //  $('#loading').hide();
            },
		'error' : function () { alert("errore"); 
			// $('#loading').hide(); 
		}
	});

}

function mobileScrollToInput() {
	if($(window).width() > 768) return;
	$('#inputarea').addClass("expand");
	$('html, body').animate({
		scrollTop: $("#inputarea").offset().top - $('.panel2').outerHeight()
	}, 500);
}

function getComboCliente(firstChoose=true, callback=null) {
	// $('#loading').show();
	$('#clientewrapper').hide();
	$('#jobwrapper').hide();
	$('#orewrapper').hide();
	$('#tipoora').hide();
	data = $('#giorno').val();
	url = 'ajax/getcombocliente.php?data='+encodeURIComponent(data)+'&utente='+encodeURIComponent(idutente);

	$.ajax({	'type' : 'GET',
		'url' : url,
		'success' : function( response ) { 
			// $('#loading').hide();
			if (response) { 
				if(firstChoose) $options = '<option value="">--' + _e('choose') + '--</option>' + response ;
					else $options =  response ;
				$('#cliente').html( $options );
				$('#job').html("");
				$('#clientewrapper').show();
				$('#cliente')[0].selectedIndex = 0;
				if($('#cliente').val()!='') {
					getComboJob(callback);
				}
				if(typeof(callback)=="function") callback(response);
				} },
		'error' : function () { alert("errore");
			//  $('#loading').hide(); 
		}
	});
}

function getComboJob(callback=null) {
	// $('#loading').show();
	$('#jobwrapper').hide();
	$('#orewrapper').hide();$('#tipoora').hide();
	$('#notewrapper').hide();
	data = $('#giorno').val();
	cliente = $('#cliente').val();
	url = 'ajax/getcombojob.php?data='+encodeURIComponent(data)+'&utente='+encodeURIComponent(idutente)+'&cliente='+encodeURIComponent(cliente);

	$.ajax({	'type' : 'GET',
		'url':url,
		'success' : function( response ) { 
            // $('#loading').hide();
			if (response) { 
				$('#job').html(response);
                $('#job').unbind("change").on("change", selectJobHourType);
                selectJobHourType();
				$('#jobwrapper').show(); 
				$('#orewrapper').show();
				$('#tipoora').show();
				$('#notewrapper').show();
				if(typeof(callback)=='function') callback(response);
				// if(typeof(scrollBottom)=='function') callback(response);
				
			 } },
		'error' : function () { alert("errore"); 
			// $('#loading').hide(); 
		}
	});


}

function selectJobHourType() {
    var type = $('#job').find(":selected").data("rel");
    if(!type) $('#tipoora options:first').val();
     else  $('#tipoora').val(type);
}



function recenti () {
	if($('a.scadd').is(":visible")) {
		$('a.scadd').hide("fast");
		$('a.scadd-title').html("+<span>recenti</span>");
	} else {
		$('a.scadd').show("slow");
		$('a.scadd-title').html("-");
	}
}
function goSalvaOraNote (tipoora,data,idutente,ore,note,job,forceinsert,callback) {
    // $('#loading').addClass("small").show();
	url = 'ajax/salvaoranote.php?' +
		'tipoora='+encodeURIComponent(tipoora)+
		'&data='+encodeURIComponent(data)+
		'&utente='+encodeURIComponent(idutente)+
		'&ore='+encodeURIComponent(ore)+
		'&note='+encodeURIComponent(note)+
		'&job='+encodeURIComponent(job);
	if(forceinsert) url = url + "&forceinsert=1";
	$.ajax({	'type' : 'GET',
		'url':url,
		'success' : function( response ) { 
			if (response) { 
				callback(response);
			 } 
            //  $('#loading').hide().removeClass("small");
            },
		'error' : function () { alert("errore"); 
			// $('#loading').hide(); 
			 $('#inputarea').show();
		}
	});
}


function scrollToSettimana() {
	// if($(window).width() > 768) return;
	$('#settimana').addClass("expand");
	$('html, body').animate({
		scrollTop: $("#settimana").offset().top - $('.panel2').outerHeight()
	}, 500);
}

function salvaOraNote() {
	ore = $('#ore').val();
	ore = strToFloat(ore);
	if (ore<=0) { alert("Non hai inserito un numero di ore valido."); return false; } else {
		$('#inputarea').hide();
		data = $('#giorno').val();
		job = $('#job').val();
		note = $('#note').val();
		tipoora = $('#tipoora').val();

		goSalvaOraNote(tipoora,data,idutente,ore,note,job,false, function(response){
			$('#inputarea').html("");
			getcal(data);
			// $('#inputarea').html("Ore salvate.");
			// $('#settimana').html("");
			$('#inputarea').show();
			getSettimana(data);
			scrollToSettimana();
			// getFormClienteJob(null, null, data);

		});

	}
	return true;
}

function getSettimana(data) {
	
	if(data=='') {
		data = $('#giorno').val();
	}
	if (data === undefined) {
		if ($("#cal td.selected a").length > 0) {
			data = $("#cal td.selected a").attr("rel");
		} else if(($("#cal td a.oggi").length > 0)){
			data = $("#cal td a.oggi").attr("rel");
		} else {
			data = $("#cal td:first a").attr("rel");
		}
	}
	url = 'ajax/getelenco.php?data='+encodeURIComponent(data)+'&utente='+encodeURIComponent(idutente);

	$.ajax({	'type' : 'GET',
		'url':url,
		'success' : function( response ) { 
			if (response) { 
				$('#settimana').html(response);
				calcolatotali();
				tooltips();
			 } },
		'error' : function () { alert("errore"); }
	});

}

function caricaOreSettimanaPrecedente() {
	data = $('#giorno').val();
	url = 'ajax/getsettimanaprecedente.php?utente='+encodeURIComponent(idutente)+'&data='+encodeURIComponent(data);
	
	$.ajax({	'type' : 'GET',
		'url':url,
		'success' : function( response ) { 
			if (response=="0") {
				alert("Attenzione!<br/>Non posso caricare le ore della settimana precedente perch&egrave; la settimana precedente non hai caricato nessuna ora.");
				} else {
					$('#settimana').html(response);
					calcolatotali();
				}  },
		'error' : function () { alert("errore"); }
	});

}



$(document).ready(function() {
	if($('a.oggi').length>0) {
		// getFormClienteJob(null,null,$('a.oggi').attr("rel"));
	}
} );