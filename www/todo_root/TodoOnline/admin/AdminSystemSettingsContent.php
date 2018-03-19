<?php

	$systemSettingTeamTrialPeriodDateInterval = TDOUtil::getStringSystemSetting('SYSTEM_SETTING_TEAM_TRIAL_PERIOD_DATE_INTERVAL', DEFAULT_SYSTEM_SETTING_TEAM_TRIAL_PERIOD_DATE_INTERVAL);
	$systemSettingTeamIAPCancellationReminderDateInterval = TDOUtil::getStringSystemSetting('SYSTEM_SETTING_TEAM_IAP_CANCELLATION_REMINDER_DATE_INTERVAL', DEFAULT_SYSTEM_SETTING_TEAM_IAP_CANCELLATION_REMINDER_DATE_INTERVAL);
	$systemSettingTeamMonthlyPricePerUser = TDOUtil::getStringSystemSetting('SYSTEM_SETTING_TEAM_MONTHLY_PRICE_PER_USER', DEFAULT_SYSTEM_SETTING_TEAM_MONTHLY_PRICE_PER_USER);
	$systemSettingTeamYearlyPricePerUser = TDOUtil::getStringSystemSetting('SYSTEM_SETTING_TEAM_YEARLY_PRICE_PER_USER', DEFAULT_SYSTEM_SETTING_TEAM_YEARLY_PRICE_PER_USER);
	$systemSettingTeamExpirationGracePeriodDateInterval = TDOUtil::getStringSystemSetting('SYSTEM_SETTING_TEAM_EXPIRATION_GRACE_PERIOD_DATE_INTERVAL', DEFAULT_SYSTEM_SETTING_TEAM_EXPIRATION_GRACE_PERIOD_DATE_INTERVAL);
	$systemSettingDiscoveryAnswers = TDOUtil::getStringSystemSetting('SYSTEM_SETTING_DISCOVERY_ANSWERS', DEFAULT_SYSTEM_SETTING_DISCOVERY_ANSWERS);
	$systemSettingTeamGrandfatherDate = TDOUtil::getStringSystemSetting('SYSTEM_SETTING_TEAM_GRANDFATHER_DATE', DEFAULT_SYSTEM_SETTING_TEAM_GRANDFATHER_DATE);
	$systemSettingTeamGrandfatherMonthlyPrice = TDOUtil::getStringSystemSetting('SYSTEM_SETTING_TEAM_GRANDFATHER_MONTHLY_PRICE', DEFAULT_SYSTEM_SETTING_TEAM_GRANDFATHER_MONTHLY_PRICE);
	$systemSettingTeamGrandfatherYearlyPrice = TDOUtil::getStringSystemSetting('SYSTEM_SETTING_TEAM_GRANDFATHER_YEARLY_PRICE', DEFAULT_SYSTEM_SETTING_TEAM_GRANDFATHER_YEARLY_PRICE);
    $systemSettingTeamSlackIntegrationForInternalUseOnly = TDOUtil::getStringSystemSetting('SYSTEM_SETTING_SLACK_ENABLED_FOR_INTERNAL_USE_ONLY', SYSTEM_SETTING_SLACK_ENABLED_FOR_INTERNAL_USE_ONLY);
	$systemSettingTeamSlackIntegrationForInternalUseOnlyTeamId = TDOUtil::getStringSystemSetting('SYSTEM_SETTING_SLACK_ENABLED_FOR_TEAM_TEAM_ID', SYSTEM_SETTING_SLACK_ENABLED_FOR_TEAM_TEAM_ID);

	$systemSettingSubscriptionLeadTimeInSeconds = TDOUtil::getStringSystemSetting('SYSTEM_SETTING_SUBSCRIPTION_LEAD_TIME_IN_SECONDS', DEFAULT_SYSTEM_SETTING_SUBSCRIPTION_LEAD_TIME_IN_SECONDS);
	$systemSettingSubscriptionAutorenewSleepInterval = TDOUtil::getStringSystemSetting('SYSTEM_SETTING_SUBSCRIPTION_AUTORENEW_SLEEP_INTERVAL_IN_SECONDS', DEFAULT_SYSTEM_SETTING_SUBSCRIPTION_AUTORENEW_SLEEP_INTERVAL_IN_SECONDS);
	$systemSettingSubscriptionMonthlyDateInterval = TDOUtil::getStringSystemSetting('SYSTEM_SETTING_SUBSCRIPTION_MONTHLY_DATE_INTERVAL', DEFAULT_SYSTEM_SETTING_SUBSCRIPTION_MONTHLY_DATE_INTERVAL);
	$systemSettingSubscriptionYearlyDateInterval = TDOUtil::getStringSystemSetting('SYSTEM_SETTING_SUBSCRIPTION_YEARLY_DATE_INTERVAL', DEFAULT_SYSTEM_SETTING_SUBSCRIPTION_YEARLY_DATE_INTERVAL);
	$systemSettingSubscriptionTrialDateInterval = TDOUtil::getStringSystemSetting('SYSTEM_SETTING_SUBSCRIPTION_TRIAL_DATE_INTERVAL', DEFAULT_SYSTEM_SETTING_SUBSCRIPTION_TRIAL_DATE_INTERVAL);

	$systemSettingSubscriptionMonthlyDurationInSeconds = TDOUtil::getStringSystemSetting('SubscriptionMonthlyDurationInSeconds', 86400 * 31)
	$systemSettingSubscriptionYearlyDurationInSeconds = TDOUtil::getStringSystemSetting('SubscriptionYearlyDurationInSeconds', 86400 * 365)
	$systemSettingSubscriptionTrialDurationInSeconds = TDOUtil::getStringSystemSetting('SubscriptionTrialDurationInSeconds', 86400 * 14)
