var idutente = -1;


function removeJob(id,ute) {
	url = 'ajax/eliminajob.php?ute='+encodeURIComponent(ute)+'&job='+encodeURIComponent(id);

	$.ajax({	'type' : 'GET',
		'url' : url,
		'success' : function( $response ) { 
			if ($response=='ok')
				{
					document.location.href=document.location.href;
				} else {
					alert($response);
				}
		 },
		'error' : function () { alert("errore"); }
	});

}

function salvatbc(id,comboidJob) {

	job = $('#' + comboidJob).value;

	url = 'ajax/salvatbc.php?ute='+encodeURIComponent(id)+'&job='+encodeURIComponent(job) //+'&ruolo='+encodeURI(ruolo);

	$.ajax({	'type' : 'GET',
		'url':url,
		'success' : function( response ) { 
			if (response=='ok')
			{
				document.location.href=document.location.href;
			} else {
				alert(response);
			}  },
		'error' : function () { alert("errore"); }
	});

}



function checkEnter(obj,event) {
	if (event.keyCode == 13) {
		obj.blur();
	}
}

function editThis(job,data,tipoora) {
	// var tipo2 = tipo.replace(/ /g,"_");
	var temp = $('#a'+job+'_'+data+"_"+tipoora).html();
	var notatemp = "";
	if ($('#nota'+job+"_"+data+"_"+tipoora).length>0)
	{
	notatemp = $('#nota'+job+"_"+data+"_"+tipoora).html();
	}
	$('#td'+job+'_'+data+"_"+tipoora).html ("<input type='text' maxlength='4' id='input"+job+"_"+data+"_"+tipoora+"' value=\""+temp+"\" onblur=\"saveThis('"+job+"','"+data+"',this.value,'"+encodeURIComponent(notatemp)+"','"+tipoora+"')\" onkeyup=\"checkEnter(this,event)\">");
	$('#input'+job+'_'+data+"_"+tipoora).focus();
}





function saveThis(job,data,value,note,tipoora) {
	note = decodeURIComponent(note);
	value = value.replace(/([^0-9,\.]+)/, "");
	if (value=="") { value ="0"; }
	
	// var tipo2 = tipo.replace(/ /g,"_");

	var temp = strToFloat(value);
	
	
/*	SPENTO PER INSERIMENTO TOTALE DELLE ORE ARRETRATE
	if (temp>12)
	{
		//max ore inseribili, poi si è poco credibili :)
		temp = 12;
	}
*/	
	

	if (temp!=NaN && temp>=0) {
		value = floatToStrOre(temp);
	}

	if (value=='0,0') {
		note = "";
		value = "0";
	} else {
		if(note=="") classenota = 'icon-comment-empty'; else classenota = 'icon-comment';
		note = "<a title='edita la nota' class=\""+ classenota +" nota\" href=\"javascript:editNote('"+job+"','"+data+"','"+tipoora+"')\"></a><div id='nota"+job+"_"+data+'_'+tipoora+"' style='display:none;' class='notina'>"+note+"</div> ";
	}

	url = 'ajax/salvaora.php?ute='+encodeURIComponent(idutente)+'&job='+encodeURIComponent(job)+'&data='+encodeURIComponent(data)+'&value='+encodeURIComponent(value)+'&tipoora='+encodeURIComponent(tipoora);

	
	// if (value!=oldvalue)
		$.ajax({	'type' : 'GET',
			'url':url,
			'success' : function( response ) { 
				$('#td'+job+'_'+data+"_"+tipoora).html( note + "<a id='a"+job+"_"+data+"_"+tipoora+"' href=\"javascript:editThis('"+job+"','"+data+"','"+tipoora+"')\">"+value+"</a>" );
				calcolatotali();
				if ($('#tabella').length>0) {
					getcal(data.substring(0, 4) + "-" + data.substring(4, 6) + "-" + data.substring(6, 8));
				}
			},
			'error' : function () { alert("errore"); }
		});

}

function editatag(id, onbluraction, tipoora) {
	// riceve l'id del tag
	// da sostituire con una input o una
	// textarea
	var testo  = $('#' + id).html();
	// console.log ( " onbluraction " );
	$('#' + id).html ( "<textarea id=\"editTag"+id+"\" onblur=\"" + onbluraction + "(this,'"+id+"','"+tipoora+"');\">"+testo+"</textarea>");	
	$("#editTag"+id).focus();
}

