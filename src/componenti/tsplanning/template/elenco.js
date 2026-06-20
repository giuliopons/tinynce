/**
 * populate the job list in the form using the value of the cd_cliente field
 */
function loadList(cd_field, id_field, de_label) {
    console.log("selez " + cd_field + " aggiorno " + id_field);
    valore = $('#' + cd_field).val();
    $.ajax({	'type' : 'GET',
        'url' : 'ajax/lists.php?' + cd_field +'='+valore,
        'dataType': 'json', 
        'success' : function( $response ) { 
            if ($response) { 
                $('#' + id_field).html('');
                if($response.length == 0) {
                    $('#' + id_field).html("<option value='0'>-</option>");
                    $('#' + id_field).attr("disabled", "disabled");

                } else {
                    $('#' + id_field).removeAttr("disabled");
                    $response.forEach(item => {
                        $('#' + id_field).html(
                            $('#' + id_field).html() + '<option value="' + item.key + '">' + item.value + '</option>'
                        );
                    });
                }

                // special behavior for the cd_todo field
                // to allow creation of new todos
                if(de_label == "de_label") {
                
                    // add pencil to edit
                    $('#cd_todo').show();
                    $('#de_label').hide();$('#counterde_label').hide();
                    $('#de_label').attr("placeholder", _e("Add a new task"));
                    $('#de_label').val("");
                    $('#pencil').show();
                    
                    if(id_field == "cd_todo" && $('#' + id_field + " option").val() == "0") {
                        // $('#' + id_field).html("<option value='0'>-</option>");
                        $('#' + id_field).hide();
                        $('#de_label').show();$('#counterde_label').show();
                        $('#pencil').hide();
                        if( $('#cd_cliente').val() == "" ) {
                            $('#de_label').attr("disabled", "disabled");
                        } else {
                            $('#de_label').removeAttr("disabled");
                        }
                    } else {
                        $('#' + id_field).show();
                        $('#' + id_field).trigger("change");
                        $('#de_label').hide();
                    }
                    
                } else {
                    $('#' + id_field).trigger("change");
                }



            } },
        'error' : function () { alert("errore"); }
    });
}


/**
 * send the data of the new task to the server to be stored
 */
function saveDataTodo( data, callback) {
    $.ajax({	'type' : 'POST',
        'url' : 'ajax/todos.php',
        'data' : data,
        'processData': false,
        'success' : function( $response ) { 
            if ($response) { 
                if($response == "ok") {
                    // refresh the list of items
                    callback();
                } else {
                    alert( $response.split("|")[0] == 'ko' ? $response.split("|")[1] : $response, null,"ERROR" );
                }
            }
        },
        'error' : function () { alert("errore"); }
    });
}


/**
 * send the data of the new task to the server to be stored
 */
function saveDataPeople( data, callback) {
    $.ajax({	'type' : 'POST',
        'url' : 'ajax/people.php',
        'data' : data,
        'processData': false,
        'success' : function( $response ) { 
            if ($response) { 
                if($response == "ok") {
                    // refresh the list of items
                    callback();
                } else {
                    alert( $response.split("|")[0] == 'ko' ? $response.split("|")[1] : $response, null, "ERROR" );
                }
            }
        },
        'error' : function () { alert("errore"); }
    });
}

/**
 * function to handle behaviour of the comments, called at startup and after a new page is dinamycally loaded
 */
function commentSetup() {
    jQuery(".comment").each(function() {
        $(this).on("click",function(e){
            e.preventDefault();
            let id = $(this).attr("id").replace("comment_","");
            $('#commentContainer').remove();
            $(this).parent().append("<div id='commentContainer'><textarea id='commentText'></textarea></div>");
            jQuery("#commentText").load('ajax/comment.php?op=get&id=' + id , function() {
                $('#commentText').on("blur",function(e){
                    e.preventDefault();
                    // save
                    let txt = $(this).val();
                    $.ajax({
                        'type' : 'POST',
                        'url' : 'ajax/comment.php',
                        'data' : "op=set&id=" + id + "&comment=" + encodeURIComponent(txt),
                        'processData': false,
                        'success' : function( ) {
                            if(txt == "") {
                                $('#comment_' + id).addClass("icon-comment-empty").removeClass("icon-comment");
                            } else {
                                $('#comment_' + id).addClass("icon-comment").removeClass("icon-comment-empty");
                            }
                            $('#commentContainer').remove();
                        }
                    });
                });
                $('#commentText').on("keydown",function(e){
                    // ESC is the same as blur (save)
                    if(e.keyCode == 27) {
                        e.preventDefault();
                        $('#commentText').trigger("blur");
                    }
                })
                $('#commentText').focus();
            });

        });
    });
}

