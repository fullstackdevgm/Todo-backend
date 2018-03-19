<?php 
include('TodoOnline/ajax_config.html');
?>

<script type="text/javascript">
inferTimezone();

function inferTimezone()
{
    var ajaxRequest = getAjaxRequest();  // The variable that makes Ajax possible!
    if(!ajaxRequest)
    {
        return false;
    }

    // Create a function that will receive data sent from the server
    ajaxRequest.onreadystatechange = function()
    {
        if(ajaxRequest.readyState == 4)
        {
            //reload the page so we use the correct timezone
            
            //take out the facebook parameters because they cause problems
            var ref = window.location.href;
            ref = ref.replace('type=', 'asdf=');
            ref = ref.replace('state=', 'jkl=');
            window.location = ref;

        }
    }
    var visitortime = new Date();
    var offset = visitortime.getTimezoneOffset() * -60;

    var params = "method=setUserTimezone&timezone_offset=" + offset;

    ajaxRequest.open("POST", "." , true);
    //Send the proper header information along with the request
    ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    

    ajaxRequest.send(params);    
}
</script>