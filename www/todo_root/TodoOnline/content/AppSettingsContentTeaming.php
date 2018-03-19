<?php
	$userid = $session->getUserId();
	
	$systemSettingTeamMonthlyPricePerUser = TDOUtil::getStringSystemSetting('SYSTEM_SETTING_TEAM_MONTHLY_PRICE_PER_USER', DEFAULT_SYSTEM_SETTING_TEAM_MONTHLY_PRICE_PER_USER);
	$systemSettingTeamYearlyPricePerUser = TDOUtil::getStringSystemSetting('SYSTEM_SETTING_TEAM_YEARLY_PRICE_PER_USER', DEFAULT_SYSTEM_SETTING_TEAM_YEARLY_PRICE_PER_USER);
	
	// Get enough information so the Javascript knows what values to use for
	// monthly and yearly subscriptions.
	$systemSettingMonthlyIntervalSetting = TDOUtil::getStringSystemSetting('SYSTEM_SETTING_SUBSCRIPTION_MONTHLY_DATE_INTERVAL', DEFAULT_SYSTEM_SETTING_SUBSCRIPTION_MONTHLY_DATE_INTERVAL);
	$systemSettingYearlyIntervalSetting = TDOUtil::getStringSystemSetting('SYSTEM_SETTING_SUBSCRIPTION_YEARLY_DATE_INTERVAL', DEFAULT_SYSTEM_SETTING_SUBSCRIPTION_YEARLY_DATE_INTERVAL);
	
	$monthlyInterval = new DateInterval($systemSettingMonthlyIntervalSetting);
	$yearlyInterval = new DateInterval($systemSettingYearlyIntervalSetting);
	
	$nowTimestamp = time();
	
	$nowDate = new DateTime("now", new DateTimeZone("UTC"));
	$nowDate->add($monthlyInterval);
	$monthlySubscriptionTimeInSeconds = $nowDate->getTimestamp() - $nowTimestamp;
	
	$nowDate = new DateTime("now", new DateTimeZone("UTC"));
	$nowDate->add($yearlyInterval);
	$yearlySubscriptionTimeInSeconds = $nowDate->getTimestamp() - $nowTimestamp;
	
	$teamSubscriptionStatus = 0;
	$isTeamTrialPeriod = false;
	$isTeamActive = true;
	$isTeamGracePeriod = false;
	$isTeamExpired = false;
	$teams = TDOTeamAccount::getAdministeredTeamsForAdmin($userid);
	if(is_array($teams)){
		$team = $teams[0];
	}
	if ($team)
	{
		$teamid = $team->getTeamID();
		$teamSubscriptionStatus = TDOTeamAccount::getTeamSubscriptionStatus($teamid);
		
		if ($teamSubscriptionStatus == TEAM_SUBSCRIPTION_STATE_TRIAL_PERIOD)
		{
			$isTeamTrialPeriod = true;
		}
		else if ($teamSubscriptionStatus == TEAM_SUBSCRIPTION_STATE_GRACE_PERIOD)
		{
			$isTeamGracePeriod = true;
		}
		else if ($teamSubscriptionStatus == TEAM_SUBSCRIPTION_STATE_EXPIRED)
		{
			$isTeamActive = false;
			$isTeamExpired = true;
		}
		
		// If the team is a grandfathered team (created before we launched
		// Todo Cloud Web 2.4), they'll have the option of having the old
		// pricing.
		$isGrandfatheredTeam = TDOTeamAccount::isGrandfatheredTeam($teamid);
		$systemSettingTeamMonthlyPricePerUser = TDOTeamAccount::unitCostForBillingFrequency(SUBSCRIPTION_TYPE_MONTH, $isGrandfatheredTeam);
		$systemSettingTeamYearlyPricePerUser = TDOTeamAccount::unitCostForBillingFrequency(SUBSCRIPTION_TYPE_YEAR, $isGrandfatheredTeam);
	}
	
?>
<link rel="stylesheet" type="text/css" href="<?php echo TP_CSS_PATH_APP_SETTINGS; ?>" />
<script type="text/javascript" src="<?php echo TP_JS_PATH_UTIL_FUNCTIONS; ?>"></script>
<script type="text/javascript" src="<?php echo TP_JS_PATH_APP_SETTINGS_FUNCTIONS; ?>" ></script>

<script type="text/javascript">
var monthlyTeamPrice = <?php echo $systemSettingTeamMonthlyPricePerUser;?>;
var yearlyTeamPrice = <?php echo $systemSettingTeamYearlyPricePerUser;?>;
var monthlySubscriptionTimeInSeconds = <?php echo $monthlySubscriptionTimeInSeconds;?>;
var yearlySubscriptionTimeInSeconds = <?php echo $yearlySubscriptionTimeInSeconds;?>;

var isTeamTrialPeriod = <?php echo $isTeamTrialPeriod ? "true" : "false";?>;
var isTeamActive = <?php echo $isTeamActive ? "true" : "false";?>;
var isTeamGracePeriod = <?php echo $isTeamGracePeriod ? "true" : "false";?>;
var isTeamExpired = <?php echo $isTeamExpired ? "true" : "false";?>;

</script>
<script type="text/javascript" src="<?php echo TP_JS_PATH_TEAM_FUNCTIONS; ?>"></script>

<style>
.settings_inner_content { margin: 20px;}

.todo-for-business h1 {
    font-size: 16pt;
    margin-bottom: 20px;
    margin-top: 0;
    line-height: 1;
}
.todo-for-business h3 {
    font-size: 14pt;
    margin-bottom: 15px;
    line-height: 1;
    margin-top: 20px;
    font-weight: 300;

}
.team_create_input { display:inline-block; position: relative; margin-top: 10px; margin-right: 20px; }
.team_create_input_row { display:block;}
.team_create_pricing_detail_paragraph { margin-bottom: 10px; margin-top: 10px; text-align:center; color:#999999; }

.team_help_section
{
	margin-top:40px;
}

.team_tos_agree_field {
    padding: 10px 0;
}

.team-create-submit-form .team_tos_agree_field  {
    padding: 6px 0;
}




.width_full	{ width: 548px; }
.width_half	{ width: 260px; }
.width_third { width: 170px; }
.last{
    float: right;
}
.team_item_hidden { display:none !important; }
.team_price_warning {
	display:inline-block;
	background-color:#CCCCCC;
	font-size:12px;
    padding: 6px;
    line-height: 18px;
}
.team_price_warning a{
    color: #fff;
}

.enabled_purchase_button
{
	border: 1px solid #4A61C8;
	border-radius: 4px;
	border-width: 2px;
	box-shadow: none;
	padding: 20px 40px 35px 40px;
	font-size: 20px;
	height: 25px;
	width: 100%;
	color:#ffffff;
	cursor:pointer;
	display:inline-block;
	font-weight:600;
	text-align:center;
	background-color: #4A61C8;
	vertical-align:middle;
    box-sizing: border-box;
    -webkit-box-sizing: border-box;
    -moz-box-sizing: border-box;
}
.disabled_purchase_button
{
	border: 1px solid #CCCCCC;
	background-color:#CCCCCC;
	cursor:inherit;
	
	border-radius: 4px;
	border-width: 2px;
	box-shadow: none;
	padding: 20px 40px 35px 40px;
	font-size: 20px;
	height: 25px;
	width: 100%;
	color:#ffffff;
	display:inline-block;
	font-weight:600;
	text-align:center;
	vertical-align:middle;
    box-sizing: border-box;
    -webkit-box-sizing: border-box;
    -moz-box-sizing: border-box;
}

select
{
    font-size: 16px;
    height: 38px;
    padding: 9px 4px 7px 10px;
    border: 1px solid #aaaaaa;
    border-radius: 4px;
    box-shadow: none;
    outline: none;
    background-color: #fafafa;
}

option
{
	padding-top: 4px;
	padding-bottom: 4px;
	padding-left: 10px;
}

#modal_footer .team_validation_error_message{
    font-size: 14px;
}
.team_validation_error_message
{
	color:#FF0000;
	font-weight:300;
	text-align:center;
	margin-top:8px;
}

.team_discount
{
	margin-left: 30px;
}

.team_discount_selected
{
	font-weight:600;
}

.team_modal_section
{
	width: 500px;
}

.team_modal_invoice_section
{
	margin-top: 10px;
	display: inline-block;
}

.team_modal_invoice_item_label
{
	display:inline-block;
	float:left;
	margin-top:6px;
	text-align:right;
	width:330px;
}

.team_modal_invoice_item_value
{
	display:inline-block;
	margin-left:20px;
	margin-top:6px;
	width:140px;
}

.team_modal_invoice_item_supplement
{
	color:#aaaaaa;
	float:left;
	margin-right:150px;
	text-align:right;
	width:330px;
}

.team_modal_invoice_subtotal
{
	font-weight:600;
}

.team_modal_invoice_total
{
	font-size:larger;
	font-weight:600;
}
.team_member_setting{margin-bottom: 4px}
.team_member_setting:hover .team_member_setting_edit { visibility:visible; }
.bb-button-wrapper{
    font-size: 0;
    width: 100%;
}
.team_member_setting_edit
{
	width:64px;
	visibility:hidden;
	cursor:pointer;
	text-align:right;
	text-transform:uppercase;
	font-weight:bold;
}
#yearly_savings{
    font-weight: 400;
}
.team_member_setting_edit:hover
{
	text-decoration:underline;
}
.team_create_left_column .team_create_input_row {
    width: 100%
}
.team_create_left_column .team_create_input_row .team_create_input{
    margin-right: 3%;
}
.team_create_left_column .team_create_input_row .team_create_input:last-child {
    margin-right: 0;
}
.team_create_left_column .team_create_input_row .width_full{
    width: 100%
}
.team_create_left_column .team_create_input_row .width_half{
    width: 48%;
}
.team_create_left_column .team_create_input_row .width_half input{
    width: 100%;
}
.team_create_left_column .team_create_input_row .width_third{
    width: 30.7%;
}
.team_create_left_column .team_create_input_row .width_third input{
    width: 100%;
}
.team_create_left_column .team_create_input_row .width_two_third{
    width: 65.4%;
}
.team_create_left_column .team_create_input_row .width_two_third .width_third {
    width: 49%;
}
.team_create_left_column .team_create_input_row .width_two_third .width_third input{
    width: auto;
    vertical-align: middle;
    display: inline-block;
}

.settings_inner_content.viewTeam .setting .setting_edit,
.settings_inner_content.viewTeam .team_member_setting .team_member_setting_edit {
    visibility: visible;
}
.settings_inner_content.viewTeam .setting_edit {
    width: auto;
}
.settings_inner_content.viewTeam #team_members_setting .setting_details {
    width:auto;
    max-width:600px;
}

#team_tos_agree_label{
    padding: 8px 0;
    display: inline-block;
}
</style>
<?php

$countries = array
(
    'US' => _('United States'),
    'GB' => _('United Kingdom'),
    'AF' => _('Afghanistan'),
    'AX' => _('Aland Islands'),
    'AL' => _('Albania'),
    'DZ' => _('Algeria'),
    'AS' => _('American Samoa'),
    'AD' => _('Andorra'),
    'AO' => _('Angola'),
    'AI' => _('Anguilla'),
    'AQ' => _('Antarctica'),
    'AG' => _('Antigua And Barbuda'),
    'AR' => _('Argentina'),
    'AM' => _('Armenia'),
    'AW' => _('Aruba'),
    'AU' => _('Australia'),
    'AT' => _('Austria'),
    'AZ' => _('Azerbaijan'),
    'BS' => _('Bahamas'),
    'BH' => _('Bahrain'),
    'BD' => _('Bangladesh'),
    'BB' => _('Barbados'),
    'BY' => _('Belarus'),
    'BE' => _('Belgium'),
    'BZ' => _('Belize'),
    'BJ' => _('Benin'),
    'BM' => _('Bermuda'),
    'BT' => _('Bhutan'),
    'BO' => _('Bolivia'),
    'BA' => _('Bosnia And Herzegovina'),
    'BW' => _('Botswana'),
    'BV' => _('Bouvet Island'),
    'BR' => _('Brazil'),
    'IO' => _('British Indian Ocean Territory'),
    'BN' => _('Brunei Darussalam'),
    'BG' => _('Bulgaria'),
    'BF' => _('Burkina Faso'),
    'BI' => _('Burundi'),
    'KH' => _('Cambodia'),
    'CM' => _('Cameroon'),
    'CA' => _('Canada'),
    'CV' => _('Cape Verde'),
    'KY' => _('Cayman Islands'),
    'CF' => _('Central African Republic'),
    'TD' => _('Chad'),
    'CL' => _('Chile'),
    'CN' => _('China'),
    'CX' => _('Christmas Island'),
    'CC' => _('Cocos (Keeling) Islands'),
    'CO' => _('Colombia'),
    'KM' => _('Comoros'),
    'CG' => _('Congo'),
    'CD' => _('Congo, Democratic Republic'),
    'CK' => _('Cook Islands'),
    'CR' => _('Costa Rica'),
    'CI' => _('Cote D\'Ivoire'),
    'HR' => _('Croatia'),
    'CU' => _('Cuba'),
    'CY' => _('Cyprus'),
    'CZ' => _('Czech Republic'),
    'DK' => _('Denmark'),
    'DJ' => _('Djibouti'),
    'DM' => _('Dominica'),
    'DO' => _('Dominican Republic'),
    'EC' => _('Ecuador'),
    'EG' => _('Egypt'),
    'SV' => _('El Salvador'),
    'GQ' => _('Equatorial Guinea'),
    'ER' => _('Eritrea'),
    'EE' => _('Estonia'),
    'ET' => _('Ethiopia'),
    'FK' => _('Falkland Islands (Malvinas)'),
    'FO' => _('Faroe Islands'),
    'FJ' => _('Fiji'),
    'FI' => _('Finland'),
    'FR' => _('France'),
    'GF' => _('French Guiana'),
    'PF' => _('French Polynesia'),
    'TF' => _('French Southern Territories'),
    'GA' => _('Gabon'),
    'GM' => _('Gambia'),
    'GE' => _('Georgia'),
    'DE' => _('Germany'),
    'GH' => _('Ghana'),
    'GI' => _('Gibraltar'),
    'GR' => _('Greece'),
    'GL' => _('Greenland'),
    'GD' => _('Grenada'),
    'GP' => _('Guadeloupe'),
    'GU' => _('Guam'),
    'GT' => _('Guatemala'),
    'GG' => _('Guernsey'),
    'GN' => _('Guinea'),
    'GW' => _('Guinea-Bissau'),
    'GY' => _('Guyana'),
    'HT' => _('Haiti'),
    'HM' => _('Heard Island & Mcdonald Islands'),
    'VA' => _('Holy See (Vatican City State)'),
    'HN' => _('Honduras'),
    'HK' => _('Hong Kong'),
    'HU' => _('Hungary'),
    'IS' => _('Iceland'),
    'IN' => _('India'),
    'ID' => _('Indonesia'),
    'IR' => _('Iran, Islamic Republic Of'),
    'IQ' => _('Iraq'),
    'IE' => _('Ireland'),
    'IM' => _('Isle Of Man'),
    'IL' => _('Israel'),
    'IT' => _('Italy'),
    'JM' => _('Jamaica'),
    'JP' => _('Japan'),
    'JE' => _('Jersey'),
    'JO' => _('Jordan'),
    'KZ' => _('Kazakhstan'),
    'KE' => _('Kenya'),
    'KI' => _('Kiribati'),
    'KR' => _('Korea'),
    'KW' => _('Kuwait'),
    'KG' => _('Kyrgyzstan'),
    'LA' => _('Lao People\'s Democratic Republic'),
    'LV' => _('Latvia'),
    'LB' => _('Lebanon'),
    'LS' => _('Lesotho'),
    'LR' => _('Liberia'),
    'LY' => _('Libyan Arab Jamahiriya'),
    'LI' => _('Liechtenstein'),
    'LT' => _('Lithuania'),
    'LU' => _('Luxembourg'),
    'MO' => _('Macao'),
    'MK' => _('Macedonia'),
    'MG' => _('Madagascar'),
    'MW' => _('Malawi'),
    'MY' => _('Malaysia'),
    'MV' => _('Maldives'),
    'ML' => _('Mali'),
    'MT' => _('Malta'),
    'MH' => _('Marshall Islands'),
    'MQ' => _('Martinique'),
    'MR' => _('Mauritania'),
    'MU' => _('Mauritius'),
    'YT' => _('Mayotte'),
    'MX' => _('Mexico'),
    'FM' => _('Micronesia, Federated States Of'),
    'MD' => _('Moldova'),
    'MC' => _('Monaco'),
    'MN' => _('Mongolia'),
    'ME' => _('Montenegro'),
    'MS' => _('Montserrat'),
    'MA' => _('Morocco'),
    'MZ' => _('Mozambique'),
    'MM' => _('Myanmar'),
    'NA' => _('Namibia'),
    'NR' => _('Nauru'),
    'NP' => _('Nepal'),
    'NL' => _('Netherlands'),
    'AN' => _('Netherlands Antilles'),
    'NC' => _('New Caledonia'),
    'NZ' => _('New Zealand'),
    'NI' => _('Nicaragua'),
    'NE' => _('Niger'),
    'NG' => _('Nigeria'),
    'NU' => _('Niue'),
    'NF' => _('Norfolk Island'),
    'MP' => _('Northern Mariana Islands'),
    'NO' => _('Norway'),
    'OM' => _('Oman'),
    'PK' => _('Pakistan'),
    'PW' => _('Palau'),
    'PS' => _('Palestinian Territory, Occupied'),
    'PA' => _('Panama'),
    'PG' => _('Papua New Guinea'),
    'PY' => _('Paraguay'),
    'PE' => _('Peru'),
    'PH' => _('Philippines'),
    'PN' => _('Pitcairn'),
    'PL' => _('Poland'),
    'PT' => _('Portugal'),
    'PR' => _('Puerto Rico'),
    'QA' => _('Qatar'),
    'RE' => _('Reunion'),
    'RO' => _('Romania'),
    'RU' => _('Russian Federation'),
    'RW' => _('Rwanda'),
    'BL' => _('Saint Barthelemy'),
    'SH' => _('Saint Helena'),
    'KN' => _('Saint Kitts And Nevis'),
    'LC' => _('Saint Lucia'),
    'MF' => _('Saint Martin'),
    'PM' => _('Saint Pierre And Miquelon'),
    'VC' => _('Saint Vincent And Grenadines'),
    'WS' => _('Samoa'),
    'SM' => _('San Marino'),
    'ST' => _('Sao Tome And Principe'),
    'SA' => _('Saudi Arabia'),
    'SN' => _('Senegal'),
    'RS' => _('Serbia'),
    'SC' => _('Seychelles'),
    'SL' => _('Sierra Leone'),
    'SG' => _('Singapore'),
    'SK' => _('Slovakia'),
    'SI' => _('Slovenia'),
    'SB' => _('Solomon Islands'),
    'SO' => _('Somalia'),
    'ZA' => _('South Africa'),
    'GS' => _('South Georgia And Sandwich Isl.'),
    'ES' => _('Spain'),
    'LK' => _('Sri Lanka'),
    'SD' => _('Sudan'),
    'SR' => _('Suriname'),
    'SJ' => _('Svalbard And Jan Mayen'),
    'SZ' => _('Swaziland'),
    'SE' => _('Sweden'),
    'CH' => _('Switzerland'),
    'SY' => _('Syrian Arab Republic'),
    'TW' => _('Taiwan'),
    'TJ' => _('Tajikistan'),
    'TZ' => _('Tanzania'),
    'TH' => _('Thailand'),
    'TL' => _('Timor-Leste'),
    'TG' => _('Togo'),
    'TK' => _('Tokelau'),
    'TO' => _('Tonga'),
    'TT' => _('Trinidad And Tobago'),
    'TN' => _('Tunisia'),
    'TR' => _('Turkey'),
    'TM' => _('Turkmenistan'),
    'TC' => _('Turks And Caicos Islands'),
    'TV' => _('Tuvalu'),
    'UG' => _('Uganda'),
    'UA' => _('Ukraine'),
    'AE' => _('United Arab Emirates'),
    'UM' => _('United States Outlying Islands'),
    'UY' => _('Uruguay'),
    'UZ' => _('Uzbekistan'),
    'VU' => _('Vanuatu'),
    'VE' => _('Venezuela'),
    'VN' => _('Viet Nam'),
    'VG' => _('Virgin Islands, British'),
    'VI' => _('Virgin Islands, U.S.'),
    'WF' => _('Wallis And Futuna'),
    'EH' => _('Western Sahara'),
    'YE' => _('Yemen'),
    'ZM' => _('Zambia'),
    'ZW' => _('Zimbabwe'),
);
$teamsCount = TDOTeamAccount::getAdministeredTeamsCountForAdmin($userid);
$is_member_of_team = false;
if (!$teamsCount) {
    $is_member_of_team = TDOTeamAccount::getTeamForTeamMember($userid);
}

