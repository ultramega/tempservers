$(document).ready(function() {
    $("#username").keyup(checkUser).blur(checkUser);
    $("#email").blur(checkEmail);
    $("#password2").blur(checkPass);
    $("#register").submit(verifyFormRegister);
    $("#reloadcaptcha").click(reloadCaptcha);
    guessTZ();
});