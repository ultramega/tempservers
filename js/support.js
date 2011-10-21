
$(document).ready(function(){$("#newthread").submit(function(){if($("#category").val()=='--Select Category--'||$("#title").val().length<1||$("#message").val().length<1){$("#error").text('Please fill in all required fields.').removeClass('hidden');return false;}
return true;});$("#reply").submit(function(){if($("#message").val().length<1){$("#error").text('Please fill in all required fields.').removeClass('hidden');return false;}
return true;});});