function salvatag(objTextbox,originalId,tipoora) {
	var testo = objTextbox.value;
	$('#' + originalId).html( testo );
	if(testo=="") classenota = 'icon-comment-empty'; else classenota = 'icon-comment';
	$('#' + originalId).parent().find('a.nota').attr("class",classenota+" nota");
	url = 'ajax/salvanota.php?ute='+encodeURIComponent(idutente)+'&id='+encodeURIComponent(originalId)+'&testo='+encodeURIComponent(testo)+'&tipoora='+encodeURIComponent(tipoora);
	

	$.ajax({	'type' : 'GET',
		'url':url,
		'success' : function( response ) { 
			if (response=='ok')
				{
					$('#'+ originalId).hide();

				} else {
					alert(response);
				}
		},
		'error' : function () { alert("errore"); }
	});
	

}

function editNote(j,d,tipoora) {
	//
	// visualizza la nota e rende editabile il testo
	//
	// tipo2 = tipo.replace(/ /g,"_");
	$('#nota'+j+'_'+d+"_"+tipoora).toggle();

	//window.alert($('#nota'+j+'_'+d+tipo2+"_"+tipoora).is(":visible"));
	if ($('#nota'+j+'_'+d+"_"+tipoora).is(":visible")
			
	)
	{
		//window.oldalert('accendo nota');
		$('#nota'+j+'_'+d+"_"+tipoora).show();
		editatag('nota'+j+'_'+d+"_"+tipoora,'salvatag',tipoora);
	}
}

function calcolatotali() {
	var sommeJob = new Array();
	var sommeDay = new Array();

	$('#timesheet a').each(function(){
		var re = /^a(\d+)_(\d+)([0-9a-z\_]+)$/
	    if (re.test($(this).attr("id"))) {
			var arr = re.exec( $(this).attr("id") );
			j = (arr[1]+arr[3]);
			d = (arr[2]);
			sommeJob[j]=0;
			sommeDay[d]=0;
	    }

	});


	$('#timesheet a').each(function(){
		var re = /^a(\d+)_(\d+)([0-9a-z\_]+)$/
	    if (re.test($(this).attr("id"))) {
			var arr = re.exec( $(this).attr("id") );
			j = (arr[1]+arr[3]);
			d = (arr[2]);
			sommeJob[j] += strToFloat($(this).html());
			sommeDay[d] += strToFloat($(this).html());
	    }

	});

	for (s in sommeJob)
	{
		if (sommeJob[s])
		{
			try
			{
				//o = o + typeof($('jobtot'+s));
			$('#jobtot'+s).html( floatToStrOre(sommeJob[s]) );	
			}
			catch (e)
			{
			}
		} else {
			try
			{
			$('#jobtot'+s).html( "0" );				
			}
			catch (e)
			{
			}
		}
	}

	//alert(o)

	for (var el in sommeDay)
	{
		if (sommeDay[el])
		{
			try
			{
			$('#daytot'+el).html( floatToStrOre(sommeDay[el]) );	
			}
			catch (e)
			{
			}
			
		} else {
			try
			{
			$('#daytot'+el).html( "0" );
			}
			catch (e)
			{
			}
		}
	}

}

/*
function confermaDeleteCheck(theForm) {

    var c;
	c=0;
    for (var i = 0; i < theForm.elements['gridcheck[]'].length; i++)
    {
       if (theForm.elements['gridcheck[]'][i].checked)
       {
		   c=1;
       }
    }
   if (c==0)
   {
	   if (theForm.elements['gridcheck[]'].length==undefined)
	   {
		   if (theForm.elements['gridcheck[]'].checked==false)
		   {
			   alert ('Non hai selezionato nessun record da eliminare');
			   return;
		   }
	   } else {
			   alert ('Non hai selezionato nessuna record da eliminare');
			   return;
	   }

   }

		if (confirm('Sei sicuro di voler eliminare i record selezionati?'))
		{

			theForm.op.value="eliminaSelezionati";
			theForm.submit();
		}

}
*/




function row_highlight(trObj,stato) {
	if (stato=='on')
	{
		metti = "highlightedRow";
	} else {
		metti = "";
	}
	if (trObj.className == "highlightedRow") {
		trObj.className = metti;
	} else {
		trObj.className = metti;
	}
} 


var refreshSn = function ()
{
    var time = 5000; // 5 secs
    setTimeout(
        function ()
        {
        $.ajax({
           url: 'ajax/refresh_session.php',
           cache: false,
           complete: function (s) {
			   if (s.responseText.indexOf("login.php")!=-1) {
				   alert("Sessione scaduta. Esci e fai login di nuovo, perch&egrave; i dati non vengono salvati: <a href='../../logout.php'>&raquo; Esci.</a>");
			   }
			   //	console.log(s);
			   refreshSn();
		   }
        });
    },
    time
);
};
setTimeout("refreshSn()",5000);
