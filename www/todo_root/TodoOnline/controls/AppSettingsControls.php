<?php

	$showApplePromoOption = '';
	$settingOption = '';
    $userid = $session->getUserId();

    $teams = TDOTeamAccount::getAdministeredTeamsForAdmin($userid);
    $teamsCount = sizeof($teams);
    $teamid = '';
    if (is_array($teams) && sizeof($teams)) {
        $team = $teams[0];
    }
    if (isset($team) && $team) {
        $teamid = $team->getTeamID();
    }

	$isTeamMember = TDOTeamAccount::isTeamMember($userid);
    $is_member_of_team = false;
    if (!$teamsCount) {
        $is_member_of_team = TDOTeamAccount::getTeamForTeamMember($userid);
    }
	if(isset($_GET['option']))
	{
		$settingOption = $_GET['option'];
	}
    $settingAction = '';
	if(isset($_REQUEST['action']) &&  $_REQUEST['action'] !== '')
	{
		$settingAction = $_REQUEST['action'];
	}

//	if(TDOUtil::isCurrentUserInWhiteList($session))
//	{
//		if(isset($_GET['applepromo']))
//		{
//			if(TDOUtil::isCurrentUserInWhiteList($session))
//				$settingOption = 'applepromo';
//			else
//				$settingOption = 'general'; //default to general options
//		}
//
//		$showApplePromoOption = '1';
//
//	}
//	else
//	{
//		$showApplePromoOption = '';
//	}
//
	echo  '<input id="settingOption" type="hidden" value="'.$settingOption.'" />';
//	echo  '<input id="applepromo" type="hidden" value="'.$showApplePromoOption.'" />';

?>

<div class="control_container">
    <ul class="control_group">
      <!-- <li class="control_item">
    		<div class="group_title">Settings</div>
    	</li> -->
    	<span id="settingOptions"></span>
    </ul>
</div>

<script>
    var settingOption = document.getElementById('settingOption').value;
    var settingAction = '<?php echo $settingAction; ?>';
    var controlOptionsEl = document.getElementById('settingOptions');

    var generalSelected = '';
    var focusSelected = '';
    var taskCreationSelected = '';
    var notificationsSelected = '';