?>


<h2>Todo Cloud System Settings</h2>

<table border="0" style="margin-left:20px;">
<tr>
	<td valign="bottom" colspan="2"><div style="font-weight:bold;">Team Trial Period (<a href="http://php.net/manual/en/dateinterval.construct.php" target="_new">PHP DateInterval Format</a>):</div></td>
</tr>
<tr>
	<td valign="middle"><div><input type="text" id="SYSTEM_SETTING_TEAM_TRIAL_PERIOD_DATE_INTERVAL" style="width:240px;margin-top:10px;" value="<?php echo $systemSettingTeamTrialPeriodDateInterval; ?>"/></div></td>
	<td valign="middle"><div class="button" id="button_SYSTEM_SETTING_TEAM_TRIAL_PERIOD_DATE_INTERVAL"style="margin-top:16px;" onclick="updateSystemSetting('SYSTEM_SETTING_TEAM_TRIAL_PERIOD_DATE_INTERVAL');">Update</div></td>
	<td valign="middle"><div id="status_SYSTEM_SETTING_TEAM_TRIAL_PERIOD_DATE_INTERVAL" style="visibility:hidden;">n/a</div></td>
</tr>

<tr>
	<td valign="bottom" colspan="2"><div style="font-weight:bold;">Team IAP Cancellation Email Reminder (<a href="http://php.net/manual/en/dateinterval.construct.php" target="_new">PHP DateInterval Format</a>):</div></td>
</tr>
<tr>
	<td valign="middle"><div><input type="text" id="SYSTEM_SETTING_TEAM_IAP_CANCELLATION_REMINDER_DATE_INTERVAL" style="width:240px;margin-top:10px;" value="<?php echo $systemSettingTeamIAPCancellationReminderDateInterval; ?>"/></div></td>
	<td valign="middle"><div class="button" id="button_SYSTEM_SETTING_TEAM_IAP_CANCELLATION_REMINDER_DATE_INTERVAL"style="margin-top:16px;" onclick="updateSystemSetting('SYSTEM_SETTING_TEAM_IAP_CANCELLATION_REMINDER_DATE_INTERVAL');">Update</div></td>
	<td valign="middle"><div id="status_SYSTEM_SETTING_TEAM_IAP_CANCELLATION_REMINDER_DATE_INTERVAL" style="visibility:hidden;">n/a</div></td>
</tr>

<tr>
	<td valign="bottom" colspan="2"><div style="font-weight:bold;">Team Monthly Price (per user):</div></td>
</tr>
<tr>
	<td valign="middle"><div><input type="text" id="SYSTEM_SETTING_TEAM_MONTHLY_PRICE_PER_USER" style="width:240px;margin-top:10px;" value="<?php echo $systemSettingTeamMonthlyPricePerUser; ?>"/></div></td>
	<td valign="middle"><div class="button" id="button_SYSTEM_SETTING_TEAM_MONTHLY_PRICE_PER_USER"style="margin-top:16px;" onclick="updateSystemSetting('SYSTEM_SETTING_TEAM_MONTHLY_PRICE_PER_USER');">Update</div></td>
	<td valign="middle"><div id="status_SYSTEM_SETTING_TEAM_MONTHLY_PRICE_PER_USER" style="visibility:hidden;">n/a</div></td>
