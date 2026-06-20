
function getAddTaskLink(e) {
    e.preventDefault();
    document.location.href ="?op=aggiungi&combotipo=" + $('#combotipo').val();
}

function setStato (obj,c,id) {
    var td = $(obj).parent();
    if(c=='0') jQuery(td).html("<span class=\"labelgrey\">...</span>");
    if(c=='1') jQuery(td).html("<span class=\"labelgrey\">...</span>");
    url = "ajax/toggleStato.php?op="+c+"&id="+id;
    $.ajax({	'type' : 'GET',
        'url' : url,
        'success' : function( response ) { 
            if (response) { 
                jQuery(td).html(response);
            } },
        'error' : function () { alert("errore"); }
    });
}

function setPriority (obj,c,id) {
    var td = $(obj).parent();
    if(c==0) jQuery(td).html("<span class=\"dot_grey\"></span>");
    if(c==1) jQuery(td).html("<span class=\"dot_grey\"></span>");
    url = "ajax/togglePriority.php?op="+c+"&id="+id;
    $.ajax({	'type' : 'GET',
        'url' : url,
        'success' : function( response ) { 
            if (response) { 
                jQuery(td).html(response);
            } },
        'error' : function () { alert("errore"); }
    });
}


// fix numbers counter in select
function fixOpt(s, tot) {
    var q = $(s).data("count");
    var count = parseInt(q) - tot;
    var label = $(s).data("label");
    $(s).data("count",count);
    $(s).text(label+" ("+count+")");
};

var funcTog = function() {
    theForm = $('#gridWrapper_ts_tasks').find('form')[0];
    var s = "";
    for (var i = 0; i < theForm.elements['gridcheck[]'].length; i++) {
        if (theForm.elements['gridcheck[]'][i].checked) {
            s+=(s==""?"":",") + theForm.elements['gridcheck[]'][i].value;
        }
    }
    url = "ajax/toggleArchive.php?ids="+s;
    $.ajax({	'type' : 'GET',
        'url' : url,
        'success' : function( response ) { 
            if(response==1) {
                var tot = 0;
                jQuery(theForm).find("tr").each(function(){
                    if($(this).find("input[type=checkbox]").is(":checked")) {tot++;
                        $(this).remove();}
                });
                gridOddEven();

                fixOpt("#combotipo option:selected", tot);
                
                var x = $("#combotipo option:selected").val();
                console.log(x);

                if(x.indexOf("_archive")!=-1) {
                    newIndex = x.replace("_archive","");
                } else {
                    newIndex = x + "_archive"; 
                }
                
                fixOpt("#combotipo option[value=" + newIndex + "]", -tot);

            }
            },
        'error' : function () { alert("errore"); }
    });
}

function toggleArchiveStatus(obj,e) {
    e.preventDefault();
    theForm = $('#gridWrapper_ts_tasks').find('form')[0];

    var c = 0;
    for (var i = 0; i < theForm.elements['gridcheck[]'].length; i++) {
        if (theForm.elements['gridcheck[]'][i].checked) c=1;
    }
    if (c==0) {
        if (theForm.elements['gridcheck[]'].length==undefined) {
            if (theForm.elements['gridcheck[]'].checked==false) {
                alert (_e("You don't have selected any item."));
                return;
            }
        } else {
            alert ( _e("You don't have selected any item."));
            return;
        }
    }
    if (theForm.name) {
        if (gconfirm( _e("Are you sure to move to Archive?") ,
            funcTog
        ,_e("YES"),_e("NO"))) {}
    }
}