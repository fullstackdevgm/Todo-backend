<!--
<h1>Todo® Cloud - Apple Employee Promo</h1>
<br/>
<p>Thank you for creating incredible devices and software to help makeour company successful. Please enjoy a free Todo® Cloud Premium Account on us!</p>

<form action="?method=generateVIPPromo" method="POST">
	<input type="submit" value="Send me a promo code!"/>
</form>
-->

<style>
	.content_wrap {width:100%}
</style>
<div class="setting_options_container">
	<div style="border: 1px solid rgb(180, 180, 180);border-radius: 4px 4px 4px 4px;box-shadow: 0 0 2px 0 rgb(160, 160, 160);margin: 0 auto;max-width: 500px; padding: 20px;margin-top:20px;text-align: center;">
		<h2>Todo<span style="vertical-align:super;font-size:.8em;color:inherit;">®</span> <?php _e('Pro - Apple Employee Promo'); ?></h2>
		<div style="margin:30px auto"><?php _e('Thank you for creating incredible devices and software to help make our company successful. Please enjoy a free Todo® Cloud Premium Account on us!'); ?></div>
		<div class="button" id="send_promo_code_button"><?php _e('Send me a promo code!'); ?></div>
	</div>
</div>

<script>

window.addEventListener('load', setupAppleEmployeePage, false);

function setupAppleEmployeePage()
{
	var doc = document;
	
	doc.getElementById('send_promo_code_button').addEventListener('click', sendPromoCode, false);
};


function sendPromoCode(){
	var ajaxRequest = getAjaxRequest();
	if(!ajaxRequest)
		return false;

	ajaxRequest.onreadystatechange = function()
	{
		if(ajaxRequest.readyState == 4)
		{
            try
            {
                var response = JSON.parse(ajaxRequest.responseText);

                if(response.success)
                {
                	var doc = document;
                    var bodyHTML = '<p><?php _e('Check your email inbox and click the link we sent you'); ?></p>';
                    var headerHTML = '<?php _e('Promo Code Sent!'); ?>';
            		var footerHTML = '<div class="button " id="promo_code_sent_ok_button"><?php _e('Ok'); ?></div>';
            		
            		displayModalContainer(bodyHTML, headerHTML, footerHTML);
            		doc.getElementById('promo_code_sent_ok_button').addEventListener ('click', function(){
	            		hideModalContainer();
            		}, false);
                }
                else
                {
                    if(response.error == "authentication")
                    {
                        //log in again
                        history.go(0);
                    }
                    else
                    {
                        var bodyHTML = '<p>' + response.error + '</p>';
	            		var headerHTML = '<?php _e('Oops!'); ?>'';
	            		var footerHTML = '<div class="button" onclick="hideModalContainer()"><?php _e('Ok'); ?></div>';
	            		
	            		displayModalContainer(bodyHTML, headerHTML, footerHTML);
                    }
                }
            }
            catch(e)
            {
                alert("<?php _e('Unknown Response'); ?> " + e);
            }
		}
	}

	var params = 'method=generateVIPPromo';
    
	ajaxRequest.open("POST", ".", true);
	ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	ajaxRequest.send(params);
}



</script>
<script type="text/javascript" src="<?php echo TP_JS_PATH_UTIL_FUNCTIONS; ?>"></script>
<script type="text/javascript" src="<?php echo TP_JS_PATH_APP_SETTINGS_FUNCTIONS; ?>" ></script>