$action = NULL;
if (isset($_POST['action']))
    $action = $_POST['action'];
if (!$action && isset($_GET['action']))
    $action = $_GET['action'];

?>
<div class="setting_options_container todo-for-business">
<div class="settings_inner_content <?php echo $action; ?>">

<?php
if ((!$teamsCount && $action === 'createTeam') || ($teamsCount && $action !== 'createTeam') || $is_member_of_team) {
    if (!$teamsCount && $is_member_of_team) {
        $action = 'user_is_member';
    }
	switch ($action)
	{
        case "teamUnsubscribe": ?>
            <script>displayGlobalErrorMessage('<?php _e(' You are successfully removed from the team.'); ?>');</script>
        <?php
        break;
		case "createTeam":
		{
			$user = TDOUser::getUserForUserId($userid);
            $possibleDiscovery = TDOTeamAccount::getPossibleDiscoveryAnswers();
			?>
                <!--<section class="setup-coach-wrapper">
                    <h1>Let's get started!</h1>
                    <ul class="steps">
                        <li class="current">
                            <span>1</span>
                            Create Your Team
                        </li>
                        <li class="next">
                            <span>2</span>
                            Invite Team Members
                        </li>
                        <li class="next">
                            <span>3</span>
                            Share Team Lists
                        </li>
                        <li class="last next">
                            <span class="fa fa-check"></span>
                        </li>
                    </ul>
                </section>-->
	            <h2><?php _e('Purchase Todo for Business'); ?></h2>
                <p><?php _e('A team purchasing feature of Todo Cloud'); ?></p>
                	<section class="team-create-info">
                        <h3><?php _e('Set an administrator'); ?></h3>
                        <p><?php printf(_('You are currently logged in as %s (%s) this account will be the administrator and billing contact for your new Todo for Business account. After you create your team you may add additional administrators to help you manage your account.'), $user->displayName(), $user->username());
                            ?></p>
                        <p><?php printf(_('If you would like to use a different Todo account to manage your Todo for Business account, please %slog out%s and create a new account or log in with a different user.'), '<a href="?method=logout">', '</a>');
                            ?></p>
                    </section>
                    
                    <!-- ABOUT YOUR TEAM -->
                    <section class="team-create-about col-sm-12 col-md-10 col-lg-8 row">
                        <h3><?php _e('Tell us about your team'); ?></h3>
                        <label id="teamname_label" class="team_create_input_label" for="teamname"><?php _e('Team name'); ?></label>
                        <input id="teamname" class="team_create_input_text" type="text" value="" maxlength="127" name="teamname"/>
                        <div class="row clearfix">
                            <div class="col-sm-4">
                                <label id="num_of_members_label" class="team_create_input_label" for="num_of_members"><?php _e('Number of members'); ?></label>
                                <input id="num_of_members" class="team_create_input_text" type="text" value="5" maxlength="3" name="num_of_members" autocomplete="off" onkeyup="updateCreateTeamPricing();" onchange="updateCreateTeamPricing();" />
                            </div>
                            <div class="col-sm-8">
                                <div class="row">
                                    <label class="team_create_input_label">&nbsp;</label>
                                    <div id="team_create_pricing_options_normal" class="clearfix">
                                        <div class="col-sm-6">
                                            <label for="monthly_radio" class="team_pricing_option monthly selected">
                                                <input
                                                    id="monthly_radio"
                                                    type="radio"
                                                    value="1"
                                                    name="billing_frequency"
                                                    checked="checked"
                                                />
                                                <span id="monthly_team_price">$<?php echo $systemSettingTeamMonthlyPricePerUser;?></span> <?php _e('USD / month'); ?>
                                                <small>$<?php echo number_format($systemSettingTeamMonthlyPricePerUser, 2); ?> <?php _e('per user / month'); ?></small>
                                            </label>
                                        </div>
                                        <div class="col-sm-6">
                                            <label for="yearly_radio" class="team_pricing_option yearly">
                                                <input
                                                    id="yearly_radio"
                                                    type="radio"
                                                    value="2"
                                                    name="billing_frequency"
                                                />
                                                <span id="yearly_team_price">$<?php echo $systemSettingTeamYearlyPricePerUser;?></span> <?php _e('USD / year'); ?>
                                                <small>$<?php echo number_format(round($systemSettingTeamYearlyPricePerUser / 12, 2), 2);?> <?php _e('per user / month'); ?></small>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div id="team_create_pricing_options_custom" class="team_item_hidden"><?php
                                    printf(_('You have entered more than 500 members (the maximum allowed using this page). Please contact us (%sbusiness@appigo.com%s) to complete your team purchase.'), sprintf('<a href="mailto:business@appigo.com?subject=%s">', _('Todo Cloud High Volume Licensing')), '</a>'); ?>
                                </div>
                            </div>
                        </div>
                        <div class="team_create_pricing_detail_paragraph"><?php
                            printf(_('Choosing the yearly option will save you %s compared to the monthly option.'), '<span id="yearly_savings">$' . (($systemSettingTeamMonthlyPricePerUser * 12) - $systemSettingTeamYearlyPricePerUser) . '</span>');
                            ?>
                        </div>
                    </section>
                    <div class="clearfix"></div>
                    
                    <!-- CONTACT INFORMATION -->
                    
                    <section class="team-create-contact-info col-sm-12 col-md-10 col-lg-8 row">
                        <h3><?php _e('Contact Information'); ?></h3>
                        <div class="row">
                            <div class="col-sm-6">
                                <label id="biz_name_label" class="team_create_input_label" for="biz_name"><?php _e('Business name/Primary contact'); ?></label>
                                <input id="biz_name" class="team_create_input_text" type="text" value="" maxlength="127" name="biz_name" />
                            </div>
                            <div class="col-sm-6">
                                <label id="biz_phone_label" class="team_create_input_label" for="ccard_name"><?php _e('Phone number (optional)'); ?></label>
                                <input id="biz_phone" class="team_create_input_text" type="text" value="" maxlength="127" name="biz_phone"/>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-12">
                                <label id="biz_country_label" class="team_create_input_label" for="biz_country"><?php _e('Country'); ?></label>
                                <select class="form-field" name="biz_country"  id="biz_country">
                                    <?php foreach($countries as $key=>$value): ?>
                                        <option value="<?php echo $key; ?>"><?php echo $value; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </section>
                    <div class="clearfix"></div>
                    
                    <!-- BILLING INFORMATION -->
                    
                    <section class="team-create-contact-info col-sm-12 col-md-10 col-lg-8 row">
                        <h3 class="pull-left"><?php _e('Billing Information'); ?></h3>
                        <h4 class="pull-right info-title"><?php _e('You won’t be charged now.'); ?></h4>
                        <div class="clearfix"></div>
                        <div class="row">
                            <div class="col-sm-6">
                                <label id="ccard_number_label" class="team_create_input_label" for="ccard_number"><?php _e('Credit card number'); ?></label>
                                <input id="ccard_number" class="team_create_input_text" type="text" value="" maxlength="127" name="ccard_number" autocomplete="off" />
                            </div>
                            <div class="col-sm-6">
                                <label id="ccard_name_label" class="team_create_input_label" for="ccard_name"><?php _e('Name on card'); ?></label>
                                <input id="ccard_name" class="team_create_input_text" type="text" value="" maxlength="127" name="ccard_name" autocomplete="off" />
                            </div>
                        </div>
                        <div class="row">
                        	<div class="col-sm-4">
                            	<label id="ccard_cvc_label" class="team_create_input_label" for="ccard_cvc"><?php _e('Security code (CVC)'); ?></label>
                            	<input id="ccard_cvc" class="team_create_input_text" type="text" value="" maxlength="4" name="ccard_cvc"  autocomplete="off" />
                            </div>
                        	<div class="col-sm-4">
                            	<label id="ccard_month_label" class="team_create_input_label" for="ccard_month"><?php _e('Expiry month'); ?></label>
                                <select id="ccard_month" data-encrypted-name="ccard_month">
                                    <?php
                                    $curMonth = intval(date('n'));
                                    for($i = 1; $i <= 12; $i++) : ?>
                                        <option value="<?php echo $i; ?>" <?php echo ($curMonth === $i)?'selected':''; ?>><?php echo sprintf("%02d", $i); ?> - <?php echo _(date("F", mktime(0, 0, 0, $i, 10))); ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        	<div class="col-sm-4">
                            	<label id="ccard_year_label" class="team_create_input_label" for="ccard_year"><?php _e('Expiry year'); ?></label>
                                <select id="ccard_year" data-encrypted-name="ccard_year">
                                	<?php
									
										$currentYear = (int)date('Y');
										$endYear = $currentYear + 10;
										
										for ($i = $currentYear; $i < $endYear; $i++)
										{
											if ($currentYear == $i)
												echo '<option value="' . $i . '" selected="selected">' . $i . '</option>';
											else
												echo '<option value="' . $i . '">' . $i . '</option>';
										}

									?>
                                </select>
                            </div>
                        </div>
                    </section>
                    <div class="clearfix"></div>

                    <section class="team-create-submit-form col-sm-12 col-md-10 col-lg-8 row">
                        <div class="row">

                            <div class="col-sm-4">
                                <label class="team_create_input_label" >&nbsp;</label>
                                <div class="colteam_create_input_row">
                                    <div class="team_tos_agree_field">
                                        <label id="team_tos_agree_label" for="team_tos_agree">
                                        <input id="team_tos_agree" type="checkbox"  name="team_tos_agree" onclick="updatePurchaseButtonEnablement(event, this, false);"/>
                                            <?php _e('I agree to the'); ?> <a href="/terms" target="_blank" title="<?php _e('Review the Terms of Service'); ?>"><?php _e(' Todo Cloud Terms of Service'); ?></a>.</label>
                                    </div>
                                </div>
                            </div>
                            <?php if ($possibleDiscovery && is_array($possibleDiscovery) && sizeof($possibleDiscovery)) : ?>
                                <div class="col-sm-4">
                                    <label id="discovery_answer_label" class="team_create_input_label" for="discovery_answer"><?php _e('How did you hear about us?'); ?></label>
                                    <select name="discoveryAnswer" id="discovery_answer">
											<option value="-not-specified-">-- <?php _e('Select One'); ?> --</option>
                                        <?php foreach ($possibleDiscovery as $v) : ?>
                                            <option value="<?php echo $v; ?>"><?php echo _($v); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="clearfix"></div>
                        <div class="team_create_input_row  clearfix">
                            <button id="team_purchase_button" class="btn-default btn-orange disabled"><?php _e('Start my Trial'); ?></button>
                        </div>
                        <div class="team_create_input_row">
                        	<div id="team_validate_error" class="team_validation_error_message width_full team_item_hidden"><?php _e('Please enter the missing information above indicated in red.'); ?></div>
                        </div>
                        <input id="stripe_token" type="hidden" value=""/>
                    </section>
                    <div class="clearfix"></div>

            <?php
			break;
		}
		case "viewTeam":
		{
			if (!isset($_GET['teamid']))
			{
				echo "<script>history.go(-1);</script>";
				break;
			}
			$teamid = $_GET['teamid'];
			
			// Check to see if the session user is an administrator of the team. If they are not
			// output an error.
			if (!TDOTeamAccount::isAdminForTeam($userid, $teamid))
			{
				echo '<div class="team_page_link"><a href="?appSettings=show&option=teaming">&lt; ' . _('Team Admin') . '</a></div>';
				echo "<script>displayGlobalErrorMessage('" . _('You are not authorized to administer the specified team.') . "');</script>";
				break;
			}
			
			
			$team = TDOTeamAccount::getTeamForTeamID($teamid, $userid);
			
			if (!$team)
			{
				echo '<div class="team_page_link"><a href="?appSettings=show&option=teaming">&lt; ' . _('View Your List of Teams') . '</a></div>';
				echo "<script>displayGlobalErrorMessage('" . _('The specified team could not be found.') . "');</script>";
				break;
			}
			
			$billingFrequencyString = "monthly";
			if ($team->getBillingFrequency() == SUBSCRIPTION_TYPE_YEAR)
				$billingFrequencyString = "yearly";
			
			?>
            
           <input type="hidden" id="teamID" value="<?php echo $team->getTeamID(); ?>" />
           
           <div id="team_name_setting" class="setting">
              <input id="origTeamName" type="hidden" value="<?php echo $team->getTeamName(); ?>" />
              
                <span class="setting_name"><?php _e('Team Name'); ?></span>
                <span class="setting_details">
					<span id="teamNameLabel"><?php echo $team->getTeamName(); ?></span>
                </span>
                <span id="team_name_edit" class="setting_edit" onClick="displaySettingDetails('team_name_config', 'team_name_edit')"><?php _e('Edit'); ?></span>
                <div id="team_name_config" class="setting_details setting_config">
                	<div class="labeled_control">
                    	<label><?php _e('Team Name'); ?></label>
                        <input type="text" onkeyup="validateChangeTeamName()" name="teamName" id="teamName" value="<?php echo $team->getTeamName(); ?>"/>
                        <span id="team_name_status" class="option_status"></span>
                    </div>
                    <div class="save_cancel_button_wrap">
                    	<div id="teamNameChangeSubmit" class="button disabled"><?php _e('Save'); ?></div>
                        <div class="button" onClick="cancelTeamNameUpdate()"><?php _e('Cancel'); ?></div>
                    </div>
                </div>
           </div>
           
           
           <div id="team_info_setting" class="setting">
              <input id="origBizName" type="hidden" value="<?php echo $team->getBizName(); ?>" />
              <input id="origBizPhone" type="hidden" value="<?php echo $team->getBizPhone(); ?>" />
              <input id="origBizAddr1" type="hidden" value="<?php echo $team->getBizAddr1(); ?>" />
              <input id="origBizAddr2" type="hidden" value="<?php echo $team->getBizAddr2(); ?>" />
              <input id="origBizCity" type="hidden" value="<?php echo $team->getBizCity(); ?>" />
              <input id="origBizState" type="hidden" value="<?php echo $team->getBizState(); ?>" />
              <input id="origBizCountry" type="hidden" value="<?php echo $team->getBizCountry(); ?>" />
              <input id="origBizPostalCode" type="hidden" value="<?php echo $team->getBizPostalCode(); ?>" />
              
                <span class="setting_name"><?php _e('Contact Information'); ?></span>
                <span class="setting_details">
					Business Name: <span id="bizNameLabel"><?php echo $team->getBizName(); ?></span><br/>
                    Phone:<span id="bizPhoneLabel"><?php echo $team->getBizPhone(); ?></span><br/>
                    Address: <span id="bizAddr1Label"><?php echo $team->getBizAddr1(); ?></span><br/>
                    <span id="bizAddr2Label"><?php echo $team->getBizAddr2(); ?></span><br/>
                    City: <span id="bizCityLabel"><?php echo $team->getBizCity(); ?></span><br/>
                    State: <span id="bizStateLabel"><?php echo $team->getBizState(); ?></span><br/>
												 Country: <span id="bizCountryLabel"><?php echo TDOTeamAccount::countryNameForCode($team->getBizCountry()); ?></span><br/>
                    Postal Code: <span id="bizPostalCodeLabel"><?php echo $team->getBizPostalCode(); ?></span><br/>
                </span>
                <span id="team_info_edit" class="setting_edit" onClick="displaySettingDetails('team_info_config', 'team_info_edit')"><?php _e('Edit'); ?></span>
                <div id="team_info_config" class="setting_details setting_config">
                	<div class="labeled_control">
                    	<label><?php _e('Business Name'); ?></label>
                        <input type="text" onkeyup="validateChangeTeamInfo()" name="bizName" id="bizName" value="<?php echo $team->getBizName(); ?>"/>
                    	<label><?php _e('Phone'); ?></label>
                        <input type="text" onkeyup="validateChangeTeamInfo()" name="bizPhone" id="bizPhone" value="<?php echo $team->getBizPhone(); ?>"/>
                    	<label><?php _e('Address'); ?></label>
                        <input type="text" onkeyup="validateChangeTeamInfo()" name="bizAddr1" id="bizAddr1" value="<?php echo $team->getBizAddr1(); ?>"/>
                        <input type="text" onkeyup="validateChangeTeamInfo()" name="bizAddr2" id="bizAddr2" value="<?php echo $team->getBizAddr2(); ?>"/>
                    	<label><?php _e('City'); ?></label>
                        <input type="text" onkeyup="validateChangeTeamInfo()" name="bizCity" id="bizCity" value="<?php echo $team->getBizCity(); ?>"/>
                    	<label><?php _e('State'); ?></label>
                        <input type="text" onkeyup="validateChangeTeamInfo()" name="bizState" id="bizState" value="<?php echo $team->getBizState(); ?>"/>
                    	<label><?php _e('Country'); ?></label>
                        <input type="text" onkeyup="validateChangeTeamInfo()" name="bizCountry" id="bizCountry" value="<?php echo $team->getBizCountry(); ?>"/>
                    	<label><?php _e('ZIP/Postal Code'); ?></label>
                        <input type="text" onkeyup="validateChangeTeamInfo()" name="bizPostalCode" id="bizPostalCode" value="<?php echo $team->getBizPostalCode(); ?>"/>
                        <span id="team_info_status" class="option_status"></span>
                    </div>
                    <div class="save_cancel_button_wrap">
                    	<div id="teamInfoChangeSubmit" class="button disabled"><?php _e('Save'); ?></div>
                        <div class="button" onClick="cancelTeamInfoUpdate()"><?php _e('Cancel'); ?></div>
                    </div>
                </div>
           </div>
           
           <div id="team_expiration_date_setting" class="setting">
                <span class="setting_name"><?php _e('License Expiration/Renewal'); ?></span>
                <span class="setting_details">
				<?php
												 
				// Need to know how many members there are to determine
				// how to handle the delete team functionality.
				$usedLicenseCount = 0;
				$memberInfos = TDOTeamAccount::getTeamMemberInfo($userid, $teamid);
				if ($memberInfos)
				{
					$usedLicenseCount = count($memberInfos);
				}
				
				$teamHasExpired = ($team->getExpirationDate() < time());
				$deleteTeamAllowed = false;
				if ($teamHasExpired && $usedLicenseCount <= 0)
					$deleteTeamAllowed = true;

												 
				if ($teamHasExpired)
				{
					echo "<span id=\"teamExpirationDateLabel\">".printf(_('Expired on %s.'), date("d M Y", $team->getExpirationDate())) . "</span>";
				}
				else
				{
					echo "<span id=\"teamExpirationDateLabel\">" . date("d M Y", $team->getExpirationDate()) . " ($billingFrequencyString)</span>";
				}
												 
				?>
                </span>
                <span id="team_purchase_history_view" class="setting_edit" onClick="viewTeamPurchases('<?php echo $teamid; ?>')"><?php _e('Purchases'); ?></span>
				<span id="team_delete" class="setting_edit" onClick="deleteTeamModal('<?php echo $teamid; ?>', <?php echo $deleteTeamAllowed ? "true" : "false"; ?>);"><?php _e('Delete'); ?></span>
           </div>
           
           <div id="team_license_count_setting" class="setting">
                <span class="setting_name"><?php _e('License Count'); ?></span>
                <span class="setting_details">
					<span id="teamLicenseCountLabel"><?php echo $team->getNewLicenseCount(); ?></span>
                </span>
                <span id="team_license_count_edit" class="setting_edit" onClick="displayChangeLicenseCount()"><?php _e('Change'); ?></span>
           </div>
           
           <div id="team_members_setting" class="setting">
                <span class="setting_name"><?php _e('Team Administrators'); ?></span>
                <span class="setting_details">
                
                <?php
				
				$billingUserID = $team->getBillingUserID();
				$teamAdminIDs = TDOTeamAccount::getAdminUserIDsForTeam($teamid);
				$i = 1;
				if ($teamAdminIDs)
				{
					foreach ($teamAdminIDs as $adminID)
					{
						$adminDisplayName = TDOUser::displayNameForUserId($adminID);
						if ((!empty($billingUserID)) && ($billingUserID == $adminID))
							$adminDisplayName = "** $adminDisplayName";
						$adminEmail = TDOUser::usernameForUserId($adminID);
						
						?>
                        
                        <div id="team_admin_slot_<?php echo $i; ?>" class="team_member_setting">
                        	<span><?php echo $i; ?>.&nbsp;<?php echo $adminDisplayName . " (" . $adminEmail . ")"; ?></span>
                            <?php
							
							if ((!empty($billingUserID)) && ($billingUserID == $adminID))
							{
								echo '<span id="team_remove_admin_link_' . $i . '" class="team_member_setting_edit" onclick="displayRemoveMemberModal(\'' . $i . '\', \'' . $adminID . '\', \'admin\')">' . _('Remove') . '&nbsp;</span>';
								echo '<span id="team_cancel_renewal_link_' . $i . '" class="team_member_setting_edit" onclick="displayCancelRenewalModal(\'' . $i . '\', \'' . $adminID . '\')">&nbsp;' . _('Cancel Renewal') . '</span>';
							}
							else
							{
                                echo '<span id="team_remove_admin_link_' . $i . '" class="team_member_setting_edit" onclick="displayRemoveMemberModal(\'' . $i . '\', \'' . $adminID . '\', \'admin\')">&nbsp;' . _('Remove') . '&nbsp;</span>';
								if ($userid == $adminID) // only show this on the session user
									echo '<span id="team_cancel_renewal_link_' . $i . '" class="team_member_setting_edit" onclick="displayBecomeBillingAdmin();">&nbsp;' . _('Become Billing Admin') . '</span>';
							}
							
							?>
                           	
                        </div>
                        
                        <?php
						$i++;
					}
				}
				
				$invitedLicenseCount = 0;
				$invitations = TDOTeamAccount::getTeamInvitationInfo($userid, $teamid, TEAM_MEMBERSHIP_TYPE_ADMIN);
				if ($invitations)
				{
					$invitedLicenseCount = count($invitations);
				}

				if ($invitations)
				{
					foreach ($invitations as $invitation)
					{
						?>
                        <div id="team_admin_slot_<?php echo $i; ?>" class="team_member_setting">
                        <span><?php echo $i . ". " . _('INVITATION') . ": " . $invitation['email']; ?></span>
                        <span class="team_member_setting_edit" onClick="displayDeleteInvitationModal('<?php echo $i; ?>', '<?php echo $invitation['invitationid']; ?>', 'admin')">&nbsp;<?php _e('Delete'); ?>&nbsp;</span>
                        <span class="team_member_setting_edit" onClick="displayResendInvitationModal('<?php echo $i; ?>', '<?php echo $invitation['invitationid']; ?>', 'admin')">&nbsp;<?php _e('Resend'); ?>&nbsp;</span>
                        </div>
                        
                        <?php
						$i++;
					}
				}
				
				?>
                    <div id="team_admin_slot_<?php echo $i; ?>" class="team_member_setting">
					<span><?php echo $i; ?>.&nbsp;<input type="text" id="team_unused_admin" placeholder="<?php _e('email address'); ?>" onKeyUp="validateTeamInviteEmail('team_unused_admin')" /></span>
                    <span class="team_member_setting_edit" onClick="inviteTeamMember('<?php echo $i; ?>', 'admin')"><?php _e('Invite'); ?></span>
					</div>
                
                	<?php
						if (empty($billingUserID))
							echo '<div class="team_member_setting" style="font-size:smaller;font-weight:bold;">' . _('Note: No billing administrator is set. Your team account will not renew at the renewal date until you have successfully configured a billing administrator.') . '</div>';
						else
							echo '<div class="team_member_setting" style="font-size:smaller;">' . _('**Indicates the billing administrator.') . '</div>';
					?>
                </span>
           </div>
           
           <div id="team_members_setting" class="setting">
                <span class="setting_name"><?php _e('Team Members'); ?></span>
                <span class="setting_details">
                
                <?php
				
				
				$invitedLicenseCount = 0;
				$invitations = TDOTeamAccount::getTeamInvitationInfo($userid, $teamid);
				if ($invitations)
				{
					$invitedLicenseCount = count($invitations);
				}
								
				$i = 1;
				if ($memberInfos)
				{
					foreach ($memberInfos as $memberInfo)
					{
						?>
						
                        <div id="team_member_slot_<?php echo $i; ?>" class="team_member_setting">
						<span><?php echo $i; ?>.&nbsp;<?php echo $memberInfo['displayName'] . " (" . $memberInfo['userName'] . ")"; ?></span>
                        <span id="team_remove_member_link" class="team_member_setting_edit" onClick="displayRemoveMemberModal('<?php echo $i; ?>', '<?php echo $memberInfo['userid']; ?>', 'member')"><?php _e('Remove'); ?></span>
                        </div>
						
						<?php
						$i++;
					}
				}
				
				if ($invitations)
				{
					foreach ($invitations as $invitation)
					{
						?>
                        <div id="team_member_slot_<?php echo $i; ?>" class="team_member_setting">
						<span><?php echo $i . ". INVITATION: " . $invitation['email']; ?></span>
                        <span class="team_member_setting_edit" onClick="displayDeleteInvitationModal('<?php echo $i; ?>', '<?php echo $invitation['invitationid']; ?>', 'member')">&nbsp;<?php _e('Delete'); ?>&nbsp;</span>
                        <span class="team_member_setting_edit" onClick="displayResendInvitationModal('<?php echo $i; ?>', '<?php echo $invitation['invitationid']; ?>', 'member')">&nbsp;<?php _e('Resend'); ?>&nbsp;</span>
                        </div>
                        
                        <?php
						$i++;
					}
				}
				
				$licenseCount = $team->getNewLicenseCount();
				$unusedLicenseCount = $licenseCount - $usedLicenseCount - $invitedLicenseCount;
				
				for (;$i <= $licenseCount; $i++)
				{
					?>

                    <div id="team_member_slot_<?php echo $i; ?>" class="team_member_setting">
					<span><?php echo $i; ?>.&nbsp;<input type="text" id="team_unused_license_<?php echo $i; ?>" placeholder="<?php _e('email address'); ?>" onKeyUp="validateTeamInviteEmail('team_unused_license_<?php echo $i; ?>')" /></span>
                    <span class="team_member_setting_edit" onClick="inviteTeamMember('<?php echo $i; ?>', 'member')"><?php _e('Invite'); ?></span>
					</div>

                    <?php
				}
								
				?>
                
                </span>
           </div>
           
                           
            <?php
		
			break;
		}
		case "becomeBillingAdmin":
		{
			if (!isset($_GET['teamid']))
			{
				echo "<script>history.go(-1);</script>";
				break;
			}
			$teamid = $_GET['teamid'];
												 
			// Check to see if the session user is an administrator of the team. If they are not
			// output an error.
			if (!TDOTeamAccount::isAdminForTeam($userid, $teamid))
			{
                echo '<div class="team_page_link"><a href="?appSettings=show&option=teaming">&lt; ' . _('Team Admin') . '</a></div>';
                echo "<script>displayGlobalErrorMessage('" . _('You are not authorized to administer the specified team.') . "');</script>";
				break;
			}
												 
			?>
            
           <input type="hidden" id="teamID" value="<?php echo $teamid; ?>" />
            
            <div class="team_header">
	            <h1><?php _e('Become the Team Administrator'); ?></h1>
                <div class="team_create_detail_paragraph"><?php _e('After entering your credit card information, you will become the billing administrator. Your credit card will be billed at the next billing cycle.'); ?></div>
            </div>
            <div class="team_create_container">
            	<div class="team_create_left_column">
                
                    <div id="billing_section" class="team_create_section">
                    	<div class="team_create_section_header">
                        	<h3><?php _e('Billing Information'); ?></h3>
                        </div>
                        <div class="team_create_input_row">
                            <div class="team_create_input width_half">
                                <label id="ccard_number_label" class="team_create_input_label" for="ccard_number"><?php _e('Credit card number'); ?></label>
                                <input id="ccard_number" class="team_create_input_text" type="text" value="" maxlength="127" name="ccard_number"  autocomplete="off" />
                            </div>
                            <div class="team_create_input width_half">
                                <label id="ccard_name_label" class="team_create_input_label" for="ccard_name"><?php _e('Name on card'); ?></label>
                                <input id="ccard_name" class="team_create_input_text" type="text" value="" maxlength="127" name="ccard_name" autocomplete="off" />
                            </div>
                        </div>
                        <div class="team_create_input_row">
                        	<div class="team_create_input width_third">
                            	<label id="ccard_cvc_label" class="team_create_input_label" for="ccard_cvc"><?php _e('Security code (CVC)'); ?></label>
                            	<input id="ccard_cvc" class="team_create_input_text" type="text" value="" maxlength="4" name="ccard_cvc" autocomplete="off" />
                            </div>
                        	<div class="team_create_input width_third">
                            	<label id="ccard_month_label" class="team_create_input_label" for="ccard_month"><?php _e('Expiry month'); ?></label>
                                <select id="ccard_month" data-encrypted-name="ccard_month">
                                    <?php
                                    $curMonth = intval(date('n'));
                                    for($i = 1; $i <= 12; $i++) : ?>
                                        <option value="<?php echo $i; ?>" <?php echo ($curMonth === $i)?'selected':''; ?>><?php echo sprintf("%02d", $i); ?> - <?php echo _(date("F", mktime(0, 0, 0, $i, 10))); ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        	<div class="team_create_input width_third">
                            	<label id="ccard_year_label" class="team_create_input_label" for="ccard_year"><?php _e('Expiry year'); ?></label>
                                <select id="ccard_year" data-encrypted-name="ccard_year">
                                	<?php
									
										$currentYear = (int)date('Y');
										$endYear = $currentYear + 10;
										
										for ($i = $currentYear; $i < $endYear; $i++)
										{
											if ($currentYear == $i)
												echo '<option value="' . $i . '" selected="selected">' . $i . '</option>';
											else
												echo '<option value="' . $i . '">' . $i . '</option>';
										}
									
									?>
                                </select>
                            </div>
                        </div>
                        <div><input id="stripe_token" type="hidden" value=""/></div>
                        
                        
                    </div>                    
                    
                    <!-- REVIEW CHANGES BUTTON -->
                    
                    <div id="review_changes_button_section" class="team_create_section">
                        <div class="team_create_input_row">
                        	<div class="team_tos_agree_field width_full">
                            	<input id="team_tos_agree" type="checkbox"  name="team_tos_agree" onclick="enableUpdateBillingInfoButton(event, this);"/>
                                <label id="team_tos_agree_label" for="team_tos_agree"><?php printf(_('I agree to the %sTodo Cloud Terms of Service%s.'), sprintf('<a href="/terms" target="_blank" title="%s">', _('Review the Terms of Service')), '</a>'); ?></label>
                            </div>
                        </div>
                        <div class="team_create_input_row">
							<div id="team_purchase_button" class="disabled_purchase_button" ><?php _e('Update Billing Info'); ?></div>
                        </div>
                        <div class="team_create_input_row">
                        	<div id="team_validate_error" class="team_validation_error_message width_full team_item_hidden"><?php _e('Please enter the missing information above indicated in red.'); ?></div>
                        </div>
                    </div>
                    
                    <!-- RESULTS PLACEHOLDERS -->
                    
                    <input type="hidden" id="team_change_new_expiration_date" value=""/>
                    <input type="hidden" id="team_change_new_num_of_members" value=""/>
                    <input type="hidden" id="team_change_billing_frequency" value=""/>
                    <input type="hidden" id="team_change_bulk_discount" value=""/>
                    <input type="hidden" id="team_change_discount_percentage" value=""/>
                    <input type="hidden" id="team_change_current_account_credit" value=""/>
                    <input type="hidden" id="team_change_total_charge" value=""/>
                    
                </div>
                <div class="team_create_right_column">
                	<div class="team_help_section">
                    	<h3><?php _e('Can I change the size of my team later?'); ?></h3>
                        <div class="team_create_detail_paragraph"><?php _e('Yes! You can change the number of team licenses you need at any time. If you increase the number of team members, you&#39;ll just pay a pro-rated difference.'); ?></div>
                    </div>
                	<div class="team_help_section">
                    	<h3><?php _e('Can I change my payment method later?'); ?></h3>
                        <div class="team_create_detail_paragraph"><?php _e('Yes! You can even add additional team administrators and they can take over the billing in your place.'); ?></div>
                    </div>
                	<div class="team_help_section">
                    	<h3><?php _e('Accepted payment types'); ?></h3>
                        <div class="team_create_detail_paragraph"><?php _e('TODO: Add in our accepted credit cards logo'); ?></div>
                    </div>
                	<div class="team_help_section">
                    	<h3><?php _e('Secure payments'); ?></h3>
                        <div class="team_create_detail_paragraph"><?php _e('Payments are secured by using SSL and encryption right in your browser.'); ?></div>
                    </div>
                </div>
            </div>
            <?php
			break;
		}
        case 'teaming_members':{
            $teams = TDOTeamAccount::getAdministeredTeamsForAdmin($userid);
            if(is_array($teams)){
                $team = $teams[0];
            }
			if ($team):
				$teamid = $team->getTeamID();
            $is_admin = TDOTeamAccount::isAdminForTeam($userid, $teamid);
            $is_team_member = TDOTeamAccount::isMemberOfTeam($userid, $teamid);
            $invitedLicenseCount = 0;
            $usedLicenseCount = 0;
            $team_members = TDOTeamAccount::getTeamMemberInfo($userid, $teamid);
            $team_members_count = sizeof($team_members);

            $billingUserID = $team->getBillingUserID();

            $invited_users = TDOTeamAccount::getTeamInvitationInfo($userid, $teamid, TEAM_MEMBERSHIP_TYPE_MEMBER);
            if ($invited_users) {
                $invitedLicenseCount = count($invited_users);
            }
            $team_status = TDOTeamAccount::getTeamSubscriptionStatus($teamid);

            $memberInfos = TDOTeamAccount::getTeamMemberInfo($userid, $teamid);
            if ($memberInfos) {
                $usedLicenseCount = count($memberInfos);
            }
//            if(($usedLicenseCount <= 1 && $invitedLicenseCount === 0) && $_COOKIE['hide_coach'] !== 'true') :
            ?>
    <!--<section class="setup-coach-wrapper">
        <h1><?php _e('Add people to your team.'); ?></h1>
        <ul class="steps">
            <li class="done">
                <span>1</span>
                Create Your Team
            </li>
            <li class="current">
                <span>2</span>
                Invite Team Members
            </li>
            <li class="next">
                <span>3</span>
                Share Team Lists
            </li>
            <li class="last next">
                <span class="fa fa-check"></span>
            </li>
        </ul>
    </section>-->
        <?php
//            endif;
//    if(($usedLicenseCount > 1 || $invitedLicenseCount > 0) && $_COOKIE['hide_coach'] !== 'true') :
    ?>
    <!--<section class="setup-coach-wrapper">
        <h1><?php _e('Share lists with your team.'); ?></h1>
        <ul class="steps">
            <li class="done">
                <span>1</span>
                Create Your Team
            </li>
            <li class="done">
                <span>2</span>
                Invite Team Members
            </li>
            <li class="current">
                <span>3</span>
                Share Team Lists
            </li>
            <li class="last next">
                <span class="fa fa-check"></span>
            </li>
        </ul>
    </section>-->
<?php //endif; ?>
    <div id="team-members-content">
        <div class="pull-left">
            <h2><?php _e('Manage Your Team'); ?></h2>
        </div>
        <div class="pull-right">
            <?php if ($is_admin && !$is_team_member) : ?>
            <button class="btn-default btn-green btn-size-sm btn-add-myself-to-the-team"><?php _e('Add Myself To The Team'); ?></button>
            <?php endif; ?>
            <button class="btn-default btn-green btn-size-sm btn-invite-new-member"><?php _e('Invite New Members'); ?></button>
            <div class="<?php echo ($is_admin && !$is_team_member) ? 'text-right' : 'text-center'; ?>"><a href="/?appSettings=show&option=teaming&action=teaming_billing"><?php _e('Add licenses'); ?></a></div>
        </div>
        <div class="clearfix"></div>
        <div class="tabs-container">
            <ul class="tabs">
                <li class="tab-link current" data-tab="members"><?php printf(_('Members (%s)'), $usedLicenseCount); ?></li>
                <li class="tab-link" data-tab="invited-members"><?php printf(_('Invited Members  %s'), $invitedLicenseCount); ?></li>
            </ul>

            <div id="members" class="tab-content current container-tb clearfix">
                <p class="info"><?php printf(_('%s of %s licenses in use. %sAdd more licenses%s'), '<strong>' . $usedLicenseCount . '</strong>', '<strong>' . $team->getNewLicenseCount() . '</strong>', '<a href="/?appSettings=show&option=teaming&action=teaming_billing">', '</a>'); ?></p>
                <br/>
                <section class="item-list">
                    <?php if($team_members) : ?>
                        <div class="clearfix b-border list-title">
                            <div class="col-sm-6"><?php _e('Name'); ?></div>
                            <div class="col-sm-6 text-right"><?php _e('Last Sync'); ?></div>
                        </div>
                        <?php
                        foreach ($team_members as $user) :
                            $user_name = $user['displayName'];
							$lastSyncActivity = _('never');
						  if (!empty($user['lastSyncActivityTimestamp']) && $user['lastSyncActivityTimestamp'] > 0)
						  {
							  $lastSyncActivity = date('D, M d, Y',$user['lastSyncActivityTimestamp']);
						  }
						  ?>
                            <div class="item clearfix">
                                <div class="user-name col-sm-6"><span><?php echo $user_name; ?></span></div>
							    <div class="user-status col-sm-6 text-right"><?php echo $lastSyncActivity; ?> <span class="fa fa-caret-down fa-fw view-actions"></span></div>
                                <div class="item-actions collapse clearfix">
                                    <div class="action-secondary col-sm-12 text-right">
                                        <a href="#" class="btn-remove-user-modal" data-user-id="<?php echo $user['userid']; ?>"><?php _e('Remove from team'); ?></a>
                                    </div>
                                </div>
                            </div>
                            <?php
                        endforeach;
                    endif; ?>
                </section>
            </div>
            <div id="invited-members" class="tab-content container-tb clearfix">
                <p class="info"><?php printf(_('%s of %s licenses in use. %sAdd more licenses%s'), '<strong>' . $usedLicenseCount . '</strong>', '<strong>' . $team->getNewLicenseCount() . '</strong>', '<a href="/?appSettings=show&option=teaming&action=teaming_billing">', '</a>'); ?></p>
                <br/>
                <p class="info"><?php _e('New members will automatically be added to team&#39;s main shared list.'); ?>
                    <a href="/?appSettings=show&option=teaming&action=teaming_lists"><?php _e('Manage shared lists.'); ?></a>
                </p>
                <br/>
                <section class="item-list">
                    <?php if($invited_users) : ?>
                        <div class="clearfix b-border list-title">
                            <div class="col-sm-6"><?php _e('Name'); ?></div>
                            <div class="col-sm-6 text-right"><?php _e('Invited on'); ?></div>
                        </div>
                        <?php
                        foreach ($invited_users as $invite) : ?>
                            <div class="item clearfix">
                                <div class="user-name col-sm-6"><span class="btn-resend-invitation-modal" data-invitation="<?php echo $invite['invitationid']; ?>"><?php echo $invite['email']; ?></span></div>
                                <div class="user-status col-sm-6 text-right"><?php echo _(date('D', $invite['timestamp'])) . ', ' . _(date('M', $invite['timestamp'])) . date(' d, Y',$invite['timestamp']);?> <span class="fa fa-caret-down fa-fw view-actions"></span></div>
                                <div class="item-actions collapse clearfix">
                                    <div class="action-main col-sm-6">
                                        <button class="btn-default btn-size-sm btn-resend-invitation progress-button" data-invitation="<?php echo $invite['invitationid']; ?>"
                                        data-method="resendTeamInvitation" data-membershiptype="<?php echo TEAM_MEMBERSHIP_TYPE_ADMIN; ?>"
                                        ><?php _e('Resend Invitation'); ?></button>
                                    </div>
                                <?php if ($team_status !== TEAM_SUBSCRIPTION_STATE_EXPIRED && $team_status !== TEAM_SUBSCRIPTION_STATE_GRACE_PERIOD) : ?>
                                    <div class="action-secondary col-sm-6 text-right"><a href="#" class="btn-delete-invitation-modal" data-invitation="<?php echo $invite['invitationid']; ?>"><?php _e('Delete this Invitation'); ?></a></div>
                                <?php endif; ?>
                                </div>
                            </div>
                            <?php
                        endforeach;
                    endif; ?>
                </section>
            </div>
        </div>
        <div class="invite-members-modal-wrapper hidden">
            <div class="modal-header"><?php _e('Invite Members'); ?></div>
            <div class="modal-content">
                <div class="form-wrapper todo-for-business invite-members-wrapper clearfix" data-available-license-count="<?php echo intval($team->getNewLicenseCount() - $usedLicenseCount); ?>">
                    <form action="#" method="POST" data-progress="true" class="form-invite-persone active primary">
                        <p class="info">
                            <?php _e('New members will automatically be added to team&#39;s main shared list.'); ?>
                            <a href="/?appSettings=show&option=teaming&action=teaming_lists"><?php _e('Manage shared lists'); ?>.</a>
                        </p>
                        <input type="hidden" name="jsaction" value="submitInviteMembersFormModal">
                        <input type="hidden" name="method" value="inviteTeamMember">
                        <input type="hidden" name="teamid" value="<?php echo $teamid; ?>">
                        <input type="hidden" name="memberType" value="<?php echo TEAM_MEMBERSHIP_TYPE_MEMBER; ?>">
                        <div class="col-sm-12 field-row">
                            <label><?php _e('Email Address'); ?></label>
                            <div class="field-wrap">
                                <input type="email" class="form-field required" name="email[]" value="" placeholder="<?php _e('Email'); ?>" required autofocus>
                                <i class="fa fa-times btn-remove-row hidden"></i>
                            </div>
                        </div>
                    </form>
                    <form action="#" method="POST" data-progress="true" class="form-invite-group collapse">
                        <p class="info">
                            <?php _e('New members will automatically be added to team&#39;s main shared list.'); ?>
                            <a href="/?appSettings=show&option=teaming&action=teaming_lists"><?php _e('Manage shared lists.'); ?></a>
                        </p>
                        <input type="hidden" name="jsaction" value="submitInviteMembersFormModal">
                        <input type="hidden" name="method" value="inviteTeamMember">
                        <input type="hidden" name="teamid" value="<?php echo $teamid; ?>">
                        <input type="hidden" name="memberType" value="<?php echo TEAM_MEMBERSHIP_TYPE_MEMBER; ?>">
                        <textarea name="email" id="" class="required email" cols="30" rows="10" placeholder="<?php _e('Enter multiply email addresses'); ?>"></textarea>
                        <p class="info small"><?php _e('Place multiply email addresses one per line.'); ?></p>
                    </form>
                    <form action="#" class="more-licenses-wrapper collapse">
                        <input type="hidden" name="method" value="getTeamChangePricingInfo">
                        <input type="hidden" name="teamID" value="<?php echo $teamid; ?>">
                        <input type="hidden" name="billingFrequency" value="<?php echo $team->getBillingFrequency(); ?>">
                        <input type="hidden" name="numOfTeamMembers" value="">

                        <input type="hidden" id="orig_current_license_count" name="orig_current_license_count" value="<?php echo $team->getLicenseCount(); ?>"/>
                        <input type="hidden" id="orig_new_license_count" name="orig_new_license_count" value="<?php echo $team->getNewLicenseCount();?>"/>
                        <input type="hidden" id="orig_billing_frequency" name="orig_billing_frequency" value="<?php echo $team->getBillingFrequency(); ?>"/>
                        <input type="hidden" id="orig_expiration_date" name="orig_expiration_date" value="<?php echo $team->getExpirationDate(); ?>"/>

                        <div class="col-sm-12">
                            <p class="info strong"><?php _e('Additional team licenses needed.'); ?></p>
                            <p class="info"><?php _e('All the licenses on your team are being used, but it&#39;s easy to add more.'); ?></p>
                            <div class="col-sm-6 text-right"><?php _e('Current Licenses'); ?></div>
                            <div class="col-sm-6 current-licenses-count"><?php echo $team->getLicenseCount(); ?></div>
                            <div class="col-sm-6 text-right"><?php _e('Licenses to Add'); ?></div>
                            <div class="col-sm-6 licenses-to-add-count">
                                <span></span>
                                (<?php
                                echo ($team->getBillingFrequency() == SUBSCRIPTION_TYPE_YEAR)
                                    ? '$' . $systemSettingTeamYearlyPricePerUser . _('/year')
                                    : '$' . $systemSettingTeamMonthlyPricePerUser . _('/month');
                                ?><?php _e(' per user)'); ?></div>
                            <?php if ($team_status !== TEAM_SUBSCRIPTION_STATE_TRIAL_PERIOD) : ?>
                            <div class="col-sm-6 text-right"><?php _e('To be charged now (pro-rated)'); ?></div>
                            <div class="col-sm-6 to-be-charged-count"></div>
                            <?php endif; ?>
                            <div class="clearfix"></div>

                            <br>
                            <?php if ($team_status !== TEAM_SUBSCRIPTION_STATE_TRIAL_PERIOD) : ?>
                            <div id="billing_section" class="">
                                <div class="row clearfix">
                                    <div class="col-sm-6">
                                        <label id="ccard_number_label" class="team_create_input_label" for="ccard_number"><?php _e('Credit card number'); ?></label>
                                        <input id="ccard_number" class="team_create_input_text" type="text" maxlength="127" autocomplete="off" />
                                    </div>
                                    <div class="col-sm-6">
                                        <label id="ccard_name_label" class="team_create_input_label" for="ccard_name"><?php _e('Name on card'); ?></label>
                                        <input id="ccard_name" class="team_create_input_text" type="text" maxlength="127" autocomplete="off" />
                                    </div>
                                </div>
                                <div class="clearfix">
                                    <div class="col-sm-4">
                                        <label id="ccard_cvc_label" class="team_create_input_label" for="ccard_cvc"><?php _e('Security code (CVC)'); ?></label>
                                        <input id="ccard_cvc" class="team_create_input_text" type="text" maxlength="4" autocomplete="off" />
                                    </div>
                                    <div class="col-sm-4">
                                        <label id="ccard_month_label" class="team_create_input_label" for="ccard_month"><?php _e('Expiry month'); ?></label>
                                        <select id="ccard_month" data-encrypted-name="ccard_month">
                                            <?php
                                            $curMonth = intval(date('n'));
                                            for($i = 1; $i <= 12; $i++) : ?>
                                            <option value="<?php echo $i; ?>" <?php echo ($curMonth === $i)?'selected':''; ?>><?php echo sprintf("%02d", $i); ?> - <?php echo _(date("F", mktime(0, 0, 0, $i, 10))); ?></option>
                                            <?php endfor; ?>
                                        </select>
                                    </div>
                                    <div class="col-sm-4">
                                        <label id="ccard_year_label" class="team_create_input_label" for="ccard_year"><?php _e('Expiry year'); ?></label>
                                        <select id="ccard_year" data-encrypted-name="ccard_year">
                                            <?php

                                            $currentYear = (int)date('Y');
                                            $endYear = $currentYear + 10;

                                            for ($i = $currentYear; $i < $endYear; $i++)
                                            {
                                                if ($currentYear == $i)
                                                    echo '<option value="' . $i . '" selected="selected">' . $i . '</option>';
                                                else
                                                    echo '<option value="' . $i . '">' . $i . '</option>';
                                            }

                                            ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                            <div><input id="stripe_token" type="hidden" value=""/></div>

                        </div>
                    </form>
                    <form action="#" class="confirm-purchase collapse">
                        <input type="hidden" name="method" value="changeTeamAccount">
                        <input type="hidden" name="teamID" value="<?php echo $teamid; ?>">
                        <input type="hidden" name="billingFrequency" value="<?php echo $team->getBillingFrequency(); ?>">
                        <input type="hidden" name="stripeToken" value="">
                        <input type="hidden" name="numOfMembers" value="">
                        <h2><?php _e('Change Summary:'); ?></h2>
                        <div class="col-sm-6 text-right "><?php _e('Number of Team Members'); ?></div>
                        <div class="col-sm-6 num-of-members"></div>
                        <div class="col-sm-6 text-right"><?php _e('Billing Type'); ?></div>
                        <div class="col-sm-6 billing-type"></div>

                        <?php if ($team_status !== TEAM_SUBSCRIPTION_STATE_TRIAL_PERIOD) : ?>
                        <div class="col-sm-6 text-right"><?php _e('Total'); ?></div>
                        <div class="col-sm-6 total-price"></div>
                        <p class="info"><?php _e('Make purchase'); ?></p>
                        <?php endif; ?>

                        <div class="clearfix"></div>
                    </form>
                </div>
                <div class="todo-for-business-add-line-user col-sm-4">
                    <a href="#"><i class="fa fa-fw fa-plus"></i><?php _e('Add another member'); ?></a>
                </div>
                <div class="btn-lots-of-people col-sm-4 col-sm-4-offset pull-right text-right">
                    <a href="#" class="btn-lots-of-people"><?php _e('Invite lots of people at once'); ?></a>
                </div>
            </div>
            <div class="modal-footer">
                <div class="team_validation_error_message pull-left"></div>
                <div class="button btn-invite progress-button <?php echo ($team_status === TEAM_SUBSCRIPTION_STATE_TRIAL_PERIOD) ? 'trial' : '';?>" data-new-licenses-count="0" data-loading="<?php _e('Sending...'); ?>" data-finished="<?php _e('Done'); ?>">
                    <span class="invite"><span><?php _e('Invite');?> <span class="reg-count">1</span> <span class="reg-one"><?php _e('person'); ?></span><span class="reg-many hidden"> <?php _e('People'); ?></span></span></span>
                    <span class="need-more-licenses collapse"><?php _e('More Licenses Needed'); ?></span>
                    <span class="licenses collapse"><?php _e('Review Changes'); ?></span>
                    <span class="make-purchase collapse"><?php _e('Purchase'); ?></span>
                </div>
                <div class="button btn-cancel-modal"><?php _e('Cancel'); ?></div>
            </div>
        </div>
        <div class="resend-invitation-wrapper hidden">
            <div class="modal-header">
                <?php _e('Resend Invitation'); ?>
            </div>
            <div class="modal-content">
                <form action="#" method="POST" data-progress="true">
                    <input type="hidden" name="method" value="resendTeamInvitation">
                    <input type="hidden" name="invitationID" value="">
                    <input type="hidden" name="membershipType" value="<?php echo TEAM_MEMBERSHIP_TYPE_ADMIN; ?>">
                </form>
                <p class="info"><?php _e('Are you sure you&#39;d like to resend this invitation?'); ?></p>
            </div>
            <div class="modal-footer">
                <div class="button btn-resend-invitation progress-button" data-loading="<?php _e('Sending...'); ?>" data-finished="<?php _e('Done'); ?>"><?php _e('Resend invitation'); ?></div>
                <div class="button btn-cancel-modal"><?php _e('Cancel'); ?></div>
            </div>
        </div>
        <div class="delete-invitation-wrapper hidden">
            <div class="modal-header"><?php _e('Delete Invitation'); ?></div>
            <div class="modal-content">
                <form action="#" method="POST" data-progress="true">
                    <input type="hidden" name="method" value="deleteTeamInvitation">
                    <input type="hidden" name="invitationID" value="">
                </form>
                <p class="info"><?php _e('Are you sure you&#39;d like to delete this invitation?'); ?></p>
            </div>
            <div class="modal-footer">
                <div class="button btn-delete-invitation"><?php _e('Delete'); ?></div>
                <div class="button btn-cancel-modal"><?php _e('Cancel'); ?></div>
            </div>
        </div>
        <div class="delete-user-wrapper hidden">
            <div class="modal-header"><?php _e('Remove Member'); ?></div>
            <div class="modal-content">
                <form action="#" method="POST">
                    <input type="hidden" name="method" value="removeTeamMember">
                    <input type="hidden" name="teamID" value="<?php echo $teamid; ?>">
                    <input type="hidden" name="membershipType" value="<?php echo TEAM_MEMBERSHIP_TYPE_MEMBER; ?>">
                    <input type="hidden" name="userID" value="">
                </form>
                <p class="info"><?php _e('Are you sure you&#39;d like to remove this member?'); ?></p>
            </div>
            <div class="modal-footer">
                <div class="button btn-delete-administrator progress-button"  data-loading="<?php _e('Deleting...'); ?>" data-finished="<?php _e('Done'); ?>"><?php _e('Delete'); ?></div>
                <div class="button btn-cancel-modal"><?php _e('Cancel'); ?></div>
            </div>
        </div>
        <div class="add-myself-to-the-team-modal hidden">
            <div class="modal-header"><?php _e('Add Myself To The Team'); ?></div>
            <div class="modal-content">
                <form action="#" method="POST">
                    <input type="hidden" name="method" value="addMyselfToTheTeam">
                    <input type="hidden" name="teamID" value="<?php echo $teamid; ?>">
                </form>
                <p class="info"><?php _e('Do you want to join this team as a member?'); ?></p>
            </div>
            <div class="modal-footer">
                <div class="button btn-add-myself-to-the-team progress-button"  data-loading="<?php _e('Join'); ?>" data-finished="<?php _e('Done'); ?>"><?php _e('Join'); ?></div>
                <div class="button btn-cancel-modal"><?php _e('Cancel'); ?></div>
            </div>
        </div>
    </div>

            <?php
            endif;
            break;
        }
        case 'teaming_lists': {
            $teams = TDOTeamAccount::getAdministeredTeamsForAdmin($userid);
            $teamid = '';
            if(is_array($teams)){
                $team = $teams[0];
            }
            if ($team){
                $teamid = $team->getTeamID();
            }
            $team_status = TDOTeamAccount::getTeamSubscriptionStatus($teamid);
            $team_list_activity_blocked = FALSE;
            $all_lists = TDOList::getSharedListsForTeam($teamid, TRUE);
            $lists = array();
            $lists_deleted = array();
            $main_listid = TDOTeamAccount::getMainListIDForTeam($teamid);
            $main_list_name = '';
            $total_member_count = 0;
            $members_count_in_secondary_lists = 0;
            if ($team_status !== TEAM_SUBSCRIPTION_STATE_EXPIRED && $team_status !== TEAM_SUBSCRIPTION_STATE_GRACE_PERIOD) {
                foreach ($all_lists as $list) {
                    $total_member_count += $list->getMembersCount();
                    if (!$list->deleted()) {
                        $lists[] = $list;
                    }else{
                        $lists_deleted[] = $list;
                    }
                    if($list->listId() === $main_listid && !$list->deleted()){
                        $main_list_name = $list->name();
                    }else{
                        $members_count_in_secondary_lists += $list->getMembersCount();
                    }
                }
            } else {
                $team_list_activity_blocked = TRUE;
            }

//    if(sizeof($all_lists) - 1 == 0 && $members_count_in_secondary_lists == 0 && $_COOKIE['hide_coach'] !== 'true') :
        ?>
    <!--<section class="setup-coach-wrapper">
        <h1><?php _e('Share lists with your team.'); ?></h1>
        <ul class="steps">
            <li class="done">
                <span>1</span>
                Create Your Team
            </li>
            <li class="done">
                <span>2</span>
                Invite Team Members
            </li>
            <li class="current">
                <span>3</span>
                Share Team Lists
            </li>
            <li class="last next">
                <span class="fa fa-check"></span>
            </li>
        </ul>
    </section>-->
    <?php
//endif;
//        if (sizeof($all_lists) - 1 > 1 && $members_count_in_secondary_lists > 0 && $_COOKIE['hide_coach'] !== 'true') :
    ?>
    <!--<section class="setup-coach-wrapper">
        <h1><?php _e('Way to go'); ?></h1>
        <ul class="steps">
            <li class="done">
                <span>1</span>
                Create Your Team
            </li>
            <li class="done">
                <span>2</span>
                Add people to your team.
            </li>
            <li class="done">
                <span>3</span>
                Share lists with your team.
            </li>
            <li class="last done">
                <span class="fa fa-check"></span>
                You're <br/>all set!
            </li>
        </ul>
    </section>-->
        <?php //endif; ?>
    <div id="team-shared-lists">
        <div class="pull-left">
            <h2><?php _e('Your Team&#39;s Shared Lists'); ?></h2>
        </div>
        <?php if(!$team_list_activity_blocked) : ?>
        <div class="pull-right">
            <button class="btn-default btn-green btn-size-sm btn-create-new-shared-list"><?php _e('Create New Shared List'); ?></button>
        </div>
        <?php endif; ?>
        <div class="clearfix"></div>
        <div class="tabs-container">
            <ul class="tabs">
                <li class="tab-link current" data-tab="shared-lists"><?php _e('Shared Lists'); ?> (<?php echo sizeof($lists); ?>)</li>
                <?php
                /**
                 * https://github.com/Appigo/todo-issues/issues/1280
                 * We should not show the Deleted Lists tab on the Shared Lists screen in the product.
                 *
                <li class="tab-link" data-tab="deleted-lists">Deleted Lists (<?php echo sizeof($lists_deleted); ?>)</li>
                 *
                 */?>
            </ul>

            <div id="shared-lists" class="tab-content current container-tb clearfix">
                <p class="info">
                    <?php if (!$team_list_activity_blocked): ?>
                    <?php _e('Share tasks with members of your team by creating a shared list.'); ?>
                    <?php if($main_list_name) : ?>
                    <?php _e('New members will automatically be added to your team&#39;s main shared list'); ?> (<strong><?php echo $main_list_name;?></strong>).
					<br/>* <?php _e('Indicates your team&#39;s main shared list.'); ?>
                    <?php endif; ?>
                    <?php else : ?>
                    <?php _e('Team subscription has expired.'); ?>
                    <?php endif; ?>
                </p>
                <br/>
                <section class="item-list">
                    <?php if($lists) : ?>
                        <div class="clearfix b-border list-title">
                            <div class="col-sm-6"><?php _e('List Name'); ?></div>
                            <div class="col-sm-2"><?php _e('Members'); ?></div>
                            <div class="col-sm-2"><?php _e('Active Tasks'); ?></div>
                            <div class="col-sm-2"></div>
                        </div>
                        <?php
                        foreach ($lists as $list) :
                            $list_id = $list->listId();
                            ?>
                            <div class="item clearfix">
                                <div class="col-sm-6"><span class="do-main-action"><?php echo $list->name(); ?><?php echo ($list_id === $main_listid)?'*':''?></span></div>
                                <div class="col-sm-2"> <?php echo $list->getMembersCount(); ?> </div>
                                <div class="col-sm-2"> <?php echo $list->getTaskCount(); ?> </div>
                                <div class="col-sm-2 text-right"> <span class="fa fa-caret-down fa-fw view-actions"></span></div>
                                <div class="clearfix"></div>
                                <div class="item-actions collapse clearfix">
                                    <div class="action-main col-sm-6"><a href="/?appSettings=show&option=teaming&action=teaming_list_members&list_id=<?php echo $list_id; ?>" class="btn-default btn-size-sm"><?php _e('Manage Members'); ?></a></div>
                                    <div class="action-secondary col-sm-6 text-right">
                                        <a href="#" class="btn-rename-list-modal" data-list-id="<?php echo $list_id; ?>" data-list-name="<?php echo $list->name(); ?>"><?php _e('Rename this list'); ?></a>
                                        <a href="#" class="btn-delete-list-modal" data-list-id="<?php echo $list_id; ?>" data-list-name="<?php echo $list->name(); ?>"><?php _e('Delete this list'); ?></a>
                                    </div>
                                </div>
                            </div>
                            <?php
                        endforeach;
                    endif; ?>
                </section>
            </div>
            <?php 
            /**
             * https://github.com/Appigo/todo-issues/issues/1280
             * We should not show the Deleted Lists tab on the Shared Lists screen in the product.
             *
            <div id="deleted-lists" class="tab-content container-tb clearfix">
                <p class="info">
                    Deleted Lists.
                </p>
                <br/>
                <section class="item-list">
                    <?php if($lists_deleted) : ?>
                        <div class="clearfix b-border list-title">
                            <div class="col-sm-6">List Name</div>
                            <div class="col-sm-2">Members</div>
                            <div class="col-sm-2">Active Tasks</div>
                            <div class="col-sm-2"></div>
                        </div>
                        <?php
                        foreach ($lists_deleted as $list) :
                            $list_id = $list->listId();
                            ?>
                            <div class="item clearfix">
                                <div class="col-sm-6"> <?php echo $list->name(); ?> </div>
                                <div class="col-sm-2"> <?php echo $list->getMembersCount(); ?> </div>
                                <div class="col-sm-2"> <?php echo $list->getTaskCount(); ?> </div>
                                <div class="col-sm-2 text-right"> <span class="fa fa-caret-down fa-fw view-actions"></span></div>
                                <div class="clearfix"></div>
                                <div class="item-actions collapse clearfix">
                                    <div class="action-main col-sm-6"><a href="/?appSettings=show&option=teaming&action=teaming_list_members&list_id=<?php echo $list_id; ?>" class="btn-default btn-size-sm">Manage Members</a></div>
                                    <div class="action-secondary col-sm-6 text-right">
                                        <a href="#" class="btn-delete-list-modal" data-list-id="<?php echo $list_id; ?>" data-list-name="<?php echo $list->name(); ?>">Delete this list</a>
                                    </div>
                                </div>
                            </div>
                            <?php
                        endforeach;
                    endif; ?>
                </section>
            </div>
            */
            ?>
            <div class="create-shared-list-wrapper hidden">
                <div class="modal-header"><?php _e('Create Shared List'); ?></div>
                <div class="modal-content">
                    <form action="#" method="POST" data-progress="true" class="form-full-width">
                        <input type="hidden" name="jsaction" value="createSharedList">
                        <input type="hidden" name="method" value="createSharedList">
                        <input type="hidden" name="teamId" value="<?php echo $teamid; ?>">
                        <input type="text" name="listName" value="" placeholder="<?php _e('List Name'); ?>" required autofocus>
                    </form>
                </div>
                <div class="modal-footer">
                    <div class="button btn-create-list progress-button" data-loading="<?php _e('Sending...'); ?>" data-finished="<?php _e('Done'); ?>"><?php _e('Create'); ?></div>
                    <div class="button btn-cancel-modal"><?php _e('Cancel'); ?></div>
                </div>
            </div>
            <div class="rename-shared-list-wrapper hidden">
                <div class="modal-header">
                    <?php _e('Rename'); ?> "<span class="current-name"></span>"
                </div>
                <div class="modal-content">
                    <form action="#" method="POST" data-progress="true" class="form-full-width">
                        <input type="hidden" name="jsaction" value="updateSharedList">
                        <input type="hidden" name="method" value="updateList">
                        <input type="hidden" name="listid" value="">
                        <input type="text" name="listname" value="" placeholder="<?php _e('List Name'); ?>">
                    </form>
                </div>
                <div class="modal-footer">
                    <div class="button btn-update-list progress-button" data-loading="<?php _e('Sending...'); ?>" data-finished="<?php _e('Done'); ?>"><?php _e('Save'); ?></div>
                    <div class="button btn-cancel-modal"><?php _e('Cancel'); ?></div>
                </div>
            </div>
            <div class="delete-shared-list-wrapper hidden">
                <div class="modal-header">
                    <?php _e('Delete'); ?> "<span class="current-name"></span>"
                </div>
                <div class="modal-content">
                    <form action="#" method="POST" data-progress="true" class="form-full-width">
                        <input type="hidden" name="jsaction" value="updateSharedList">
                        <input type="hidden" name="method" value="deleteList">
                        <input type="hidden" name="listid" value="">
                        <p class="info"><?php _e('Are you sure you want to permanently delete this list and all of its tasks?'); ?></p>
                    </form>
                </div>
                <div class="modal-footer">
                    <div class="button btn-delete-list progress-button" data-loading="<?php _e('Deleting...'); ?>" data-finished="<?php _e('Done'); ?>"><?php _e('Delete'); ?></div>
                    <a href="#" class="button btn-manage-members"><?php _e('Manage Members'); ?></a>
                    <div class="button btn-cancel-modal"><?php _e('Cancel'); ?></div>
                </div>
            </div>

        </div>
    </div>
        <?php
        break;
        }
        case 'teaming_list_members': {
            $list_id = trim($_GET['list_id']);
            $list = TDOList::getListForListid($list_id);
            if($list) :
                $list_members = TDOList::getMembersByListId($list_id);
                $team_members = TDOTeamAccount::getTeamMemberInfo($userid, $list->creator());
                $list_members_ids = array();
                $owners_count = 0;

                //create array of IDs users that already in list
                foreach ($list_members as $member_item) {
                    $member = $member_item['user'];
                    $list_members_ids[] = $member->userid();
                    if ($member_item['membership_type'] == LIST_MEMBERSHIP_OWNER) {
                        $owners_count++;
                    }
                }
                if (sizeof($list_members_ids)) {
                    //if user already is member of list we need to remove him from array
                    foreach ($team_members as $key => $member) {
                        if (in_array($member['userid'], $list_members_ids)) {
                            unset($team_members[$key]);
                        }
                    }
                }

//if(sizeof($list_members_ids) <= 1 && $_COOKIE['hide_coach'] !== 'true') :
    ?>
    <!--<section class="setup-coach-wrapper">
        <h1>Share lists with your team.</h1>
        <ul class="steps">
            <li class="done">
                <span>1</span>
                Create Your Team
            </li>
            <li class="done">
                <span>2</span>
                Invite Team Members
            </li>
            <li class="current">
                <span>3</span>
                Share Team Lists
            </li>
            <li class="last next">
                <span class="fa fa-check"></span>
            </li>
        </ul>
    </section>-->
<?php //endif;
//if (sizeof($list_members_ids) > 1 && $_COOKIE['hide_coach'] !== 'true') :
    ?>
    <!--<section class="setup-coach-wrapper">
        <h1>Way to go</h1>
        <ul class="steps">
            <li class="done">
                <span>1</span>
                Create Your Team
            </li>
            <li class="done">
                <span>2</span>
                Add people to your team.
            </li>
            <li class="done">
                <span>3</span>
                Share lists with your team.
            </li>
            <li class="last done">
                <span class="fa fa-check"></span>
                You're <br/>all set!
            </li>
        </ul>
    </section>-->
<?php //endif;?>
    <div id="team-shared-list-members">
        <div class="pull-left">
            <a href="/?appSettings=show&option=teaming&action=teaming_lists" class="pull-left"><h2><?php _e('Your Team&#39;s Shared Lists'); ?></h2></a>
            <h2 class="pull-left">&nbsp;/&nbsp;</h2>
            <h2 class="pull-left"><?php _e('List:');?> <strong><?php echo $list->name(); ?></strong></h2>
            <div class="clearfix"></div>
        </div>
        <div class="pull-right">
            <button class="btn-default btn-green btn-size-sm btn-add-members-to-list"><?php _e('Add Members'); ?></button>
        </div>
        <div class="clearfix"></div>
        <div class="tabs-container">
            <ul class="tabs">
                <li class="tab-link current" data-tab="members-list"><?php _e('Members'); ?> (<?php echo $list->getMembersCount(); ?>)</li>
            </ul>
            <div id="members-list" class="tab-content current container-tb clearfix">
                <p class="info"><?php _e('List:'); ?> <strong><?php echo $list->name(); ?></strong></p>
                <br/>
                <section class="item-list">
                    <?php if($list_members) : ?>
                        <div class="clearfix b-border list-title">
                            <div class="col-sm-8"><?php _e('Name'); ?></div>
                            <div class="col-sm-4 text-right"><?php _e('Status'); ?></div>
                        </div>
                        <?php
                        foreach ($list_members as $member_item) :
                            $member = $member_item['user'];
                            $member_type = _('Viewer');
                            if ($member_item['membership_type'] == LIST_MEMBERSHIP_MEMBER) {
                                $member_type = _('Member');
                            } elseif ($member_item['membership_type'] == LIST_MEMBERSHIP_OWNER) {
                                $member_type = _('Owner');
                            }
                            ?>
                            <div class="item clearfix">
                                <div class="col-sm-8"><span <?php echo (($member_item['membership_type'] == LIST_MEMBERSHIP_OWNER && $owners_count > 1) || $member_item['membership_type'] == LIST_MEMBERSHIP_MEMBER)?'class="do-main-action"':'' ;?>><?php echo $member->displayName(); ?></span></div>
                                <div class="col-sm-4 text-right"><?php echo $member_type; ?> <span class="fa fa-caret-down fa-fw view-actions"></span></div>
                                <div class="clearfix"></div>
                                <div class="item-actions collapse clearfix">
                                    <div class="action-main col-sm-6">
                                        <?php if (($member_item['membership_type'] == LIST_MEMBERSHIP_OWNER && $owners_count > 1) || $member_item['membership_type'] == LIST_MEMBERSHIP_MEMBER) : ?>
                                        <button
                                            class="btn-default btn-size-sm btn-change-member-role-modal"
                                            data-role-id="<?php echo ($member_item['membership_type'] == LIST_MEMBERSHIP_OWNER) ? LIST_MEMBERSHIP_MEMBER : LIST_MEMBERSHIP_OWNER; ?>"
                                            data-role-name="<?php echo ($member_item['membership_type'] == LIST_MEMBERSHIP_OWNER) ? _('Member') : _('Owner'); ?>"
                                            data-user-id="<?php echo $member->userid(); ?>"
                                            ><?php _e('Make an'); ?> <?php echo ($member_item['membership_type'] == LIST_MEMBERSHIP_OWNER) ? _('Member') : _('Owner'); ?></button>
                                    <?php endif; ?>
                                    </div>
                                    <div class="action-secondary col-sm-6 text-right">
                                        <a href="#" class="btn-remove-list-member-modal" data-user-name="<?php echo $member->displayName(); ?>" data-user-id="<?php echo $member->userid(); ?>"><?php _e('Remove this member'); ?></a>
                                    </div>
                                </div>
                            </div>
                            <?php
                        endforeach;
                    else : ?>
                        <h3><?php _e('No members in current list'); ?></h3>
                    <?php
                    endif; ?>
                </section>
            </div>
        </div>
        <div class="add-members-to-list-wrapper hidden">
            <div class="modal-header">
                <?php _e('Add members to'); ?> "<?php echo $list->name(); ?>"
            </div>
            <div class="modal-content">
                <?php if ($team_members && sizeof($team_members)) : ?>
                <p class="info"><?php printf(_('Add members to the &quot%s&quot; shared list.'), $list->name()); ?></p>
                <form action="#" method="POST" data-progress="true" class="add-members-to-shared-list-form">
                    <input type="hidden" name="jsaction" value="addMembersToSharedList">
                    <input type="hidden" name="method" value="addMembersToSharedList">
                    <input type="hidden" name="listid" value="<?php echo $list_id; ?>">
                    <div class="team-members-checkbox-container">
                    <?php foreach($team_members as $member) : ?>
                        <label for="member-id-<?php echo $member['userid']; ?>">
                            <input type="checkbox" name="add_members[]" value="<?php echo $member['userid']; ?>" id="member-id-<?php echo $member['userid']; ?>">
                            <?php echo $member['displayName']; ?>
                        </label>
                    <?php endforeach; ?>
                    </div>
                    <a href="#" class="team-members-checkbox-select-all"><?php _e('Select All'); ?></a>
                    <a href="#" class="team-members-checkbox-deselect-all"><?php _e('Deselect All'); ?></a>
                </form>
                <?php else: ?>
                    <p class="info"><?php _e('Sorry, all team members already in list.'); ?></p>
                <?php endif; ?>
            </div>
            <div class="modal-footer">
                <div class="button btn-add-members-to-shared-list progress-button disabled" data-loading="<?php _e('Sending...'); ?>" data-finished="<?php _e('Done'); ?>"><?php _e('Add'); ?> <span class="add-count"></span> <?php _e('Member'); ?><span class="add-many hidden">s</span></div>
                <div class="button btn-cancel-modal"><?php _e('Cancel'); ?></div>
            </div>
        </div>
        <div class="remove-member-from-list-wrapper hidden">
            <div class="modal-header"><?php printf(_('Remove member from list &quot;%s&quot;'), $list->name()); ?></div>
            <div class="modal-content">
                <form action="#" method="POST" data-progress="true" class="form-full-width">
                    <input type="hidden" name="jsaction" value="removeMemberFromSharedList">
                    <input type="hidden" name="method" value="removeMemberFromSharedList">
                    <input type="hidden" name="listid" value="<?php echo $list_id; ?>">
                    <input type="hidden" name="userid" value="">
                    <p class="info"><?php printf(_('Are you sure you want to remove %s from list?'), '"<span class="user-name"></span>"'); ?></p>
                </form>
            </div>
            <div class="modal-footer">
                <div class="button btn-remove-shared-list-member progress-button" data-loading="<?php _e('Processing...'); ?>" data-finished="<?php _e('Done'); ?>"><?php _e('Remove'); ?></div>
                <div class="button btn-cancel-modal"><?php _e('Cancel'); ?></div>
            </div>
        </div>
        <div class="change-member-role-wrapper hidden">
            <div class="modal-header"><?php _e('Change member role'); ?></div>
            <div class="modal-content">
                <form action="#" method="POST" data-progress="true">
                    <input type="hidden" name="jsaction" value="changeMemberRole">
                    <input type="hidden" name="method" value="changeMemberRole">
                    <input type="hidden" name="listid" value="<?php echo $list_id; ?>">
                    <input type="hidden" name="userid" value="">
                    <input type="hidden" name="roleid" value="">
                    <p class="info"><?php printf(_('Are you sure you want to change role to %s ?'), '"<span class="role-name"></span>"'); ?></p>
                </form>
            </div>
            <div class="modal-footer">
                <div class="button btn-change-member-role progress-button" data-loading="<?php _e('Processing...'); ?>" data-finished="<?php _e('Done'); ?>"><?php _e('Update'); ?></div>
                <div class="button btn-cancel-modal"><?php _e('Cancel'); ?></div>
            </div>
        </div>
    </div>
            <?php
        endif;
            break;
        }
        case 'teaming_billing':{
            $teams = TDOTeamAccount::getAdministeredTeamsForAdmin($userid);
            $teamid = '';
            if(is_array($teams)){
                $team = $teams[0];
            }
            if ($team){
                $teamid = $team->getTeamID();
            }
            // Check to see if the session user is an administrator of the team. If they are not
            // output an error.
            if (!TDOTeamAccount::isAdminForTeam($userid, $teamid)) {
                echo '<div class="team_page_link"><a href="?appSettings=show&option=teaming">'._('Back to Team Owerview').'</a></div>';
                echo "<script>displayGlobalErrorMessage('"._('You are not authorized to administer the specified team.')."');</script>";
                break;
            }

            $billing_admin = $team->getBillingUserID();
            $team_status = TDOTeamAccount::getTeamSubscriptionStatus($teamid);

            $paidLicenses = $team->getLicenseCount();
            $currentLicenses = $team->getNewLicenseCount();
            $expirationDate = $team->getExpirationDate();
            $billingFrequency = $team->getBillingFrequency();
            $usedLicenseCount = 0;
            $memberInfos = TDOTeamAccount::getTeamMemberInfo($userid, $teamid);
            if ($memberInfos) {
                $usedLicenseCount = count($memberInfos);
            }
            $numOfActiveTeamCredits = intval(TDOTeamAccount::numOfActiveTeamCreditMonthsForTeam($teamid));
            ?>
    <div id="team-billing">
        <div class="tabs-container">
            <ul class="tabs">
                <li class="tab-link current" data-tab="billing-licenses"><?php _e('Licenses'); ?></li>
                <li class="tab-link" data-tab="billing-history"><?php _e('History'); ?></li>
            </ul>
            <div id="billing-licenses" class="tab-content current container-tb clearfix">
                <?php if ($billing_admin && $team_status > 0) : ?>
                <p class="info pull-left"><?php _e('Next auto-renewal date:');?> <?php echo _(date('F', $team->getExpirationDate())) . date(' d, Y', $team->getExpirationDate()); ?></p>
                <?php else : ?>
                    <?php if (!$billing_admin && $team_status > 0) { ?>
                <p class="info b-border"><?php _e('Team Subscription expire in:');?> <?php echo _(date('F', $team->getExpirationDate())) . date(' d, Y', $team->getExpirationDate()); ?></p>
                    <?php } else { ?>
                <p class="info pull-left"><?php _e('Team Subscription Expired'); ?></p>
                    <?php } ?>
                <?php endif; ?>
                <?php if(!$billing_admin) : ?>
                <div class="clearfix"></div>
                <h3><?php _e('Become the Team Billing Administrator'); ?></h3>
                <?php

                    // Check to see if the session user is an administrator of the team. If they are not
                    // output an error.
                    if (!TDOTeamAccount::isAdminForTeam($userid, $teamid)) : ?>
                    <p><?php _e('You are not authorized to administer the specified team.'); ?></p>
                    <?php
                    else :
                    ?>

                    <input type="hidden" id="teamID" value="<?php echo $teamid; ?>" />
                    <div class="team_create_detail_paragraph"><?php _e('After entering your credit card information, you will become the billing administrator. Your credit card will be billed at the next billing cycle.'); ?></div>
                    <div class="team_create_container row">
                        <div id="billing_section" class="col-sm-12 col-md-10 col-lg-6">
                            <h3 class="pull-left"><?php _e('Billing Information'); ?></h3>
                            <?php if ($team_status !== TEAM_SUBSCRIPTION_STATE_EXPIRED) : ?>
                                <h4 class="pull-right info-title"><?php _e('You won’t be charged now.'); ?></h4>
                            <?php endif; ?>
                            <div class="clearfix"></div>
                            <div class="row clearfix">
                                <div class="col-sm-6">
                                    <label id="ccard_number_label" class="team_create_input_label" for="ccard_number"><?php _e('Credit card number'); ?></label>
                                    <input id="ccard_number" class="team_create_input_text" type="text" value="" maxlength="127" name="ccard_number"  autocomplete="off" />
                                </div>
                                <div class="col-sm-6">
                                    <label id="ccard_name_label" class="team_create_input_label" for="ccard_name"><?php _e('Name on card'); ?></label>
                                    <input id="ccard_name" class="team_create_input_text" type="text" value="" maxlength="127" name="ccard_name" autocomplete="off" />
                                </div>
                            </div>
                            <div class="row clearfix">
                                <div class="col-sm-4">
                                    <label id="ccard_cvc_label" class="team_create_input_label" for="ccard_cvc"><?php _e('Security code (CVC)'); ?></label>
                                    <input id="ccard_cvc" class="team_create_input_text" type="text" value="" maxlength="4" name="ccard_cvc" autocomplete="off" />
                                </div>
                                <div class="col-sm-4">
                                    <label id="ccard_month_label" class="team_create_input_label" for="ccard_month"><?php _e('Expiry month'); ?></label>
                                    <select id="ccard_month" data-encrypted-name="ccard_month">
                                        <?php
                                        $curMonth = intval(date('n'));
                                        for($i = 1; $i <= 12; $i++) : ?>
                                            <option value="<?php echo $i; ?>" <?php echo ($curMonth === $i)?'selected':''; ?>><?php echo sprintf("%02d", $i); ?> - <?php echo _(date("F", mktime(0, 0, 0, $i, 10))); ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                <div class="col-sm-4">
                                    <label id="ccard_year_label" class="team_create_input_label" for="ccard_year"><?php _e('Expiry year'); ?></label>
                                    <select id="ccard_year" data-encrypted-name="ccard_year">
                                        <?php

                                        $currentYear = (int)date('Y');
                                        $endYear = $currentYear + 10;

                                        for ($i = $currentYear; $i < $endYear; $i++)
                                        {
                                            if ($currentYear == $i)
                                                echo '<option value="' . $i . '" selected="selected">' . $i . '</option>';
                                            else
                                                echo '<option value="' . $i . '">' . $i . '</option>';
                                        }

                                        ?>
                                    </select>
                                </div>
                            </div>
                            <div><input id="stripe_token" type="hidden" value=""/></div>


                        </div>
                        <!-- REVIEW CHANGES BUTTON -->

                        <div class="clearfix"></div>
                        <div id="review_changes_button_section" class="col-sm-12">
                            <div class="team_tos_agree_field">
                                <label id="team_tos_agree_label" for="team_tos_agree">
                                    <input id="team_tos_agree" type="checkbox"  name="team_tos_agree" onclick="enableUpdateBillingInfoButton(event, this);"/>
                                    <?php _e('I agree to the'); ?> <a href="/terms" target="_blank" title="<?php _e('Review the Terms of Service'); ?>"><?php _e(' Todo Cloud Terms of Service'); ?></a>.</label>
                            </div>

                            <div class="team_create_input_row">
                                <button id="team_purchase_button" class="btn-default btn-orange disabled "><?php _e('Update Billing Info'); ?></button>
                            </div>
                            <div class="team_create_input_row">
                                <div id="team_validate_error" class="team_validation_error_message width_full team_item_hidden"><?php _e('Please enter the missing information above indicated in red.'); ?></div>
                            </div>
                        </div>
                        <!-- RESULTS PLACEHOLDERS -->
                        <input type="hidden" id="team_change_new_expiration_date" value=""/>
                        <input type="hidden" id="team_change_new_num_of_members" value=""/>
                        <input type="hidden" id="team_change_billing_frequency" value=""/>
                        <input type="hidden" id="team_change_bulk_discount" value=""/>
                        <input type="hidden" id="team_change_discount_percentage" value=""/>
                        <input type="hidden" id="team_change_current_account_credit" value=""/>
                        <input type="hidden" id="team_change_total_charge" value=""/>
                    </div>
                <?php endif; ?>

                <?php endif; ?>

                <?php if ($billing_admin === $userid) : ?>
                <button class="btn-default btn-size-sm pull-right btn-cancel-subscription-modal"><?php _e('Cancel my team subscription'); ?></button>
                <?php endif; ?>
                <div class="clearfix"></div>
                <?php if ($billing_admin === $userid) : ?>

                <div class="col-lg-8 teaming-billing row">
                    <h2><?php _e('Modify Your Team'); ?></h2>
                    <div class="active-licenses-wrapper clearfix row">
                        <p class="info col-sm-4"><?php printf(_('Active licenses: %s of %s'), '<span class="current-count">' . $usedLicenseCount . '</span>', '<span class="max-count">' . $team->getNewLicenseCount() . '</span>'); ?></p>
                        <div class="col-sm-8">
                            <div class="progress-bar pull-left progress-sm"><div class="bar orange" data-bar-width="<?php echo round($usedLicenseCount / $team->getNewLicenseCount() * 100) ;?>"></div></div>
                        </div>
                    </div>

                    <input type="hidden" id="teamID" value="<?php echo $teamid; ?>" />
                    <div class="team_create_container">
                        <div class="team_create_section">
                            <div class="row clerfix">
                                <div class="col-sm-4">
                                    <label id="num_of_members_label" class="team_create_input_label" for="num_of_members"><?php _e('Number of members'); ?></label>
                                    <input id="num_of_members" class="team_create_input_text" type="text" maxlength="3" name="num_of_members"  autocomplete="off" onkeyup="updateModifyTeamPricing('<?php echo $paidLicenses; ?>', '<?php echo $currentLicenses; ?>', '<?php echo $expirationDate; ?>', '<?php echo $billingFrequency; ?>');" value="<?php echo $team->getNewLicenseCount(); ?>"/>
                                </div>
                                <div class="col-sm-8">
                                    <div class="row">
                                        <label class="team_create_input_label">&nbsp;</label>
                                        <input type="hidden" id="orig_current_license_count" name="orig_current_license_count" value="<?php echo $paidLicenses; ?>"/>
                                        <input type="hidden" id="orig_new_license_count" name="orig_new_license_count" value="<?php echo $currentLicenses;?>"/>
                                        <input type="hidden" id="orig_billing_frequency" name="orig_billing_frequency" value="<?php echo $billingFrequency; ?>"/>
                                        <input type="hidden" id="orig_expiration_date" name="orig_expiration_date" value="<?php echo $expirationDate; ?>"/>
                                        <div id="team_create_pricing_options_normal" class="clearfix">
                                            <div class="col-sm-6">
                                                    <label for="monthly_radio" class="team_pricing_option monthly<?php if ($billingFrequency == SUBSCRIPTION_TYPE_MONTH) {echo ' selected';} ?>">
                                                    <input
                                                        id="monthly_radio"
                                                        type="radio"
                                                        value="1"
                                                        name="billing_frequency"
                                                        data-change-frequency="1"
                                                        <?php if ($billingFrequency == SUBSCRIPTION_TYPE_MONTH) {echo 'checked';} ?>/>
                                                        <span id="monthly_team_price" style="font-weight:normal;">$<?php echo $systemSettingTeamMonthlyPricePerUser;?></span> <?php _e('USD / month'); ?>
                                                        <small>$<?php echo number_format($systemSettingTeamMonthlyPricePerUser, 2); ?> <?php _e('per user / month'); ?></small>
                                                    </label>
                                            </div>
                                            <div class="col-sm-6">
                                                    <label for="yearly_radio" class="team_pricing_option yearly <?php if ($billingFrequency == SUBSCRIPTION_TYPE_YEAR) {echo ' selected';} ?>">
                                                    <input
                                                        id="yearly_radio"
                                                        type="radio"
                                                        value="2"
                                                        name="billing_frequency"
                                                        data-change-frequency="1"
                                                        <?php if ($billingFrequency == SUBSCRIPTION_TYPE_YEAR) {echo 'checked';} ?>/>
                                                        <span id="yearly_team_price" style="font-weight:normal;">$<?php echo $systemSettingTeamYearlyPricePerUser;?></span> <?php _e('USD / year'); ?>
                                                        <small>$<?php echo number_format(round($systemSettingTeamYearlyPricePerUser / 12, 2), 2);?> <?php _e('per user / month'); ?></small>
                                                    </label>
                                            </div>
                                        </div>
                                    </div>
                                    <div id="team_create_pricing_options_custom" class="team_item_hidden"><?php
                                        printf(_('You have entered more than 500 members (the maximum allowed using this page). Please contact us (%sbusiness@appigo.com%s) to complete your team purchase.'), sprintf('<a href="mailto:business@appigo.com?subject=%s">', _('Todo Cloud High Volume Licensing')), '</a>'); ?>
                                    </div>
                                </div>
                            </div>
                            <div class="team_create_pricing_detail_paragraph"><?php
                                printf(_('Choosing the yearly option will save you %s compared to the monthly option.'), '<span id="yearly_savings">$' . (($systemSettingTeamMonthlyPricePerUser * 12) - $systemSettingTeamYearlyPricePerUser) . '</span>');?>
                            </div>
                        </div>
                    </div>

                    <!-- CHANGE SUMMARY SECTION -->
                    <div class="team_create_section">
                        <h3><?php _e('Change Summary'); ?></h3>
                        <div class="col-sm-6 text-right"><?php _e('New Expiration/Renewal Date:'); ?></div>
                        <div id="team_change_expiration_date" class="col-sm-6"><?php echo date('d M Y', $team->getExpirationDate()); ?></div>
                        <div class="clearfix"></div>

                        <div class="col-sm-6 text-right"><?php _e('Members to Add:'); ?></div>
                        <div id="team_change_members_to_add" class="col-sm-6">0</div>
                        <div class="clearfix"></div>

                        <div class="col-sm-6 text-right"><?php _e('Members to Remove:'); ?></div>
                        <div id="team_change_members_to_remove" class="col-sm-6">0</div>
                        <div class="clearfix"></div>

                        <div class="col-sm-6 text-right"><?php _e('Donated Month Credits:'); ?></div>
                        <div id="team_donated" class="col-sm-6"><?php echo $numOfActiveTeamCredits; ?></div>
                        <div class="clearfix"></div>

                        <div class="col-sm-6 text-right"><?php _e('Amount Due:'); ?></div>
                        <div id="team_change_amount_due" class="col-sm-6">$0.00</div>
                        <div class="clearfix"></div>
                    </div>

                    <!-- BILLING INFORMATION -->
                    <div id="billing_section" class="">
                        <br/>
                        <h2><?php _e('Billing Information'); ?></h2>
                        <?php

                        $billingInfo = TDOSubscription::getSubscriptionBillingInfoForUser($billing_admin);
                        if ($billingInfo) : ?>
                        <h3><a href="#" class="select-previous-credit-card active"><?php _e('Previous credit card information'); ?></a></h3>
                        <div class="previous-payment-method active b-border">
                            <h3><?php _e('Card name:');?> <strong><?php echo $billingInfo['name']; ?></strong></h3>
                            <h3><?php _e('Card number:');?> <strong>**** **** **** <?php echo $billingInfo['last4']; ?></strong> (<?php echo $billingInfo['exp_month']; ?>/<?php echo $billingInfo['exp_year']; ?>)</h3>
                        </div>
                        <h3><a href="#" class="select-new-peyment-method"><?php _e('New payment method'); ?></a></h3>
                        <?php
                        endif;
                        ?>

                        <div class="new-payment-method <?php echo (!$billingInfo)?'active':'collapse';?>">
                        <div class="team_create_input_row row clearfix">
                            <div class="col-sm-6">
                                <label id="ccard_number_label" class="team_create_input_label" for="ccard_number"><?php _e('Credit card number'); ?></label>
                                <input id="ccard_number" class="team_create_input_text" type="text" value="" maxlength="127" name="ccard_number"  autocomplete="off" />
                            </div>
                            <div class="col-sm-6">
                                <label id="ccard_name_label" class="team_create_input_label" for="ccard_name"><?php _e('Name on card'); ?></label>
                                <input id="ccard_name" class="team_create_input_text" type="text" value="" maxlength="127" name="ccard_name" autocomplete="off" />
                            </div>
                        </div>
                        <div class="team_create_input_row row clearfix">
                            <div class="col-sm-4">
                                <label id="ccard_cvc_label" class="team_create_input_label" for="ccard_cvc"><?php _e('Security code (CVC)'); ?></label>
                                <input id="ccard_cvc" class="team_create_input_text" type="text" value="" maxlength="4" name="ccard_cvc"  autocomplete="off" />
                            </div>
                            <div class="col-sm-4">
                                <label id="ccard_month_label" class="team_create_input_label" for="ccard_month"><?php _e('Expiry month'); ?></label>
                                <select id="ccard_month" data-encrypted-name="ccard_month">
                                    <?php
                                    $curMonth = intval(date('n'));
                                    for($i = 1; $i <= 12; $i++) : ?>
                                        <option value="<?php echo $i; ?>" <?php echo ($curMonth === $i)?'selected':''; ?>><?php echo sprintf("%02d", $i); ?> - <?php echo _(date("F", mktime(0, 0, 0, $i, 10))); ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="col-sm-4">
                                <label id="ccard_year_label" class="team_create_input_label" for="ccard_year"><?php _e('Expiry year'); ?></label>
                                <select id="ccard_year" data-encrypted-name="ccard_year">
                                    <?php

                                    $currentYear = (int)date('Y');
                                    $endYear = $currentYear + 10;

                                    for ($i = $currentYear; $i < $endYear; $i++)
                                    {
                                        if ($currentYear == $i)
                                            echo '<option value="' . $i . '" selected="selected">' . $i . '</option>';
                                        else
                                            echo '<option value="' . $i . '">' . $i . '</option>';
                                    }

                                    ?>
                                </select>
                            </div>
                        </div>
                        </div>
                    </div>
                    <div><input id="stripe_token" type="hidden" value=""/></div>

                    <!-- REVIEW CHANGES BUTTON -->
                    <div id="review_changes_buttocn_section" class="team_create_section">
                        <div class="team_create_input_row clearfix">
                            <div class="team_tos_agree_field width_full">
                                <label id="team_tos_agree_label" for="team_tos_agree">
                                <input id="team_tos_agree" type="checkbox" name="team_tos_agree" onclick="updatePurchaseButtonEnablement(event, this, true);"/>
                                <?php _e('I agree to the'); ?> <a href="/terms" target="_blank" title="<?php _e('Review the Terms of Service'); ?>"><?php _e(' Todo Cloud Terms of Service'); ?></a>.</label>
                            </div>
                        </div>
                        <div class="team_create_input_row  clearfix">
                            <button id="team_purchase_button" class="btn-default btn-orange disabled"><?php _e('Review Changes'); ?></button>
                        </div>
                        <div class="team_create_input_row row clearfix">
                            <div id="team_validate_error" class="team_validation_error_message width_full team_item_hidden"><?php _e('Please enter the missing information above indicated in red.'); ?></div>
                        </div>
                    </div>

                    <!-- RESULTS PLACEHOLDERS -->
                    <input type="hidden" id="team_change_new_expiration_date" value=""/>
                    <input type="hidden" id="team_change_new_num_of_members" value=""/>
                    <input type="hidden" id="team_change_billing_frequency" value=""/>
                    <input type="hidden" id="team_change_bulk_discount" value=""/>
                    <input type="hidden" id="team_change_discount_percentage" value=""/>
                    <input type="hidden" id="team_change_current_account_credit" value=""/>
                    <input type="hidden" id="team_change_total_charge" value=""/>
                </div>
                <script>updateModifyTeamPricing('<?php echo $paidLicenses; ?>', '<?php echo $currentLicenses; ?>', '<?php echo $expirationDate; ?>', '<?php echo $billingFrequency; ?>');</script>
                <?php endif; ?>
            </div>
            <div id="billing-history" class="tab-content container-tb clearfix">
                <h2><?php _e('Team Purchase History:'); ?> <?php echo $team->getTeamName(); ?></h2>
                <?php
                $teamPurchases = TDOTeamAccount::getTeamPurchaseHistory($teamid);
                if (!$teamPurchases) { ?>
                    <h3><?php _e('At the current moment there&#39;s no purchase history'); ?></h3>
                    <?php
                } else {
                if (count($teamPurchases) > 0) : ?>
                <br/>
                <section class="item-list">
                    <div class="clearfix b-border list-title">
                        <div class="col-sm-2"><?php _e('Purchase Date'); ?></div>
                        <div class="col-sm-2"><?php _e('Renewal Type'); ?></div>
                        <div class="col-sm-5"><?php _e('Description'); ?></div>
                        <div class="col-sm-2"><?php _e('Amount'); ?></div>
                        <div class="col-sm-1"></div>
                    </div>
                    <?php
                    foreach ($teamPurchases as $purchase) :
                        $typeString = _('Monthly');
                        if ($purchase['subscriptionType'] == "year")
                            $typeString = _('Yearly');
                        ?>
                    <div class="item clearfix">
                        <div class="col-sm-2"><?php echo _(date('F', $purchase['timestamp'])) . date(' d, Y', $purchase['timestamp']); ?></div>
                        <div class="col-sm-2"><?php echo $typeString; ?></div>
                        <div class="col-sm-5"><?php echo $purchase['description']; ?></div>
                        <div class="col-sm-2"><?php echo money_format('$%!.2n', $purchase['amount']); ?></div>
                        <div class="col-sm-1 text-right"><span class="fa fa-caret-down fa-fw view-actions"></span></div>
                        <div class="clearfix"></div>
                        <div class="item-actions collapse clearfix">
                            <div class="action-secondary col-sm-12 text-right">
                                <a href="#" onclick="resendPurchaseReceipt('<?php echo $teamid; ?>', '<?php echo $purchase['timestamp']; ?>')"><?php _e('Resend'); ?></a>
                            </div>
                        </div>
                    </div>
                    <?php
                    endforeach; ?>
                </section>
                <?php endif;
                } ?>
            </div>
            <div class="cancel-subscription-wrapper hidden">
                <div class="modal-header">
                    <?php _e('Cancel Team Account'); ?>
                </div>
                <div class="modal-content">
                    <form action="#" method="POST" data-progress="true">
                        <input type="hidden" name="method" value="cancelTeamRenewal">
                        <input type="hidden" name="teamID" value="<?php echo $teamid; ?>">
                    </form>
                    <p class="info"><?php
                        printf(_('Are you sure you&#39;d like to cancel your team account? After %s, members of your team will no longer enjoy the benefits of a premium account. Additionally, you will no longer have access to tasks stored in your team&#39;s shared lists.'), '<strong>'. date("d M Y", $team->getExpirationDate()).'</strong>');?>

                    </p>
                </div>
                <div class="modal-footer">
                    <div class="button btn-cancel-subscription progress-button" data-loading="<?php _e('Sending...'); ?>" data-finished="<?php _e('Done'); ?>"><?php _e('Cancel Team Account'); ?></div>
                    <div class="button btn-cancel-modal"><?php _e('Not Now'); ?></div>
                </div>
            </div>
        </div>
    </div>
    <?php
            break;
        }
        case 'user_is_member': {
    //overview
    $team = $is_member_of_team;
    if ($team):
        $teamid = $team->getTeamID();
        $team_admin = false;
        if($team->getBillingUserID()) {
            $team_admin = TDOUser::getUserForUserId($team->getBillingUserID());
        }
        $team_status = TDOTeamAccount::getTeamSubscriptionStatus($teamid);
        ?>

    <div class="tabs-container">
        <ul class="tabs">
            <li class="tab-link current" data-tab="overview"><?php _e('Overview'); ?></li>
        </ul>

        <div id="overview" class="tab-content current container-tb clearfix">
            <div class="row team-name-wrapper">
                <div class="col-sm-3"><h3><?php _e('Team name'); ?></h3></div>
                <div class="col-sm-9">
                    <div class="team-name">
                        <span><?php echo $team->getTeamName(); ?></span>
                    </div>
                </div>
            </div>
            <div class="clearfix"></div>
            <p>
                <?php if($team_status > 0) :
                    _e('Next renewal date:');
                 else :
                    _e('This account expired on:');
                endif; ?>
                <strong><?php echo _(date('F', $team->getExpirationDate())) . date(' d, Y', $team->getExpirationDate()); ?></strong></p>
            <br>
            <p><?php _e('Your Todo Cloud Premium Account is paid for by a Todo for Business Team.');?>
                <?php if($team_admin) : ?>
                <?php _e('Your team&#39;s administrator is'); ?> <?php echo $team_admin->displayName(); ?> (<a href="mailto:<?php echo $team_admin->username(); ?>"><?php echo $team_admin->username(); ?></a>)
                <?php endif; ?>
            </p>
            <br>
            <a href="#" class="btn-leave-the-team-modal"><?php _e('Leave this Team'); ?></a>
        </div>

        <div class="leave-the-team-modal-content-wrapper hidden">
            <div class="modal-header">
                <?php _e('Leave'); ?> <?php echo $team->getTeamName(); ?>?
            </div>
            <div class="modal-content">
                <p class="info"><?php _e('Your account will no longer be paid for by a Todo Cloud for Business team. To get the most of Todo Cloud, you will need to purchase an individual subscription after leaving this team.'); ?></p>
                <form action="#" method="POST">
                    <input type="hidden" name="method" value="leaveTeam">
                    <input type="hidden" name="teamID" value="<?php echo $teamid; ?>">
                    <input type="hidden" name="userID" value="<?php echo $userid; ?>">
                </form>
            </div>
            <div class="modal-footer">
                <div class="button btn-leave-this-team progress-button"  data-loading="<?php _e('Processing...'); ?>" data-finished="<?php _e('Done'); ?>"><?php _e('Leave this Team'); ?></div>
                <div class="button btn-cancel-modal"><?php _e('Cancel'); ?></div>
            </div>
        </div>
    </div>
<?php else : ?>
<?php endif;
    break;
}
        case 'integrations' : {
        $teams = TDOTeamAccount::getAdministeredTeamsForAdmin($userid);
        $teamid = '';
        if (is_array($teams)) {
            $team = $teams[0];
        }
        if ($team) {
            $teamid = $team->getTeamID();
        }

        $systemSettingTeamSlackIntegrationForInternalUseOnly = TDOUtil::getStringSystemSetting('SYSTEM_SETTING_SLACK_ENABLED_FOR_INTERNAL_USE_ONLY', SYSTEM_SETTING_SLACK_ENABLED_FOR_INTERNAL_USE_ONLY);
        $systemSettingTeamSlackIntegrationForInternalUseOnlyTeamId = TDOUtil::getStringSystemSetting('SYSTEM_SETTING_SLACK_ENABLED_FOR_TEAM_TEAM_ID', SYSTEM_SETTING_SLACK_ENABLED_FOR_TEAM_TEAM_ID);
        $systemSettingTeamSlackIntegrationForInternalUseOnly = $systemSettingTeamSlackIntegrationForInternalUseOnly == 'true'? true: false;
        if ($systemSettingTeamSlackIntegrationForInternalUseOnly == true && $teamid !== $systemSettingTeamSlackIntegrationForInternalUseOnlyTeamId) {
            break;
        }

        $team_status = TDOTeamAccount::getTeamSubscriptionStatus($teamid);
        $team_list_activity_blocked = FALSE;
        $all_lists = TDOList::getSharedListsForTeam($teamid, TRUE);
        $lists = array();
        $slack_config = TDOTeamSlackIntegration::getTeamSlackIntegrations($teamid);
        if ($team_status !== TEAM_SUBSCRIPTION_STATE_EXPIRED && $team_status !== TEAM_SUBSCRIPTION_STATE_GRACE_PERIOD) {
            foreach ($all_lists as $list) {
                if (!$list->deleted()) {
                    $lists[] = $list;
                }
            }

        } else {
            $team_list_activity_blocked = TRUE;
        }
    ?>
    <div class="tabs-container">
        <ul class="tabs">
            <li class="tab-link current" data-tab="integrations"><?php _e('Integrations'); ?></li>
        </ul>

        <div id="integrations" class="tab-content current container-tb clearfix">
            <?php if(!$team_list_activity_blocked) : ?>
            <div class="row clearfix integration-item">
                <div class="col-sm-10">
                    <div class="service-icon"><img src="/images/slack_logo_256.png" alt="Slack"></div>
                    <h2><?php _e('Slack'); ?></h2>
                    <?php if ($slack_config && sizeof($slack_config)) : ?>
                    <p class="info"><?php echo sizeof($slack_config); ?> <?php printf(_('team shared list%s configured'), (sizeof($slack_config)>1)?'s':''); ?></p>
                    <?php else: ?>
                    <p class="info"><?php _e('Shared list task notifications in your Slack channels'); ?></p>
                    <?php endif; ?>
                </div>
                <div class="col-sm-2"><a href="#" class="btn-default btn-size-sm btn-block btn-config-slack-integration"><?php _e('Configure'); ?></a></div>
            </div>
        <?php else: ?>
            <h2><?php _e('Sorry, but team subscription has expired.'); ?></h2>
        <?php endif; ?>


        </div>
        <div class="slack-integration-wrapper hidden">
            <div class="modal-header"><?php _e('Configure Slack Integration'); ?></div>
            <div class="modal-content">
                <div class="modal-big">
                <form action="#" method="POST" data-progress="true">
                    <input type="hidden" name="method" value="updateSlackConfig">
                    <input type="hidden" name="jsaction" value="updateSlackWebhook">
                    <input type="hidden" name="teamID" value="<?php echo $teamid; ?>">
                    <section class="item-list webhook-list">
                        <div class=" clearfix b-border list-title">
                            <div class="col-sm-3"><?php _e('List Name'); ?></div>
                            <div class="col-sm-5"><?php _e('Slack Incoming Webhook URL'); ?></div>
                            <div class="col-sm-2"><?php _e('Slack Channel'); ?></div>
                            <div class="col-sm-2"><?php _e('Token'); ?></div>
                        </div>
                        <?php foreach ($lists as $list) : ?>
                        <?php
                            $webhookUrl = '';
                            $channelName = '';
                            if ($slack_config && isset($slack_config[$list->listId()])) {
                                $webhookUrl = $slack_config[$list->listId()]['webhook_url'];
                                $channelName = $slack_config[$list->listId()]['channel_name'];
                                $token = $slack_config[$list->listId()]['out_token'];
                            }
                            ?>
                        <input type="hidden" name="listID[]" value="<?php echo $list->listId(); ?>">
                        <div class="item clearfix">
                            <div class="col-sm-3"><?php echo $list->name(); ?></div>
                            <div class="col-sm-5"><input type="text" name="webhookUrl[]" value="<?php echo $webhookUrl; ?>" class="form-control channel-url <?php echo ($webhookUrl === '') ? '' : 'origin-valid'; ?>" placeholder="<?php _e('Incoming Webhook URL'); ?>"></div>
                            <div class="col-sm-2"><input type="text" name="channelName[]" value="<?php echo $channelName; ?>" class="form-control channel-field <?php echo ($channelName === '') ? '' : 'origin-valid'; ?>" maxlength="22" placeholder="#channel"></div>
                            <div class="col-sm-2"><input type="text" name="token[]" value="<?php echo $token; ?>" class="form-control token-field" placeholder="<?php _e('Token'); ?>"></div>
                        </div>
                        <?php endforeach; ?>
                    </section>

                </form>
                </div>
            </div>
            <div class="modal-footer">
                <div class="button btn-update-slack-webhook disabled" data-loading="<?php _e('Saving...'); ?>" data-finished="<?php _e('Done'); ?>"><?php _e('Save'); ?></div>
                <div class="button btn-cancel-modal"><?php _e('Cancel'); ?></div>
            </div>
        </div>

    </div>


    <?php
            break;
        }
		default: {
            //overview
            $teams = TDOTeamAccount::getAdministeredTeamsForAdmin($userid);
            if(is_array($teams)){
                $team = $teams[0];
            }
            if ($team):
                $teamid = $team->getTeamID();
                $is_admin = TDOTeamAccount::isAdminForTeam($userid, $teamid);
                $invitedLicenseCount = 0;
                $usedLicenseCount = 0;
                $shared_list_count = 0;
                $active_tasks_count = 0;

                $team_status = TDOTeamAccount::getTeamSubscriptionStatus($teamid);

                $invited_users = TDOTeamAccount::getTeamInvitationInfo($userid, $teamid, TEAM_MEMBERSHIP_TYPE_ADMIN);
                $invitedLicenseCount = TDOTeamAccount::getInvitationCountByTeamId($teamid);

                $memberInfos = TDOTeamAccount::getTeamMemberInfo($userid, $teamid);
                if ($memberInfos) {
                    $usedLicenseCount = count($memberInfos);
                }
                $shared_lists = TDOList::getSharedListsForTeam($teamid);
                $shared_list_count = sizeof($shared_lists);
                foreach ($shared_lists as $list) {
                    $active_tasks_count += $list->getTaskCount();
                }
            ?>

    <div class="tabs-container">

        <ul class="tabs">
            <li class="tab-link current" data-tab="overview"><?php _e('Overview'); ?></li>
            <li class="tab-link" data-tab="team-administrators"><?php _e('Team Administrators'); ?></li>
            <li class="tab-link <?php echo ($is_admin)?'':'disabled'?>" data-tab="contact-info"><?php _e('Contact Info'); ?></li>
        </ul>

        <div id="overview" class="tab-content current container-tb clearfix">
            <div class="row team-name-wrapper">
                <div class="col-sm-3"><h3><?php _e('Team name'); ?></h3></div>
                <div class="col-sm-9">
                    <div class="team-name">
                        <span><?php echo $team->getTeamName(); ?></span>
                        <a href="#" class="change-team-name fa fa-edit"></a>

                        <div class="form-wrapper collapse" >
                            <form action="#" class="" autocomplete="off">
                                <input type="hidden" name="method" value="updateTeamName">
                                <input type="hidden" name="teamid" value="<?php echo $teamid; ?>">
                                <input type="text" class="form-field" name="teamName" value="<?php echo $team->getTeamName();?>" placeholder="<?php _e('Team name'); ?>" maxlength="127">
                                <button class="fa fa-check btn-form-submit"></button>
                                <button class="fa fa-times btn-form-hide"></button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <div class="clearfix"></div>
            <p>
            <?php if($team_status > 0) :
                _e('Next renewal date:');
            else :
                _e('This account expired on:');
            endif; ?>
                <strong><?php echo _(date('F', $team->getExpirationDate())) . date(' d, Y', $team->getExpirationDate()); ?></strong></p>

            <br>
            <hr class="delimiter">

            <div class="col-sm-4 text-center overview-item">
                <h2><?php echo $usedLicenseCount; ?></h2>

                <p class="info"><?php _e('Team Members'); ?></p>
                <a href="?appSettings=show&option=teaming&action=teaming_members#invited-members-btn" class="action"><?php _e('Invite members'); ?></a>
            </div>
            <div class="col-sm-4 text-center overview-item">
                <h2><?php echo $invitedLicenseCount; ?></h2>
                <p class="info"><?php _e('Pending Invitations'); ?></p>
                <a href="?appSettings=show&option=teaming&action=teaming_members#invited-members-of" class="action"><?php _e('Send reminders'); ?></a>
            </div>
            <div class="col-sm-4 text-center overview-item">
                <h2><?php echo $usedLicenseCount; ?> <em><?php _e('of'); ?></em> <?php echo $team->getNewLicenseCount(); ?></h2>
                <p class="info"><?php _e('Licenses in Use'); ?></p>
                <a href="?appSettings=show&option=teaming&action=teaming_billing" class="action"><?php _e('Add licenses'); ?></a>
            </div>
            <div class="col-sm-4 text-center overview-item">
                <h2><?php echo $shared_list_count; ?></h2>
                <p class="info"><?php _e('Shared Lists'); ?></p>
                <a href="?appSettings=show&option=teaming&action=teaming_lists" class="action"><?php _e('Manage Lists'); ?></a>
            </div>
            <div class="col-sm-4 text-center overview-item">
                <h2><?php echo $active_tasks_count; ?></h2>
                <p class="info"><?php _e('Active Tasks'); ?></p>
            </div>
        </div>

        <div id="team-administrators" class="tab-content">
            <div class="pull-right">
                <button class="btn-default btn-green btn-size-sm btn-invite-new-admin"><?php _e('Invite New Admin'); ?></button>
            </div>
            <div class="clearfix"></div>
            <br/>
            <section class="item-list">
                <?php
                $billingUserID = $team->getBillingUserID();
                $teamAdminIDs = TDOTeamAccount::getAdminUserIDsForTeam($teamid);
                if ($teamAdminIDs) : ?>
                    <div class="clearfix b-border list-title">
                        <div class="col-sm-6"><?php _e('Name'); ?></div>
                        <div class="col-sm-6 text-right"><?php _e('Status'); ?></div>
                    </div>
                    <?php
                    foreach ($teamAdminIDs as $adminID) :
                        $adminDisplayName = TDOUser::displayNameForUserId($adminID); ?>
                        <div class="item clearfix">
                            <div class="user-name col-sm-6"><span <?php echo (!empty($billingUserID)) && ($billingUserID == $adminID)?'':'class="do-main-action"';?>><?php echo $adminDisplayName; ?></span></div>
                            <div class="user-status col-sm-6 text-right"><?php
                                echo ((!empty($billingUserID)) && ($billingUserID == $adminID)) ? _('Billing Admin') : _('Admin');
                                ?> <span class="fa fa-caret-down fa-fw view-actions"></span></div>
                            <div class="item-actions collapse clearfix">
                                <div class="action-main col-sm-6">
                                    <?php if ((!empty($billingUserID)) && ($billingUserID == $adminID)) : ?>
                                    <p><?php _e('You cannot remove the billing administrator'); ?></p>
                                    <?php else : ?>
                                        <?php if ($userid === $adminID) { ?>
                                    <a href="/?appSettings=show&option=teaming&action=becomeBillingAdmin&teamid=<?php echo $teamid; ?>" class="btn-default btn-size-sm btn-become-billing-admin" data-user-id="<?php echo $adminID; ?>"><?php _e('Become Billing Admin'); ?></a>
                                        <?php } else { ?>
                                    <button class="btn-default btn-size-sm btn-remove-user-modal" data-user-id="<?php echo $adminID; ?>"><?php _e('Remove this Admin'); ?></button>
                                        <?php } ?>
                                    <?php endif; ?>
                                </div>
                                <div class="action-secondary col-sm-6 text-right">
                                    <?php if ($userid === $adminID && $userid !== $billingUserID) { ?>
                                    <a href="#" class="btn-remove-me-modal" data-user-id="<?php echo $adminID; ?>"><?php _e('Remove me as an admin'); ?></a>
                                    <?php } ?>
                                </div>
                            </div>
                        </div>
                        <?php
                    endforeach;
                endif; ?>
                <?php if($invited_users) :
                    foreach ($invited_users as $invite) :
                        $user_name = $invite['email']; ?>
                    <div class="item clearfix">
                        <div class="user-name col-sm-6"><span class="do-main-action"><?php echo $user_name; ?></span></div>
                        <div class="user-status col-sm-6 text-right">Invited <span class="fa fa-caret-down fa-fw view-actions"></span></div>
                        <div class="item-actions collapse clearfix">
                            <div class="action-main col-sm-6">
                                <button class="btn-default btn-size-sm btn-resend-invitation-modal" data-invitation="<?php echo $invite['invitationid']; ?>"><?php _e('Resend Invitation'); ?></button>
                            </div>
                            <?php if ($team_status !== TEAM_SUBSCRIPTION_STATE_EXPIRED && $team_status !== TEAM_SUBSCRIPTION_STATE_GRACE_PERIOD) : ?>
                            <div class="action-secondary col-sm-6 text-right"><a href="#" class="btn-delete-invitation-modal" data-invitation="<?php echo $invite['invitationid']; ?>"><?php _e('Delete this Invitation'); ?></a></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php
                endforeach;
                endif; ?>
            </section>
            <div class="invite-members-modal-wrapper hidden">
                <div class="modal-header"><?php _e('Invite Administrators'); ?></div>
                <div class="modal-content">
                    <div class="form-wrapper todo-for-business clearfix" data-available-license-count="<?php echo intval($team->getNewLicenseCount() - $usedLicenseCount); ?>">
                        <form action="#" method="POST" data-progress="true" class="form-invite-persone active primary">
                            <p class="info"><?php _e('New administrators can invite other team members and control access to shared lists.'); ?></p>
                            <input type="hidden" name="jsaction" value="submitInviteMembersFormModal">
                            <input type="hidden" name="method" value="inviteTeamMember">
                            <input type="hidden" name="teamid" value="<?php echo $teamid; ?>">
                            <input type="hidden" name="memberType" value="<?php echo TEAM_MEMBERSHIP_TYPE_ADMIN; ?>">
                            <div class="col-sm-12 field-row">
                                <label><?php _e('Email Address'); ?></label>
                                <div class="field-wrap">
                                    <input type="email" class="form-field required" name="email[]" value="" placeholder="<?php _e('Email'); ?>" required autofocus>
                                    <i class="fa fa-times btn-remove-row hidden"></i>
                                </div>
                            </div>
                        </form>
                        <form action="#" class="more-licenses-wrapper collapse">
                            <input type="hidden" name="method" value="getTeamChangePricingInfo">
                            <input type="hidden" name="teamID" value="<?php echo $teamid; ?>">
                            <input type="hidden" name="billingFrequency" value="<?php echo $team->getBillingFrequency(); ?>">
                            <input type="hidden" name="numOfTeamMembers" value="">

                            <input type="hidden" id="orig_current_license_count" name="orig_current_license_count" value="<?php echo $team->getLicenseCount(); ?>"/>
                            <input type="hidden" id="orig_new_license_count" name="orig_new_license_count" value="<?php echo $team->getNewLicenseCount();?>"/>
                            <input type="hidden" id="orig_billing_frequency" name="orig_billing_frequency" value="<?php echo $team->getBillingFrequency(); ?>"/>
                            <input type="hidden" id="orig_expiration_date" name="orig_expiration_date" value="<?php echo $team->getExpirationDate(); ?>"/>

                            <div class="col-sm-12">
                                <p class="info strong"><?php _e('Additional team licenses needed.'); ?></p>
                                <p class="info"><?php _e('All the licenses on your team are being used, but it&#39;s easy to add more.'); ?></p>
                                <div class="col-sm-6 text-right"><?php _e('Current Licenses'); ?></div>
                                <div class="col-sm-6 current-licenses-count"><?php echo $team->getLicenseCount(); ?></div>
                                <div class="col-sm-6 text-right"><?php _e('Licenses to Add'); ?></div>
                                <div class="col-sm-6 licenses-to-add-count">
                                    <span></span>
                                    (<?php
                                    echo ($team->getBillingFrequency() == SUBSCRIPTION_TYPE_YEAR)
                                        ? '$' . $systemSettingTeamYearlyPricePerUser . _('/year')
                                        : '$' . $systemSettingTeamMonthlyPricePerUser . _('/month');
                                    ?> <?php _e('per user'); ?>)</div>
                                <div class="col-sm-6 text-right"><?php _e('To be charged now (pro-rated)'); ?></div>
                                <div class="col-sm-6 to-be-charged-count"></div>
                                <div class="clearfix"></div>

                                <br>
                                <div id="billing_section" class="">
                                    <div class="row clearfix">
                                        <div class="col-sm-6">
                                            <label id="ccard_number_label" class="team_create_input_label" for="ccard_number"><?php _e('Credit card number'); ?></label>
                                            <input id="ccard_number" class="team_create_input_text" type="text" maxlength="127" autocomplete="off" />
                                        </div>
                                        <div class="col-sm-6">
                                            <label id="ccard_name_label" class="team_create_input_label" for="ccard_name"><?php _e('Name on card'); ?></label>
                                            <input id="ccard_name" class="team_create_input_text" type="text" maxlength="127" autocomplete="off" />
                                        </div>
                                    </div>
                                    <div class="clearfix">
                                        <div class="col-sm-4">
                                            <label id="ccard_cvc_label" class="team_create_input_label" for="ccard_cvc"><?php _e('Security code (CVC)'); ?></label>
                                            <input id="ccard_cvc" class="team_create_input_text" type="text" maxlength="4" autocomplete="off" />
                                        </div>
                                        <div class="col-sm-4">
                                            <label id="ccard_month_label" class="team_create_input_label" for="ccard_month"><?php _e('Expiry month'); ?></label>
                                            <select id="ccard_month" data-encrypted-name="ccard_month">
                                                <?php
                                                $curMonth = intval(date('n'));
                                                for($i = 1; $i <= 12; $i++) : ?>
                                                    <option value="<?php echo $i; ?>" <?php echo ($curMonth === $i)?'selected':''; ?>><?php echo sprintf("%02d", $i); ?> - <?php echo _(date("F", mktime(0, 0, 0, $i, 10))); ?></option>
                                                <?php endfor; ?>
                                            </select>
                                        </div>
                                        <div class="col-sm-4">
                                            <label id="ccard_year_label" class="team_create_input_label" for="ccard_year"><?php _e('Expiry year'); ?></label>
                                            <select id="ccard_year" data-encrypted-name="ccard_year">
                                                <?php

                                                $currentYear = (int)date('Y');
                                                $endYear = $currentYear + 10;

                                                for ($i = $currentYear; $i < $endYear; $i++)
                                                {
                                                    if ($currentYear == $i)
                                                        echo '<option value="' . $i . '" selected="selected">' . $i . '</option>';
                                                    else
                                                        echo '<option value="' . $i . '">' . $i . '</option>';
                                                }

                                                ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div><input id="stripe_token" type="hidden" value=""/></div>
                                </div>

                            </div>
                        </form>
                        <form action="#" class="confirm-purchase collapse">
                            <input type="hidden" name="method" value="changeTeamAccount">
                            <input type="hidden" name="teamID" value="<?php echo $teamid; ?>">
                            <input type="hidden" name="billingFrequency" value="<?php echo $team->getBillingFrequency(); ?>">
                            <input type="hidden" name="stripeToken" value="">
                            <input type="hidden" name="numOfMembers" value="">
                            <h2><?php _e('Change Summary:'); ?></h2>
                            <div class="col-sm-6 text-right "><?php _e('Number of Team Members'); ?></div>
                            <div class="col-sm-6 num-of-members"></div>
                            <div class="col-sm-6 text-right"><?php _e('Billing Type'); ?></div>
                            <div class="col-sm-6 billing-type"></div>
                            <div class="col-sm-6 text-right"><?php _e('Total'); ?></div>
                            <div class="col-sm-6 total-price"></div>
                            <div class="clearfix"></div>
                            <p class="info"><?php _e('Make purchase'); ?></p>
                        </form>
                    </div>
                    <div class="todo-for-business-add-line-user">
                        <a href="#"><i class="fa fa-fw fa-plus"></i><?php _e('Add another administrator'); ?></a>
                    </div>
                </div>
                <div class="modal-footer">
                    <div class="team_validation_error_message pull-left"></div>
                    <div class="button btn-invite progress-button" data-new-licenses-count="0" data-loading="<?php _e('Sending...'); ?>" data-finished="<?php _e('Done'); ?>">
                        <span class="invite"><?php _e('Invite');?> <span class="reg-count">1</span> <?php _e('Administrator'); ?><span class="reg-many hidden">s</span></span>
                        <span class="need-more-licenses collapse"><?php _e('More Licenses Needed'); ?></span>
                        <span class="licenses collapse"><?php _e('Review Changes'); ?></span>
                        <span class="make-purchase collapse"><?php _e('Purchase'); ?></span>
                    </div>
                    <div class="button btn-cancel-modal"><?php _e('Cancel'); ?></div>
                </div>
            </div>
            <div class="invitation-limit-modal-content-wrapper hidden">
                <div class="invitation-limit-modal-header"><?php _e('Sorry'); ?></div>
                <div class="invitation-limit-modal-content">
                    <p class="info"><?php _e('You have reached limit of licenses.'); ?></p>
                </div>
                <div class="invitation-limit-modal-footer">
                    <a href="?appSettings=show&option=teaming&action=teaming_billing" class="button"><?php _e('Add licenses'); ?></a>
                    <div class="button btn-cancel-modal"><?php _e('Cancel'); ?></div>
                </div>
            </div>
            <div class="resend-invitation-wrapper hidden">
                <div class="modal-header"><?php _e('Resend Invitation'); ?></div>
                <div class="modal-content">
                    <form action="#" method="POST" data-progress="true">
                        <input type="hidden" name="method" value="resendTeamInvitation">
                        <input type="hidden" name="invitationID" value="">
                        <input type="hidden" name="membershipType" value="<?php echo TEAM_MEMBERSHIP_TYPE_ADMIN; ?>">
                    </form>
                    <p class="info"><?php _e('Are you sure you&#39;d like to resend this invitation?'); ?></p>
                </div>
                <div class="modal-footer">
                    <div class="button btn-resend-invitation progress-button" data-loading="<?php _e('Sending...'); ?>" data-finished="<?php _e('Done'); ?>"><?php _e('Resend invitation'); ?></div>
                    <div class="button btn-cancel-modal"><?php _e('Cancel'); ?></div>
                </div>
            </div>
            <div class="delete-invitation-wrapper hidden">
                <div class="modal-header"><?php _e('Delete Invitation'); ?></div>
                <div class="modal-content">
                    <form action="#" method="POST" data-progress="true">
                        <input type="hidden" name="method" value="deleteTeamInvitation">
                        <input type="hidden" name="invitationID" value="">
                    </form>
                    <p class="info"><?php _e('Are you sure you&#39;d like to delete this invitation?'); ?></p>
                </div>
                <div class="modal-footer">
                    <div class="button btn-delete-invitation"><?php _e('Delete'); ?></div>
                    <div class="button btn-cancel-modal"><?php _e('Cancel'); ?></div>
                </div>
            </div>
            <div class="delete-user-wrapper hidden">
                <div class="modal-header"><?php _e('Remove Administrator'); ?></div>
                <div class="modal-content">
                    <form action="#" method="POST">
                        <input type="hidden" name="method" value="removeTeamMember">
                        <input type="hidden" name="teamID" value="<?php echo $teamid; ?>">
                        <input type="hidden" name="membershipType" value="<?php echo TEAM_MEMBERSHIP_TYPE_ADMIN; ?>">
                        <input type="hidden" name="userID" value="">
                    </form>
                    <p class="info"><?php _e('Are you sure you&#39;d like to remove this team administrator?'); ?></p>
                </div>
                <div class="modal-footer">
                    <div class="button btn-delete-administrator progress-button"  data-loading="<?php _e('Deleting...'); ?>" data-finished="<?php _e('Done'); ?>"><?php _e('Delete'); ?></div>
                    <div class="button btn-cancel-modal"><?php _e('Cancel'); ?></div>
                </div>
            </div>
            <div class="delete-me-wrapper hidden">
                <div class="modal-header"><?php _e('Remove Me From Team'); ?></div>
                <div class="modal-content">
                    <form action="#" method="POST">
                        <input type="hidden" name="method" value="removeTeamMember">
                        <input type="hidden" name="teamID" value="<?php echo $teamid; ?>">
                        <input type="hidden" name="membershipType" value="<?php echo TEAM_MEMBERSHIP_TYPE_ADMIN; ?>">
                        <input type="hidden" name="userID" value="">
                    </form>
                    <p class="info"><?php _e('Remove me as an administrator?'); ?></p>
                </div>
                <div class="modal-footer">
                    <div class="button btn-delete-administrator progress-button"  data-loading="<?php _e('Deleting...'); ?>" data-finished="<?php _e('Done'); ?>"><?php _e('Delete'); ?></div>
                    <div class="button btn-cancel-modal"><?php _e('Cancel'); ?></div>
                </div>
            </div>

        </div>
        <div id="contact-info" class="tab-content">
            <?php if ($is_admin) :?>
            <p class="info"><?php _e('This information will be included on all your purchase receipts'); ?></p>

            <div class="form-wrapper">
                <form action="#" class="form-action" data-form-action-type="update" data-reload-type="live" data-progress="true">
                    <div class="col-sm-12 col-md-10 col-lg-6">
                        <input type="hidden" name="method" value="updateTeamInfo">
                        <input type="hidden" name="teamid" value="<?php echo $teamid; ?>">

                        <label for="field-company-name"><?php _e('Company name'); ?></label>
                        <input type="text" id="field-company-name" class="form-field required" name="bizName" value="<?php echo $team->getBizName();?>" placeholder="<?php _e('Company name'); ?>" autofocus >

                        <label for="field-company-phone"><?php _e('Phone'); ?></label>
                        <input type="text" id="field-company-phone" class="form-field" name="bizPhone" value="<?php echo $team->getBizPhone();?>" placeholder="<?php _e('Phone'); ?>">

                        <label for="field-company-address"><?php _e('Address'); ?></label>
                        <textarea id="field-company-address" class="form-field" name="bizAddr1" cols="30" rows="5" placeholder="<?php _e('Company address'); ?>"><?php echo $team->getBizAddr1(); ?></textarea>

                        <label for="field-company-city"><?php _e('City'); ?></label>
                        <input type="text" id="field-company-city" class="form-field" name="bizCity" value="<?php echo $team->getBizCity();?>" placeholder="<?php _e('City'); ?>">

                        <label for="field-company-state"><?php _e('State'); ?></label>
                        <input type="text" id="field-company-state" class="form-field" name="bizState" value="<?php echo $team->getBizState();?>" placeholder="<?php _e('State'); ?>">

                        <label for="field-company-country"><?php _e('Country'); ?></label>
                        <select class="form-field required" name="bizCountry"  id="field-company-country">
                            <?php foreach($countries as $key=>$value): ?>
                                <option value="<?php echo $key; ?>"<?php if($key === $team->getBizCountry()) echo 'selected'; ?>><?php echo $value; ?></option>
                            <?php endforeach; ?>
                        </select>

                        <label for="field-company-postcode"><?php _e('ZIP/Postal Code'); ?></label>
                        <input type="text" id="field-company-postcode" class="form-field" name="bizPostalCode" value="<?php echo $team->getBizPostalCode();?>" placeholder="<?php _e('ZIP/Postal Code'); ?>">


                        <p class="info"><?php printf(_('After you&#39;ve changed this information, you can %sresend a purchase receipt%s.'),'<a href="/?appSettings=show&option=teaming&action=teaming_billing#billing-history">', '</a>');?></p>
                        <button class="btn-default btn-orange btn-form-submit progress-button" data-origin-label="<?php _e('Save changes'); ?>" data-loading="<?php _e('Saving...'); ?>" data-finished="<?php _e('Saved'); ?>"><?php _e('Save changes'); ?></button>
                    </div>
                    <div class="clearfix"></div>
                </form>
            </div>
            <?php endif;?>
        </div>
    </div>
        <?php else : ?>
        <?php endif;
        break;
        }
    }
} else {
    if($teamsCount): ?>
    <script type="text/javascript">window.location = "?appSettings=show&option=teaming";</script>
    <?php else: ?>
    <script type="text/javascript">window.location = "?appSettings=show&option=teaming&action=createTeam";</script>
    <?php endif;
}
?>
</div>


<script type="text/javascript" src="https://js.stripe.com/v1/"></script>
<script type="text/javascript" src="/js/progress-meter.js"></script>
<script type="text/javascript">
     Stripe.setPublishableKey('<?php echo APPIGO_STRIPE_PUBLIC_KEY; ?>');
</script>

<script type="text/javascript" src="<?php echo TP_JS_PATH_UTIL_FUNCTIONS; ?>"></script>
<script type="text/javascript" src="<?php echo TP_JS_PATH_APP_SETTINGS_FUNCTIONS; ?>" ></script>
<!--<script type="text/javascript" src="<?php echo TP_JS_PATH_DATE_PICKER; ?>" ></script>-->
<!--
<script>
buildSubscriptionHtmlLayout();
loadPremiumAccountInfo ();
loadSubscriptionSettingContent();
</script>
-->
