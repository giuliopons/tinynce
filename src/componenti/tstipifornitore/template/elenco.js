function confermaDelete(id) {
	if (gconfirm("Confermi l'eliminazione definitiva di questa tipologia fornitore?",function(){
		document.location.href = "index.php?op=elimina&id="+id
	})) { }

}
