
function applyNoteColors() {
    $('#container_ts_notes .griglia tbody tr').each(function() {
        var color = $(this).find('a.stress[data-color]').data('color');
        if (color) {
            $(this).css('--note-color', color);
        } else {
            $(this).css('--note-color', '');
        }
    });
}

$(document).ready(applyNoteColors);
$(document).on('gridReloaded', applyNoteColors);

// collect checked note ids and call toggleArchive via AJAX
var funcTog = function() {
    var theForm = $('#gridWrapper_ts_notes').find('form')[0];
    var s = "";
    var checkboxes = theForm.elements['gridcheck[]'];
    if (checkboxes) {
        if (checkboxes.length === undefined) {
            // single checkbox
            if (checkboxes.checked) s = checkboxes.value;
        } else {
            for (var i = 0; i < checkboxes.length; i++) {
                if (checkboxes[i].checked) {
                    s += (s === "" ? "" : ",") + checkboxes[i].value;
                }
            }
        }
    }
    url = "ajax/toggleArchive.php?ids=" + s;
    $.ajax({
        'type': 'GET',
        'url': url,
        'success': function(response) {
            if (response == 1) {
                var tot = 0;
                jQuery(theForm).find("tr").each(function() {
                    if ($(this).find("input[type=checkbox]").is(":checked")) {
                        tot++;
                        $(this).remove();
                    }
                });
                gridOddEven();

                // update counter in the select
                var curVal = $("#filtro option:selected").val();
                var curCount = parseInt($("#filtro option:selected").data("count")) || 0;
                var newCount = curCount - tot;
                var curLabel = $("#filtro option:selected").data("label");
                $("#filtro option:selected").data("count", newCount);
                $("#filtro option:selected").text(curLabel + (newCount > 0 ? " (" + newCount + ")" : ""));

                // update counter in opposite option
                var otherVal = (curVal === "archive") ? "" : "archive";
                var otherOpt = $("#filtro option[value='" + otherVal + "']");
                var otherCount = (parseInt(otherOpt.data("count")) || 0) + tot;
                var otherLabel = otherOpt.data("label");
                otherOpt.data("count", otherCount);
                otherOpt.text(otherLabel + (otherCount > 0 ? " (" + otherCount + ")" : ""));
            }
        },
        'error': function() { alert("errore"); }
    });
};

function toggleArchiveStatus(obj, e) {
    e.preventDefault();
    var theForm = $('#gridWrapper_ts_notes').find('form')[0];

    var c = 0;
    var checkboxes = theForm.elements['gridcheck[]'];
    if (checkboxes) {
        if (checkboxes.length === undefined) {
            if (checkboxes.checked) c = 1;
        } else {
            for (var i = 0; i < checkboxes.length; i++) {
                if (checkboxes[i].checked) c = 1;
            }
        }
    }
    if (c === 0) {
        alert(_e("You don't have selected any item."));
        return;
    }
    gconfirm(_e("Are you sure to move to Archive?"), funcTog, _e("YES"), _e("NO"));
}