</tr>

<tr>
	<td valign="bottom" colspan="2"><div style="font-weight:bold;">Team Yearly Price (per user):</div></td>
</tr>
<tr>
	<td valign="middle"><div><input type="text" id="SYSTEM_SETTING_TEAM_YEARLY_PRICE_PER_USER" style="width:240px;margin-top:10px;" value="<?php echo $systemSettingTeamYearlyPricePerUser; ?>"/></div></td>
	<td valign="middle"><div class="button" id="button_SYSTEM_SETTING_TEAM_YEARLY_PRICE_PER_USER"style="margin-top:16px;" onclick="updateSystemSetting('SYSTEM_SETTING_TEAM_YEARLY_PRICE_PER_USER');">Update</div></td>
	<td valign="middle"><div id="status_SYSTEM_SETTING_TEAM_YEARLY_PRICE_PER_USER" style="visibility:hidden;">n/a</div></td>
</tr>

<tr>
	<td valign="bottom" colspan="2"><div style="font-weight:bold;">Team Expiration Grace Period (<a href="http://php.net/manual/en/dateinterval.construct.php" target="_new">PHP DateInterval Format</a>):</div></td>
</tr>
<tr>
	<td valign="middle"><div><input type="text" id="SYSTEM_SETTING_TEAM_EXPIRATION_GRACE_PERIOD_DATE_INTERVAL" style="width:240px;margin-top:10px;" value="<?php echo $systemSettingTeamExpirationGracePeriodDateInterval ?>"/></div></td>
	<td valign="middle"><div class="button" id="button_SYSTEM_SETTING_TEAM_EXPIRATION_GRACE_PERIOD_DATE_INTERVAL"style="margin-top:16px;" onclick="updateSystemSetting('SYSTEM_SETTING_TEAM_EXPIRATION_GRACE_PERIOD_DATE_INTERVAL');">Update</div></td>
	<td valign="middle"><div id="status_SYSTEM_SETTING_TEAM_EXPIRATION_GRACE_PERIOD_DATE_INTERVAL" style="visibility:hidden;">n/a</div></td>
</tr>

<tr>
	<td valign="bottom" colspan="2"><div style="font-weight:bold;">How did you find us answers (shown when creating a new team -- comma separated answers):</div></td>
</tr>
<tr>
	<td valign="middle"><div><input type="text" id="SYSTEM_SETTING_DISCOVERY_ANSWERS" style="width:240px;margin-top:10px;" value="<?php echo $systemSettingDiscoveryAnswers ?>"/></div></td>
	<td valign="middle"><div class="button" id="button_SYSTEM_SETTING_DISCOVERY_ANSWERS"style="margin-top:16px;" onclick="updateSystemSetting('SYSTEM_SETTING_DISCOVERY_ANSWERS');">Update</div></td>
	<td valign="middle"><div id="status_SYSTEM_SETTING_DISCOVERY_ANSWERS" style="visibility:hidden;">n/a</div></td>
</tr>

<tr>
	<td valign="bottom" colspan="2"><div style="font-weight:bold;">Teaming Grandfather cut-off date (ISO8601 format):</div></td>
</tr>
<tr>
	<td valign="middle"><div><input type="text" id="SYSTEM_SETTING_TEAM_GRANDFATHER_DATE" style="width:240px;margin-top:10px;" value="<?php echo $systemSettingTeamGrandfatherDate ?>"/></div></td>
	<td valign="middle"><div class="button" id="button_SYSTEM_SETTING_TEAM_GRANDFATHER_DATE"style="margin-top:16px;" onclick="updateSystemSetting('SYSTEM_SETTING_TEAM_GRANDFATHER_DATE');">Update</div></td>
	<td valign="middle"><div id="status_SYSTEM_SETTING_TEAM_GRANDFATHER_DATE" style="visibility:hidden;">n/a</div></td>
</tr>

<tr>
	<td valign="bottom" colspan="2"><div style="font-weight:bold;">Teaming Grandfather Monthly Price (in USD per user):</div></td>