//    var messageCenterSelected = '';
    var accountSelected = '';
    var subscriptionSelected = '';
	var referralsSelected = '';
    var invitationSelected = '';
    var giftsSelected = '';
	var teamingSelected = '';
	var teamingCreateSelected = '';
	var teamingMembersSelected = '';
	var teamingListsSelected = '';
	var teamingIntegrationsSelected = '';
	var teamingBillingSelected = '';
	var showSupport = '';
    var boydSelected = '';
    var appleSelected = '';
    var controlsHTML = '';
    var teamingShowChild = '';

	var showAccountOptions = false;

    switch (settingOption)
    {
    	case "general":
    		generalSelected = ' selected_option';
    		break;
    	case "focus":
    		focusSelected = ' selected_option';
    		break;
        case "taskcreation":
            taskCreationSelected = ' selected_option';
            break;
    	case "notifications":
    		notificationsSelected = ' selected_option';
    		break;
//        case "messagecenter":
//            messageCenterSelected = ' selected_option';
//            break;
    	case "account":
			showAccountOptions = true;
    		accountSelected = ' selected_option';
    		break;
    	case "subscription":
			showAccountOptions = true;
    		subscriptionSelected = ' selected_option';
    		break;
		case "referrals":
			showAccountOptions = true;
			referralsSelected = ' selected_option';
			break;
    	case "invitations":
    		invitationSelected = ' selected_option';
    		break;
    	case "gifts":
			showAccountOptions = true;
    		giftsSelected = ' selected_option';
    		break;
		case "teaming":
            switch (settingAction) {
                case "createTeam":
                    teamingCreateSelected    = ' selected_option';
                    break;
                case "teaming_members":
                    teamingMembersSelected    = ' selected_option';
                    break;
                case "teaming_lists":
                case "teaming_list_members":
                    teamingListsSelected    = ' selected_option';
                    break;
                case "integrations":
                    teamingIntegrationsSelected    = ' selected_option';
                    break;
                case "teaming_billing":
                    teamingBillingSelected    = ' selected_option';
                    break;
                default:
			        teamingSelected = ' selected_option';
                    break;
            }
            teamingSelected += ' show-second-level-item';
            teamingCreateSelected += ' show-second-level-item';
            teamingMembersSelected += ' show-second-level-item';
            teamingListsSelected += ' show-second-level-item';
            teamingIntegrationsSelected += ' show-second-level-item';
            teamingBillingSelected += ' show-second-level-item';
            showSupport += ' show-second-level-item';

    	case "applepromo":
    		appleSelected = ' selected_option';
    		break;
    	default:
    		break;
    }

    //build control options
		controlsHTML += '<li class="group_option return-to-app">';
		controlsHTML += '	<a class="control_link" href=".">';
		controlsHTML += '		 <i class="fa fa-reply"></i>';
		controlsHTML += ' 		<span class="option_name"><?php _e('Return to Todo-Cloud'); ?></span>';
		controlsHTML += '	</a>';
		controlsHTML += '</li>';

    controlsHTML += '<li class="group_option ' + generalSelected + '">';
    controlsHTML += '	<a class="control_link" href="?appSettings=show&option=general">';
		controlsHTML += '		 <i class="fa fa-cogs ' + generalSelected + '"></i>';
    controlsHTML += ' 		<span class="option_name">' +  settingStrings.general + '</span>';
    controlsHTML += '	</a>';
    controlsHTML += '</li>';
    controlsHTML += '<li class="group_option ' + focusSelected + '">';
    controlsHTML += '	<a class="control_link" href="?appSettings=show&option=focus">';
		controlsHTML += '		<i class="fa fa-dot-circle-o ' + focusSelected + '"></i>';
    controlsHTML += '		<span class="option_name">' + settingStrings.focusList + '</span>';
    controlsHTML += '	</a>';
    controlsHTML += '</li>';
    controlsHTML += '<li class="group_option ' + taskCreationSelected + '">';
    controlsHTML += '	<a class="control_link" href="?appSettings=show&option=taskcreation">';
		controlsHTML += '		<i class="fa fa-check-circle-o ' + taskCreationSelected + '"></i>';
    controlsHTML += '		<span class="option_name">' + settingStrings.taskCreation + '</span>';
    controlsHTML += '	</a>';
    controlsHTML += '</li>';
    controlsHTML += '<li class="group_option ' + notificationsSelected + '">';
    controlsHTML += '	<a class="control_link" href="?appSettings=show&option=notifications">';
		controlsHTML += '		<i class="fa fa-envelope ' + notificationsSelected + '"></i>';
    controlsHTML += '		<span class="option_name">' + settingStrings.notifications + '</span>';
    controlsHTML += '	</a>';
    controlsHTML += '</li>';
//    controlsHTML += '<li class="group_option ' + messageCenterSelected + '">';
//    controlsHTML += '	<span class="option_left_icon announcements_icon ' + messageCenterSelected + '">	</span>';
//    controlsHTML += '	<a class="control_link mc_center" href="?appSettings=show&option=messagecenter">';
//    controlsHTML += '		<span class="option_name">';
//    controlsHTML +=             settingStrings.messageCenter + ' <span id="message_center_count_item" class="count"></span> ';
//    controlsHTML += '       </span>';
//    controlsHTML += '	</a>';
//    controlsHTML += '</li>';


	controlsHTML += '<li class="group_option">';
	// controlsHTML += '	<span class="option_left_icon account_settings_icon ">	</span>';
	controlsHTML += '	<a class="control_link" href="?appSettings=show&option=account">';
	controlsHTML += '		<i class="fa fa-user"></i>';
	controlsHTML += '	  <span class="option_name">' +  settingStrings.account + '</span>';
	controlsHTML += '	</a>';
	controlsHTML += '</li>';
    controlsHTML += '<li class="group_option account_option ' + accountSelected + '">';
