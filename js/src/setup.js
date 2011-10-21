function refreshTime() {
    var now = new Date(),
    startDateText = $("#date_start").val(),
    startDate = new Date(startDateText),
    endDateText = $("#date_end").val(),
    endDate = new Date(endDateText),
    startTime = parseInt($("#time_start").val()),
    endTime = parseInt($("#time_end").val());

    if(!endDateText.length || startDate.getTime() > endDate.getTime()) {
        endDateText = $("#date_start").val();
        endDate = new Date(endDateText);
        $("#date_end").datepicker('setDate', $("#date_start").datepicker('getDate'));
    }

    var maxDate = new Date(startDate.getTime()+tsMax*3600000);
    $("#date_end").datepicker('option', 'maxDate', maxDate);
    $("#date_end").datepicker('option', 'minDate', $("#date_start").datepicker('getDate'));

    $("#time_start option, #time_end option").removeAttr('disabled');

    if(startDate.getMonth == now.getMonth && startDate.getDate() == now.getDate()) {
        var minTime = tsMin+now.getHours();
        if(minTime > startTime) {
            startTime = minTime;
            $("#time_start").val(startTime);
        }
        $("#time_start option:lt("+(minTime+1)+")").attr('disabled', 'disabled');
    }

    if(startDate.getTime() == endDate.getTime() && startTime >= 0) {
        $("#time_end option:lt("+(startTime+2)+")").attr('disabled', 'disabled');
        if(startTime >= endTime) {
            endTime = startTime+1;
            $("#time_end").val(endTime);
        }
    }
    else if(startDateText.length && startTime >= 0) {
        startDate.setHours(startTime);
        var maxTime = tsMax-((endDate.getTime()-startDate.getTime())/3600000);
        if(endTime > maxTime) {
            endTime = maxTime;
            $("#time_end").val(endTime);
        }
        $("#time_end option:gt("+(maxTime+1)+")").attr('disabled', 'disabled');
    }

    if(startDateText.length && endDateText.length && startTime >= 0 && endTime >= 0) {
        startDate.setHours(startTime);
        endDate.setHours(endTime);
        var numHours = (endDate.getTime()-startDate.getTime())/3600000,
        cost = (numHours*tsPrice).toFixed(2);
        $("#previewtime").text("Total: "+numHours+" hours ($"+cost+")");
    }
}
function verifyFormSetup() {
    var error = 0;
    $("input, select").removeClass("focus");
    $("#setup input").not("#hostname, #sv_password").each(function(i) {
        if(this.value == '') {
            $(this).addClass("focus");
            error = 1;
        }
    });
    if($("#time_start").val() == '-1') {
        $("#time_start").addClass("focus");
        error = 1;
    }
    if($("#time_end").val() == '-1') {
        $("#time_end").addClass("focus");
        error = 1;
    }
    if($("#game").val() == '--Select Game--') {
        $("#game").addClass("focus");
        error = 1;
    }
    if($("#user").length && error == 0) {
        $.ajax({
            url: 'user',
            type: 'POST',
            async: false,
            data: {
                mode: "login",
                user: $("#user").val(),
                pass: $("#pass").val()
            },
            success: function(data) {
                if(data != 0) {
                    error = 2;
                }
            }
        });
    }
    if(error == 0) {
        $.ajax({
            url: 'res',
            type: 'POST',
            async: false,
            data: {
                date_start: $("#date_start").val(),
                date_end: $("#date_end").val(),
                time_start: $("#time_start").val(),
                time_end: $("#time_end").val(),
                rcon: $("#rcon_password").val()
            },
            success: function(data) {
                error = parseInt(data);
            }
        });
    }
    if(error > 0) {
        switch(error) {
            case 1:
                $("#error").text('Please fill in all required fields.');
                break;
            case 2:
                $("#error").text('Error: Invalid user and/or password');
                break;
            case 3:
                $("#error").text('Error: Invalid date entered.');
                $("#date_start").addClass("focus");
                break;
            case 4:
                $("#error").text('Error: All available servers are booked within this timeslot.');
                $("#date_start, #time_start").addClass("focus");
                break;
            case 5:
                $("#error").text('Please limit the Rcon password to alphanumeric characters (0-9, A-Z).');
                $("#rcon_password").addClass("focus");
                break;
        }
        $("#error").removeClass('hidden');
        return false;
    }
    return true;
}
$(document).ready(function() {
    if($("#userstatus").hasClass('nologon')) {
        $("#loginlink").live('click', function() {
            $("#modalform").html("Loading...");
            $("#modalform").load('user', {
                mode: 'form',
                type: 'login'
            });
            $("#modalform").dialog('option', 'buttons', {
                "Login": userLogin
            });
            $("#modalform").dialog('option', 'title', 'Please Login');
            $("#modalform").dialog('option', 'width', 300);
            return false;
        });

        $("#registerlink").live('click', function() {
            $("#modalform").html("Loading...");
            $("#modalform").load('user', {
                mode: 'form',
                type: 'register'
            }, function() {
                $("#username").keyup(checkUser).blur(checkUser);
                $("#email").blur(checkEmail);
                $("#password2").blur(checkPass);
                $("#reloadcaptcha").click(reloadCaptcha);
                guessTZ();
            });
            $("#modalform").dialog('option', 'buttons', {
                "Register": userRegister
            });
            $("#modalform").dialog('option', 'title', 'Registration');
            $("#modalform").dialog('option', 'width', 450);
            return false;
        });

        $("#modalform").dialog({
            closeOnEscape: false,
            draggable: false,
            resizable: false,
            open: function() {
                $("#modalform").parents(".ui-dialog:first").find(".ui-dialog-titlebar-close").remove();
            },
            buttons: {
                "Login": userLogin
            },
            width: 300
        });
    }
    else {
        $("#date_start, #date_end").datepicker({
            mandatory: true,
            changeMonth: false,
            changeYear: false,
            minDate: 0,
            hideIfNoPrevNext: true,
            changeFirstDay: false,
            closeAtTop: false,
            onSelect: refreshTime
        });
        $("#time_start, #time_end").change(refreshTime);
        $("#setup").submit(verifyFormSetup);
    }
});