</tr>
<tr>
	<td valign="middle"><div><input type="text" id="SYSTEM_SETTING_TEAM_GRANDFATHER_MONTHLY_PRICE" style="width:240px;margin-top:10px;" value="<?php echo $systemSettingTeamGrandfatherMonthlyPrice ?>"/></div></td>
	<td valign="middle"><div class="button" id="button_SYSTEM_SETTING_TEAM_GRANDFATHER_MONTHLY_PRICE"style="margin-top:16px;" onclick="updateSystemSetting('SYSTEM_SETTING_TEAM_GRANDFATHER_MONTHLY_PRICE');">Update</div></td>
	<td valign="middle"><div id="status_SYSTEM_SETTING_TEAM_GRANDFATHER_MONTHLY_PRICE" style="visibility:hidden;">n/a</div></td>
</tr>

<tr>
	<td valign="bottom" colspan="2"><div style="font-weight:bold;">Teaming Grandfather Yearly Price (in USD per user):</div></td>
</tr>
<tr>
	<td valign="middle"><div><input type="text" id="SYSTEM_SETTING_TEAM_GRANDFATHER_YEARLY_PRICE" style="width:240px;margin-top:10px;" value="<?php echo $systemSettingTeamGrandfatherYearlyPrice; ?>"/></div></td>
	<td valign="middle"><div class="button" id="button_SYSTEM_SETTING_TEAM_GRANDFATHER_YEARLY_PRICE"style="margin-top:16px;" onclick="updateSystemSetting('SYSTEM_SETTING_TEAM_GRANDFATHER_YEARLY_PRICE');">Update</div></td>
	<td valign="middle"><div id="status_SYSTEM_SETTING_TEAM_GRANDFATHER_YEARLY_PRICE" style="visibility:hidden;">n/a</div></td>
</tr>
<tr>
	<td valign="bottom" colspan="2"><div style="font-weight:bold;">Enable Slack integration for internal use only:</div></td>
</tr>
<tr>
	<td valign="middle"><div><input type="checkbox" id="SYSTEM_SETTING_SLACK_ENABLED_FOR_INTERNAL_USE_ONLY" style="margin-top:10px;" value="<?php echo ($systemSettingTeamSlackIntegrationForInternalUseOnly) ?>" <?php echo ($systemSettingTeamSlackIntegrationForInternalUseOnly == 'true') ? 'checked' : ''; ?>/></div></td>
	<td valign="middle"><div class="button" id="button_SYSTEM_SETTING_SLACK_ENABLED_FOR_INTERNAL_USE_ONLY"style="margin-top:16px;" onclick="updateSystemSetting('SYSTEM_SETTING_SLACK_ENABLED_FOR_INTERNAL_USE_ONLY');">Update</div></td>
	<td valign="middle"><div id="status_SYSTEM_SETTING_SLACK_ENABLED_FOR_INTERNAL_USE_ONLY" style="visibility:hidden;">n/a</div></td>
</tr>
<tr>
	<td valign="bottom" colspan="2"><div style="font-weight:bold;">Enable Slack integration for internal Team Id:</div></td>
</tr>
    <tr>
        <td valign="middle"><div><input type="text" id="SYSTEM_SETTING_SLACK_ENABLED_FOR_TEAM_TEAM_ID" style="width:240px;margin-top:10px;" value="<?php echo $systemSettingTeamSlackIntegrationForInternalUseOnlyTeamId; ?>"/></div></td>
        <td valign="middle"><div class="button" id="button_SYSTEM_SETTING_SLACK_ENABLED_FOR_TEAM_TEAM_ID"style="margin-top:16px;" onclick="updateSystemSetting('SYSTEM_SETTING_SLACK_ENABLED_FOR_TEAM_TEAM_ID');">Update</div></td>
        <td valign="middle"><div id="status_SYSTEM_SETTING_SLACK_ENABLED_FOR_TEAM_TEAM_ID" style="visibility:hidden;">n/a</div></td>
    </tr>

<tr>
	<td valign="bottom" colspan="2"><div style="font-weight:bold;">&nbsp;</div></td>
</tr>
<tr>
	<td valign="bottom" colspan="2"><div style="font-weight:bold;">&nbsp;</div></td>
</tr>
<tr>
	<td valign="bottom" colspan="2"><div style="font-weight:bold;">&nbsp;</div></td>
</tr>
<tr>
	<td valign="bottom" colspan="2"><div style="font-weight:bold;"><hr/></div></td>
