function confermaDelete(id) {
	if (gconfirm("Confermi l'eliminazione definitiva di questa tipologia cliente?",function(){
		document.location.href = "index.php?op=elimina&id="+id
	})) { }

}
