function confermaDelete(id) {
	if (gconfirm("Confermi l'eliminazione definitiva di questo ricavo?",function(){
		document.location.href = "index.php?op=elimina&id="+id
	})) { }

}