</tr>
<tr>
	<td valign="bottom" colspan="2"><div style="font-weight:bold;">&nbsp;</div></td>
</tr>
<tr>
	<td valign="bottom" colspan="2"><div style="font-weight:bold;">IMPORTANT: DO NOT CHANGE anything below this line in a production environment unless you absolutely know what you are doing. These items are really only meant for testing.</div></td>
</tr>
<tr>
	<td valign="bottom" colspan="2"><div style="font-weight:bold;">&nbsp;</div></td>
</tr>
<tr>
	<td valign="bottom" colspan="2"><div style="font-weight:bold;"><hr/></div></td>
</tr>


<tr>
	<td valign="bottom" colspan="2"><div style="font-weight:bold;">Subscription Lead Time (in seconds):</div></td>
</tr>
<tr>
	<td valign="middle"><div><input type="text" id="SYSTEM_SETTING_SUBSCRIPTION_LEAD_TIME_IN_SECONDS" style="width:240px;margin-top:10px;" value="<?php echo $systemSettingSubscriptionLeadTimeInSeconds ?>"/></div></td>
	<td valign="middle"><div class="button" id="button_SYSTEM_SETTING_SUBSCRIPTION_LEAD_TIME_IN_SECONDS"style="margin-top:16px;" onclick="updateSystemSetting('SYSTEM_SETTING_SUBSCRIPTION_LEAD_TIME_IN_SECONDS');">Update</div></td>
	<td valign="middle"><div id="status_SYSTEM_SETTING_SUBSCRIPTION_LEAD_TIME_IN_SECONDS" style="visibility:hidden;">n/a</div></td>
</tr>

<tr>
	<td valign="bottom" colspan="2"><div style="font-weight:bold;">Subscription Daemon Processing Interval (in seconds):</div></td>
</tr>
<tr>
	<td valign="middle"><div><input type="text" id="SYSTEM_SETTING_SUBSCRIPTION_AUTORENEW_SLEEP_INTERVAL_IN_SECONDS" style="width:240px;margin-top:10px;" value="<?php echo $systemSettingSubscriptionAutorenewSleepInterval ?>"/></div></td>
	<td valign="middle"><div class="button" id="button_SYSTEM_SETTING_SUBSCRIPTION_AUTORENEW_SLEEP_INTERVAL_IN_SECONDS"style="margin-top:16px;" onclick="updateSystemSetting('SYSTEM_SETTING_SUBSCRIPTION_AUTORENEW_SLEEP_INTERVAL_IN_SECONDS');">Update</div></td>
	<td valign="middle"><div id="status_SYSTEM_SETTING_SUBSCRIPTION_AUTORENEW_SLEEP_INTERVAL_IN_SECONDS" style="visibility:hidden;">n/a</div></td>
</tr>

<tr>
	<td valign="bottom" colspan="2"><div style="font-weight:bold;">Subscription Monthly Interval (<a href="http://php.net/manual/en/dateinterval.construct.php" target="_new">PHP DateInterval Format</a>):</div></td>
</tr>
<tr>
	<td valign="middle"><div><input type="text" id="SYSTEM_SETTING_SUBSCRIPTION_MONTHLY_DATE_INTERVAL" style="width:240px;margin-top:10px;" value="<?php echo $systemSettingSubscriptionMonthlyDateInterval ?>"/></div></td>
	<td valign="middle"><div class="button" id="button_SYSTEM_SETTING_SUBSCRIPTION_MONTHLY_DATE_INTERVAL"style="margin-top:16px;" onclick="updateSystemSetting('SYSTEM_SETTING_SUBSCRIPTION_MONTHLY_DATE_INTERVAL');">Update</div></td>
	<td valign="middle"><div id="status_SYSTEM_SETTING_SUBSCRIPTION_MONTHLY_DATE_INTERVAL" style="visibility:hidden;">n/a</div></td>
</tr>

<tr>
	<td valign="bottom" colspan="2"><div style="font-weight:bold;">Subscription Yearly Interval (<a href="http://php.net/manual/en/dateinterval.construct.php" target="_new">PHP DateInterval Format</a>):</div></td>
