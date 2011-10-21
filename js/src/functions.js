function userLogin() {
    $.post('user', {
        mode: 'login',
        user: $('#user').val(),
        pass: $('#pass').val()
    }, function(data) {
        if(data == 0) {
            window.location = 'setup';
        }
        else {
            $("#loginerror").removeClass('hidden');
        }
    });
}
function userRegister() {
    if(verifyFormRegister()) {
        $.post('user', {
            mode: 'register',
            user: $('#username').val(),
            email: $('#email').val(),
            pass: $('#password').val(),
            tz: $('#tz').val(),
            code: $('#captcha').val()
        }, function(data) {
            if(data == 0) {
                window.location = 'setup';
            }
            else {
                window.location = 'register';
            }
        });
    }
}
function guessTZ() {
    var isdst;
    var rightNow = new Date();
    var current_offset = rightNow.getTimezoneOffset()*60;
    var jan1 = new Date(rightNow.getFullYear(),0, 1, 0, 0, 0, 0);
    var temp = jan1.toGMTString();
    var jan2 = new Date(temp.substring(0, temp.lastIndexOf(' ')-1));
    var std_time_offset = (jan1-jan2)/1000;
    var june1 = new Date(rightNow.getFullYear(), 6, 1, 0, 0, 0, 0);
    temp = june1.toGMTString();
    var june2 = new Date(temp.substring(0, temp.lastIndexOf(' ')-1));
    var daylight_time_offset = (june1-june2)/1000;
    if(std_time_offset == daylight_time_offset) {
        isdst = 0;
    }
    else {
        isdst = 1;
    }
    $.post('user', {
        mode: 'tz',
        offset: current_offset,
        dst: isdst
    }, function(data) {
        if(data.length > 0) {
            $("#tz > option[value=" + data + "]:first").attr("selected", "selected");
        }
    });
}
function checkUser() {
    if($(this).val() != '') {
        if($(this).val().length <= 2) {
            $("#namestatus").text('Too Short');
            $("#namestatus").css('color', '#000000');
        }
        else {
            $.post('user', {
                mode: "checkuser",
                username: $(this).val()
            }, function(data) {
                if(data > 0) {
                    $("#namestatus").text('Not Available');
                    $("#namestatus").css('color', '#CC0000');
                }
                else {
                    $("#namestatus").text('Available!');
                    $("#namestatus").css('color', '#00CC00');
                }
            });
        }
    }
}
function checkEmail() {
    if($(this).val() != '') {
        $.post('user', {
            mode: "checkemail",
            email: $(this).val()
        }, function(data) {
            if(data == 1) {
                $("#emailstatus").text('Invalid Email');
                $("#emailstatus").css('color', '#CC0000');
            }
            else if(data == 2) {
                $("#emailstatus").text('Already Registered');
                $("#emailstatus").css('color', '#CC0000');
            }
            else {
                $("#emailstatus").text('Valid!');
                $("#emailstatus").css('color', '#00CC00');
            }
        });
    }
}
function checkPass() {
    var pass = $("#password").val();
    if($(this).val() != pass) {
        $("#pwstatus").text('Password does not match!');
        $("#pwstatus").css('color', '#CC0000');
    }
    else {
        $("#pwstatus").text('');
    }
}
function reloadCaptcha() {
    var date = new Date();
    $("#captchaimg > img").attr('src', 'captcha.gif?' + date.getTime());
    return false;
}
function verifyFormRegister() {
    var error = 0;
    $("#register input").removeClass("focus");
    $("#register input").each(function(i) {
        if(this.value == '') {
            $(this).addClass("focus");
            error = 1;
        }
    });
    if(error == 0) {
        if($("#username").val().length <= 2 || $("#password").val().length <= 2) {
            error = 2;
        }
        else if($("#password").val() != $("#password2").val()) {
            error = 3;
        }
        else if($("#namestatus").text() == "Not Available") {
            error = 4;
        }
        else if($("#emailstatus").text() == "Invalid Email") {
            error = 5;
        }
        else if($("#emailstatus").text() == "Already Registered") {
            error = 6;
        }
        else {
            $.ajax({
                url: 'user',
                type: 'POST',
                async: false,
                data: {
                    mode: "captcha",
                    code: $("#captcha").val()
                },
                success: function(data) {
                    if(data != 0) {
                        error = 7;
                    }
                }
            });
    }
}
if(error > 0) {
    switch(error) {
        case 1:
            $("#registererror").text('Please fill in all required fields.');
            break;
        case 2:
            $("#registererror").text('Error: User Name and Password must be longer than 2 characters.');
            $("#username, #password").addClass("focus");
            break;
        case 3:
            $("#registererror").text('Error: Password does not match Confirm Password.');
            $("#password, #password2").addClass("focus");
            break;
        case 4:
            $("#registererror").text('Error: That username has already been taken. Please try another.');
            $("#username").addClass("focus");
            break;
        case 5:
            $("#registererror").text('Error: The Email Address appears invalid.');
            $("#email").addClass("focus");
            break;
        case 6:
            $("#registererror").text('Error: The Email Address is already registered.');
            $("#email").addClass("focus");
            break;
        case 7:
            $("#registererror").text('Error: Incorrect image validation code entered.');
            $("#captcha").addClass("focus");
            break;
    }
    $("#registererror").removeClass('hidden');
    reloadCaptcha();
    return false;
}
return true;
}
function formatTime(secs){
    var times = new Array(3600, 60, 1);
    var time = '';
    var tmp;
    for(var i = 0; i < times.length; i++){
        tmp = Math.floor(secs / times[i]);
        if(tmp < 1){
            tmp = '00';
        }
        else if(tmp < 10){
            tmp = '0' + tmp;
        }
        time += tmp;
        if(i < 2){
            time += ':';
        }
        secs = secs % times[i];
    }
    return time;
}