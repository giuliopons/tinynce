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