function switchMode() {
    currentDate = jQuery("#settimana .day-user:first-child").data("date"); 
    let currentMode = jQuery(".titlecontainer a.people").hasClass("active") ? "people" : "jobs";
    document.location.href = "index.php?op=" + (currentMode == "people" ? "jobs" : "people") + "&date=" + currentDate;
}

function showTable( currentDate = "" ) {
    currentDate = currentDate == "" ? jQuery("#settimana .day-user:first-child").data("date") : currentDate; 
    let currentMode = jQuery(".titlecontainer a.people").hasClass("active") ? "people" : "jobs";
    jQuery("<div>").load('index.php?op=' + currentMode + '&date=' + currentDate + ' #settimana', function() {
        jQuery("#settimana").html(jQuery(this).find('#settimana').html());
        jQuery(".corpo").opacity =1;
        commentSetup();
    });
}


function showNextWeek() {
    let currentDate = jQuery("#settimana .day-user:first-child").data("date");
    currentDate = new Date(currentDate);
    currentDate.setDate(currentDate.getDate() + 7);
    showTable( currentDate.toISOString().split("T")[0] );
}

function showPrevWeek() {
    let currentDate = jQuery("#settimana .day-user:first-child").data("date");
    currentDate = new Date(currentDate);
    currentDate.setDate(currentDate.getDate() - 7);
    showTable( currentDate.toISOString().split("T")[0] );
}





function moveCheckFormFunction($response) {                   
    // nella todos.php risposta c'è il codice per verificare il form
    // generato dalla classe dei form
    // lo estraggo, lo modifico per restituire un risultato senza inviare il form
    // e lo aggiungo al DOM
    const scripts = $response.match(/<script[\s\S]*?<\/script>/gi);
    if (scripts) {
        scripts.forEach(scriptTag => {
            const scriptContent = scriptTag.replace(/<script>|<\/script>/gi, '');
            const scriptElement = document.createElement('script');
            scriptElement.text = scriptContent.replace("submit();","return true;");
            document.getElementById("confirmBox").appendChild(scriptElement);
        });
    }
}

function avoidSubmitOnEnter( inputField ) {
    inputField.addEventListener("keydown", function(event) {
        if (event.key === "Enter" && inputField.tagName!="TEXTAREA") {
            event.preventDefault();
        }
    });
}

function addDeleteButtonToConfirmBox( label, callback) {
    // <a id="thirdBtn" href="#">{label}</a>
    const a = document.createElement("a");
    a.setAttribute("id", "thirdBtn");
    a.setAttribute("href", "#");
    a.setAttribute("class", "btn");
    a.innerHTML = label;
    document.getElementById("confirmBox").appendChild( a);
    document.getElementById("thirdBtn").addEventListener("click", function(e) {
        e.preventDefault();
        callback();
    });
}

function checkCdTodoOrLabel() {
    if(jQuery("#cd_todo").val() == "0" && jQuery("#de_label").val().trim() == "") {
        // retrun true on error
        return true;
    }
}

function editTodo(obj) {
    if(parseInt( jQuery('#cd_todo').val() ) > 0) {
        jQuery('#cd_todo').hide();
        jQuery('#de_label').show();$('#counterde_label').show();
        jQuery('#de_label').attr("placeholder", "");
        jQuery('#de_label').val(jQuery('#cd_todo').find(":selected").text());
        jQuery('#pencil').hide();
    }
}

