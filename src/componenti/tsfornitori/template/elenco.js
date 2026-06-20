function confermaDelete(id) {
	if (gconfirm("Confermi l'eliminazione definitiva di questo fornitore?",function(){
		document.location.href = "index.php?op=elimina&id="+id
	})) { }

}
