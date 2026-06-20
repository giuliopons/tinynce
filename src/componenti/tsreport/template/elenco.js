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
	
});
