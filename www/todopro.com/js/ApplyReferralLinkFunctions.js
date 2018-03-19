
// This modal is shown when the user first signs in after using a referral link
function displayReferralLinkSuccessModal()
{
    var header = labels.welcome_to_todo_cloud;
    var body = '';
    body += '<div style="width:400px;">';
    body += '	<p>' + labels.thank_you_for_choosing_todo_cloud + '</p>';
    body += '	<p>' + labels.thanks_to_your_friend + '</p>';
    body += '	<p>' + labels.get_even_more_time_added + '</p>';
	body += '</div>';
    var footer = '<div class="button" onclick="top.location=\'.\'">' + labels.ok + '</div>';
    
    displayModalContainer(body, header, footer);

    document.getElementById('modal_overlay').onclick = null;
}


function displayReferralLinkErrorModal(errorMessage)
{
    var header = labels.referral_link;
    var body = errorMessage;
    var footer = '<div class="button" onclick="top.location=\'.\'">' + labels.ok + '</div>';
    
    displayModalContainer(body, header, footer);
    document.getElementById('modal_overlay').onclick = null;
}


