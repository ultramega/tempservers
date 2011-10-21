
function verifyFormAccount(){var error=0;$("#account input").removeClass("focus");if($("#email").val()!=$("#email").attr('defaultValue')||$("#npassword").val()!=''){if($("#password").val()==''){error=1;}
$.ajax({url:'user',type:'POST',async:false,data:{mode:"checkpass",pass:$("#password").val()},success:function(data){if(data!=0){error=2;}}});}
if(error==0){if($("#npassword").val()!=$("#npassword2").val()){error=3;}
else if($("#emailstatus").text()=="Invalid Email"){error=4;}
else if($("#emailstatus").text()=="Already Registered"){error=5;}}
if(error>0){switch(error){case 1:$("#error").text('Error: Password is required.');$("#password").addClass("focus");break;case 2:$("#error").text('Error: Incorrect password.');$("#password").addClass("focus");break;case 3:$("#error").text('Error: New Password does not match Confirm Password.');$("#npassword, #npassword2").addClass("focus");break;case 4:$("#error").text('Error: The Email Address appears invalid.');$("#email").addClass("focus");break;case 5:$("#error").text('Error: The Email Address is already registered.');$("#email").addClass("focus");break;}
$("#error").removeClass('hidden');return false;}
return true;}
$(document).ready(function(){$("#email").blur(checkEmail);$("#npassword2").blur(checkPass);$("#account").submit(verifyFormAccount);});