//    controlsHTML += '	<span class="option_left_icon account_settings_icon ' + accountSelected + '">	</span>';
    controlsHTML += '	<a class="control_link" href="?appSettings=show&option=account">';
    controlsHTML += '	<span class="option_name">' +  settingStrings.accountDetails + '</span>';
    controlsHTML += '	</a>';
    controlsHTML += '</li>';

<?php
if (!$isTeamMember)
	{
?>

    controlsHTML += '<li class="group_option account_option ' + subscriptionSelected + '">';
//    controlsHTML += '	<span class="option_left_icon subscription_settings_icon ' + subscriptionSelected + '">	</span>';
    controlsHTML += '	<a class="control_link" href="?appSettings=show&option=subscription">';
    controlsHTML += '		<span class="option_name">' +  settingStrings.premiunAccount + '</span>';
    controlsHTML += '	</a>';
    controlsHTML += '</li>';
	controlsHTML += '<li class="group_option account_option ' + referralsSelected + '">';
//	controlsHTML += '	<span class="option_left_icon referrals_settings_icon ' + referralsSelected + '">	</span>';
	controlsHTML += '	<a class="control_link" href="?appSettings=show&option=referrals">';
	controlsHTML += '		<span class="option_name">' +  settingStrings.referrals + '</span>';
	controlsHTML += '	</a>';
	controlsHTML += '</li>';
    controlsHTML += '<li class="group_option account_option ' + giftsSelected + '">';
//    controlsHTML += '	<span class="option_left_icon gifts_settings_icon ' + giftsSelected + '">	</span>';
    controlsHTML += '	<a class="control_link" href="?appSettings=show&option=gifts">';
    controlsHTML += '		<span class="option_name"><?php _e('Gifting'); ?></span>';
    controlsHTML += '	</a>';
    controlsHTML += '</li>';

<?php
	}
?>

//    if (document.getElementById('applepromo').value == '1')
//    {
//	    controlsHTML += '<li class="group_option ' + appleSelected + '">';
//	    controlsHTML += '	<span class="option_left_icon gifts_settings_icon ' + appleSelected + '">	</span>';
//	    controlsHTML += '	<a class="control_link" href="?applepromo=1">';
//	    controlsHTML += '		<span class="option_name">Special Gift For You</span>';
//	    controlsHTML += '	</a>';
//	    controlsHTML += '</li>';
//    }

