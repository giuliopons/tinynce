function confermaDelete(id) {
	if (gconfirm("Confermi l'eliminazione definitiva di questa commessa?<br>Verranno cancellate anche tutte le ore caricate.",function(){
		document.location.href = "index.php?op=elimina&id="+id
	})) { }
		
}

