function confermaDelete(id) {
	if (gconfirm("Confermi l'eliminazione definitiva di questo cliente?<br>Verranno cancellati anche tutte le commesse e tutte le ore caricate su quelle commesse.",function(){
		document.location.href = "index.php?op=elimina&id="+id
	})) { }
		
}