</tr>
<tr>
	<td valign="middle"><div><input type="text" id="SYSTEM_SETTING_SUBSCRIPTION_YEARLY_DATE_INTERVAL" style="width:240px;margin-top:10px;" value="<?php echo $systemSettingSubscriptionYearlyDateInterval ?>"/></div></td>
	<td valign="middle"><div class="button" id="button_SYSTEM_SETTING_SUBSCRIPTION_YEARLY_DATE_INTERVAL"style="margin-top:16px;" onclick="updateSystemSetting('SYSTEM_SETTING_SUBSCRIPTION_YEARLY_DATE_INTERVAL');">Update</div></td>
	<td valign="middle"><div id="status_SYSTEM_SETTING_SUBSCRIPTION_YEARLY_DATE_INTERVAL" style="visibility:hidden;">n/a</div></td>
</tr>

<tr>
	<td valign="bottom" colspan="2"><div style="font-weight:bold;">Subscription Trial Period Interval (<a href="http://php.net/manual/en/dateinterval.construct.php" target="_new">PHP DateInterval Format</a>):</div></td>
</tr>
<tr>
	<td valign="middle"><div><input type="text" id="SYSTEM_SETTING_SUBSCRIPTION_TRIAL_DATE_INTERVAL" style="width:240px;margin-top:10px;" value="<?php echo $systemSettingSubscriptionTrialDateInterval ?>"/></div></td>
	<td valign="middle"><div class="button" id="button_SYSTEM_SETTING_SUBSCRIPTION_TRIAL_DATE_INTERVAL"style="margin-top:16px;" onclick="updateSystemSetting('SYSTEM_SETTING_SUBSCRIPTION_TRIAL_DATE_INTERVAL');">Update</div></td>
	<td valign="middle"><div id="status_SYSTEM_SETTING_SUBSCRIPTION_TRIAL_DATE_INTERVAL" style="visibility:hidden;">n/a</div></td>
</tr>

<tr>
	<td valign="bottom" colspan="2"><div style="font-weight:bold;">Subscription Monthly Interval (xPlat):</div></td>
</tr>
<tr>
	<td valign="middle"><div><input type="text" id="SubscriptionMonthlyDurationInSeconds" style="width:240px;margin-top:10px;" value="<?php echo $systemSettingSubscriptionMonthlyDurationInSeconds ?>"/></div></td>
	<td valign="middle"><div class="button" id="button_SubscriptionMonthlyDurationInSeconds" style="margin-top:16px;" onclick="updateSystemSetting('SubscriptionMonthlyDurationInSeconds');">Update</div></td>
	<td valign="middle"><div id="status_SubscriptionMonthlyDurationInSeconds" style="visibility:hidden;">n/a</div></td>
</tr>

<tr>
	<td valign="bottom" colspan="2"><div style="font-weight:bold;">Subscription Yearly Interval (xPlat):</div></td>
</tr>
<tr>
	<td valign="middle"><div><input type="text" id="SubscriptionYearlyDurationInSeconds" style="width:240px;margin-top:10px;" value="<?php echo $systemSettingSubscriptionYearlyDurationInSeconds ?>"/></div></td>
	<td valign="middle"><div class="button" id="button_SubscriptionYearlyDurationInSeconds" style="margin-top:16px;" onclick="updateSystemSetting('SubscriptionYearlyDurationInSeconds');">Update</div></td>
	<td valign="middle"><div id="status_SubscriptionYearlyDurationInSeconds" style="visibility:hidden;">n/a</div></td>
</tr>

<tr>
	<td valign="bottom" colspan="2"><div style="font-weight:bold;">Subscription Trial Period Interval (xPlat):</div></td>
</tr>
<tr>
	<td valign="middle"><div><input type="text" id="SubscriptionTrialDurationInSeconds" style="width:240px;margin-top:10px;" value="<?php echo $systemSettingSubscriptionTrialDurationInSeconds ?>"/></div></td>
	<td valign="middle"><div class="button" id="button_SubscriptionTrialDurationInSeconds" style="margin-top:16px;" onclick="updateSystemSetting('SubscriptionTrialDurationInSeconds');">Update</div></td>
	<td valign="middle"><div id="status_SubscriptionTrialDurationInSeconds" style="visibility:hidden;">n/a</div></td>
</tr>

</table>

<script type="text/javascript">
<?php

    $adminLevel = TDOUser::adminLevel($session->getUserId());
    if($adminLevel >= ADMIN_LEVEL_ROOT)
        echo 'var adminIsRoot = true;';
    else
        echo 'var adminIsRoot = false;';

?>
</script>
