var timer, timeleft;
function timeDisplay(){
    if(timeleft > 0){
        $("#status_timeleft").text(formatTime(timeleft));
        timeleft--;
    }
    else{
        $("#status_timeleft").text("None");
        clearInterval(timer);
    }
}
function restartServer() {
    disableAll(true);
    $("#restartstatus").fadeIn("slow");
    $.get("cp", {
        mode: 'restart'
    }, function(data){
        if(data != 'done') {
            alert('Error: Server restart failed!');
        }
        refreshData();
        disableAll(false);
        $("#restartstatus").fadeOut("slow");
    });
    return false;
}
function gameSwitch() {
    var game = $("#game").val();
    if(game.indexOf("--Select Game--") == 0) {
        return false;
    }
    disableAll(true);
    $("#gameswitchstatus").fadeIn("slow");
    $.get("cp", {
        mode: 'switch',
        game: game
    }, function(data){
        if(data != 'done') {
            alert('Error: Game switch failed!');
        }
        window.location.reload();
    });
    return false;
}
function configServer() {
    disableAll(true);
    $("#configstatus").fadeIn("slow");
    $.get("cp", $("#serverconfig input"), function(data){
        if(data != 'done') {
            alert('Error: Server config change failed!');
        }
        $("#configstatus").fadeOut("slow");
        refreshConfig();
        disableAll(false);
    });
    return false;
}
function configServerRaw() {
    disableAll(true);
    $("#configstatus2").fadeIn("slow");
    $.get("cp", {
        mode: 'config',
        raw: 'raw',
        config: $("#config").val()
    }, function(data){
        if(data != 'done') {
            alert('Error: Server config change failed!');
        }
        $("#configstatus2").fadeOut("slow");
        refreshConfig();
        disableAll(false);
    });
    return false;
}
function setMapcycle() {
    disableAll(true);
    $("#cyclestatus").fadeIn("slow");
    $.get("cp", {
        mode: 'mapcycle',
        mapcycle: $("#mapcycle").val()
    }, function(data){
        if(data != 'done') {
            alert('Error: Server config change failed!');
        }
        $("#cyclestatus").fadeOut("slow");
        disableAll(false);
    });
    return false;
}
function disableAll(b) {
    if(b) $("input,select,button,textarea").attr("disabled", "disabled");
    else $("input,select,button,textarea").removeAttr("disabled");
}
function refreshData() {
    $.getJSON("cp", {
        mode: 'info'
    }, function(data){
        $.each(data.items, function(i,item){
            $("#status_" + item.field).text(item.value).show("fast");
        });
    });
    return false;
}
function refreshConfig() {
    $.getJSON("cp", {
        mode: 'getcfg'
    }, function(data){
        $("#config").val(data.raw);
        $.each(data.array, function(i,item){
            if($("#scfg #" + i).length > 0) {
                $("#scfg #" + i).val(item);
            }
            else {
                $("#configserver").before('<label for="' + i + '" class="float">' + i + ' </label><input type="text" name="' + i + '" id="' + i + '" value="' + item + '" /><br />');
            }
        });
    });
    return false;
}
$(document).ready(function(){
    $.get("cp", {
        mode: 'time'
    }, function(data){
        timeleft = data;
        timer = setInterval("timeDisplay()", 1000);
    });
    $("#serverconfig").submit(configServer);
    $("#serverconfig2").submit(configServerRaw);
    $("#cycle").submit(setMapcycle);
    $("#serverrestart").submit(restartServer);
    $("#cvardialog").dialog({
        bgiframe: true,
        autoOpen: false,
        height: 160,
        width: 250,
        draggable: true,
        modal: true,
        buttons: {
            'Add Cvar': function() {
                var cvar = $("#cvar").val();
                var value = $("#value").val();
                if(cvar.length > 0) {
                    $("#cvar, #value").val('').blur();
                    $("#configserver").before('<label for="' + cvar + '" class="float">' + cvar + ' </label><input type="text" name="' + cvar + '" id="' + cvar + '" value="' + value + '" /><br />');
                }
                $(this).dialog('close');
            },
            'Cancel': function() {
                $(this).dialog('close');
            }
        }
    });
    $("#addcvar").click(function () {
        $("#cvardialog").dialog('open');
        return false;
    });
    $("#scfg ul, #scfgsimple, #mcycle").removeClass('hidden');
    $("#scfg").tabs({
        cookie: {
            expires: 30
        }
    });
$("#mcdialog").dialog({
    bgiframe: true,
    autoOpen: false,
    height: 400,
    width: 500,
    draggable: true,
    modal: true,
    buttons: {
        'Confirm': function() {
            var list = '';
            $("#mcbuilder option:selected").each(function() {
                list += $(this).text() + '\n';
            });
            $("#mapcycle").val(list);
            $(this).dialog('close');
            setMapcycle();
        },
        'Cancel': function() {
            $(this).dialog('close');
        }
    }
});
$("#openbuilder").click(function () {
    $("#mcdialog").dialog('open');
    return false;
});
$("#mcbuilder").multiselect();
    $("#switchdialog").dialog({
        bgiframe: true,
        autoOpen: false,
        height: 100,
        width: 300,
        modal: true,
        buttons: {
            'Confirm Game Switch': function() {
                gameSwitch();
                $(this).dialog('close');
            },
            'Cancel': function() {
                $(this).dialog('close');
            }
        }
    });
    $("#servergame").submit(function () {
        $("#switchdialog").dialog('open');
        return false;
    });
});