function showAllInOne( id_todo, cd_user, data, id_planning, nu_hours ) {  
    if(data == "") data = jQuery("#settimana .day-user:first-child").data("date"); 
    $.ajax({	'type' : 'GET',
        'url' : 'ajax/people.php?op=getformall&id=' + id_planning + "&cd_user=" + cd_user + "&currentDate=" + data + "&cd_todo=" + id_todo + "&nu_hours=" + nu_hours,
        'success' : function( $response ) { 
            if ($response) { 

                // estraggo lo script del form
                const htmlContent = $response.replace(/<script[\s\S]*?<\/script>/gi, '');

                // button save is the ok in the gconfirm dialog
                gconfirm( htmlContent ,function(){
                    result = checkForm();

                    if (result === true) {

                        // salvo in ajax il nuovo planning
                        saveDataPeople( jQuery("#confirmBox form").serialize(), function() {
                            showTable();
                        });
                        
                    }

                    return result === true ? null : false;
                    
                }, _e("OK"), _e("CANCEL"), function(){
                    
                    // annullo inserimento
                },
                _e(id_planning == 0 ? "Add a planning for a person" : "Edit a planning for a person"));


                moveCheckFormFunction($response);
                avoidSubmitOnEnter( document.dati.nu_hours );
                if(id_planning  > 0) {                    
                    addDeleteButtonToConfirmBox( _e("Delete"), function() {
                        document.dati.op.value ='delete';
                        saveDataPeople( jQuery("#confirmBox form").serialize(), function(){
                            removeCustomAlert();
                            showTable();
                        });
                    })
                }

        } },
        'error' : function () { alert("errore"); }
    });
}

function addItemPeopleOrJob() {
    if( jQuery(".titlecontainer a.people").hasClass("active") ) {
        showAllInOne( 0, 0, '', 0, 0 );
    } else {
        showTodoPopUp(0,0,'')
    }
}



function showTodoPopUp( id_todo, id_job, currentDate ) {
    if(currentDate == "") currentDate = jQuery("#settimana .day-user:first-child").data("date");
    // get the form to edit the todo
    $.ajax({	'type' : 'GET',
        'url' : 'ajax/todos.php?op=getform&id=' + id_todo + '&id_job=' + id_job + "&currentDate=" + currentDate,
        'success' : function( $response ) { 
            if ($response) { 

                // estraggo lo script del form
                const htmlContent = $response.replace(/<script[\s\S]*?<\/script>/gi, '');

                // button save is the ok in the gconfirm dialog
                gconfirm( htmlContent ,function(){
                    result = checkForm();

                    if (result === true) {
                        
                        // salvo in ajax il nuovo todo
                        saveDataTodo( jQuery("#confirmBox form").serialize(), function(){
                            showTable();
                        });
                        
                    }

                    return result === true ? null : false;
                    
                }, _e("OK"), _e("CANCEL"), function(){
                    
                    // annullo inserimento
                },
                _e( id_todo == 0 ? "Add a new task" : "Edit the task" ));

                moveCheckFormFunction($response);
                avoidSubmitOnEnter( document.dati.de_label );
                if(id_todo  > 0) {                    
                    addDeleteButtonToConfirmBox( _e("Delete"), function() {
                        document.dati.op.value ='delete';
                        saveDataTodo( jQuery("#confirmBox form").serialize(), function(){
                            removeCustomAlert();
                            showTable();
                        });
                    })
                }

        } },
        'error' : function () { alert("errore"); }
    });
    
}

jQuery(window).ready(function() {

    // set the current mode
    if(window.location.href.indexOf("op=people")!=-1) {
        jQuery(".titlecontainer a.people").addClass("active");
        jQuery(".titlecontainer a.jobs").removeClass("active");
        jQuery(".titlecontainer a.people").removeAttr("href");
    } else {
        jQuery(".titlecontainer a.jobs").addClass("active");
        jQuery(".titlecontainer a.people").removeClass("active");
        jQuery(".titlecontainer a.jobs").removeAttr("href");
    }

    // on scrolling the page make the .day spans fixed
    jQuery(window).scroll(function() {
        if(jQuery(window).scrollTop() > jQuery("#settimana").offset().top - jQuery(".day").height()) {
            jQuery(".job:first-of-type").addClass("fixed");
            jQuery(".people:first-of-type").addClass("fixed");
        } else {
            jQuery(".job:first-of-type").removeClass("fixed");
            jQuery(".people:first-of-type").removeClass("fixed");
        }
    });

    commentSetup();

});