//
// UNCOMMENT THE FOLLOWING SECTION TO EXPOSE THE TEAMING FEATURE
//
	controlsHTML += '<li class="group_option ' + teamingShowChild + '">';
	controlsHTML += '	<a class="control_link" href="?appSettings=show&option=teaming<?php echo (!$teamsCount && !$is_member_of_team)?'&action=createTeam':''?>">';
	controlsHTML += '		<i class="fa fa-users"></i>';
	controlsHTML += '		<span class="option_name">' + settingStrings.teaming + '</span>';
	controlsHTML += '	</a>';
	controlsHTML += '</li>';
    <?php if(!$teamsCount && !$is_member_of_team) : ?>
    controlsHTML += '<li class="group_option team_option second-level' + teamingCreateSelected + '">';
    controlsHTML += '	<a class="control_link" href="?appSettings=show&option=teaming&action=createTeam">';
    controlsHTML += '		<span class="option_name">' +  settingStrings.teaming_create + '</span>';
    controlsHTML += '	</a>';
    controlsHTML += '</li>';
    <?php else :?>
    controlsHTML += '<li class="group_option team_option second-level' + teamingSelected + '">';
    controlsHTML += '	<a class="control_link" href="?appSettings=show&option=teaming">';
    controlsHTML += '		<span class="option_name">' +  settingStrings.teaming_overview + '</span>';
    controlsHTML += '	</a>';
    controlsHTML += '</li>';
    <?php if ($teamsCount) : ?>
    controlsHTML += '<li class="group_option team_option second-level' + teamingMembersSelected + '">';
    controlsHTML += '	<a class="control_link" href="?appSettings=show&option=teaming&action=teaming_members">';
    controlsHTML += '		<span class="option_name">' +  settingStrings.teaming_members + '</span>';
    controlsHTML += '	</a>';
    controlsHTML += '</li>';
    controlsHTML += '<li class="group_option team_option second-level' + teamingListsSelected + '">';
    controlsHTML += '	<a class="control_link" href="?appSettings=show&option=teaming&action=teaming_lists">';
    controlsHTML += '		<span class="option_name">' +  settingStrings.teaming_lists + '</span>';
    controlsHTML += '	</a>';
    controlsHTML += '</li>';
    <?php
        $systemSettingTeamSlackIntegrationForInternalUseOnly = TDOUtil::getStringSystemSetting('SYSTEM_SETTING_SLACK_ENABLED_FOR_INTERNAL_USE_ONLY', SYSTEM_SETTING_SLACK_ENABLED_FOR_INTERNAL_USE_ONLY);
        $systemSettingTeamSlackIntegrationForInternalUseOnlyTeamId = TDOUtil::getStringSystemSetting('SYSTEM_SETTING_SLACK_ENABLED_FOR_TEAM_TEAM_ID', SYSTEM_SETTING_SLACK_ENABLED_FOR_TEAM_TEAM_ID);
        $systemSettingTeamSlackIntegrationForInternalUseOnly = $systemSettingTeamSlackIntegrationForInternalUseOnly == 'true'? true: false;
        if ($systemSettingTeamSlackIntegrationForInternalUseOnly == false || $teamid == $systemSettingTeamSlackIntegrationForInternalUseOnlyTeamId): ?>
    controlsHTML += '<li class="group_option team_option second-level' + teamingIntegrationsSelected + '">';
    controlsHTML += '	<a class="control_link" href="?appSettings=show&option=teaming&action=integrations">';
    controlsHTML += '		<span class="option_name">' +  settingStrings.integrations + '</span>';
    controlsHTML += '	</a>';
    controlsHTML += '</li>';
    <?php endif; ?>
    controlsHTML += '<li class="group_option team_option second-level' + teamingBillingSelected + '">';
    controlsHTML += '	<a class="control_link" href="?appSettings=show&option=teaming&action=teaming_billing">';
    controlsHTML += '		<span class="option_name">' +  settingStrings.teaming_billing + '</span>';
    controlsHTML += '	</a>';
    controlsHTML += '</li>';
    <?php endif; ?>
    <?php endif; ?>

    controlsHTML += '<li class="group_option team_option second-level ' + showSupport + '">';
    controlsHTML += '	<a class="control_link" href="http://support.appigo.com/support/solutions/4000003811" target="_blank">';
    controlsHTML += '		<span class="option_name">' +  settingStrings.support + '</span>';
    controlsHTML += '	</a>';
    controlsHTML += '</li>';

    controlsHTML += '<li class="group_option">';
    <?php if (!$teamsCount && !$is_member_of_team) : ?>
        controlsHTML += '	<a class="control_link" href="mailto:support@appigo.com">';
    <?php else : ?>
        controlsHTML += '	<a class="control_link" href="mailto:support@appigo.com">';
    <?php endif; ?>
    controlsHTML += '		<i class="fa fa-question-circle"></i>';
    controlsHTML += '	  <span class="option_name">' +  settingStrings.email_support + '</span>';
    controlsHTML += '	</a>';
    controlsHTML += '</li>';

    controlOptionsEl.innerHTML = controlsHTML;

	if (showAccountOptions)
	{
		var accountOptions = document.getElementsByClassName("account_option");
		for (var i = 0; i < accountOptions.length; i++)
		{
			accountOptions[i].style.display = "block";
		}
	}
</script>
