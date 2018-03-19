<?php
$userid = FALSE;
if ($session->getUserId()) {
    $userid = $session->getUserId();
}
$countries = array
(
    'US' => 'United States',
    'GB' => 'United Kingdom',
    'AF' => 'Afghanistan',
    'AX' => 'Aland Islands',
    'AL' => 'Albania',
    'DZ' => 'Algeria',
    'AS' => 'American Samoa',
    'AD' => 'Andorra',
    'AO' => 'Angola',
    'AI' => 'Anguilla',
    'AQ' => 'Antarctica',
    'AG' => 'Antigua And Barbuda',
    'AR' => 'Argentina',
    'AM' => 'Armenia',
    'AW' => 'Aruba',
    'AU' => 'Australia',
    'AT' => 'Austria',
    'AZ' => 'Azerbaijan',
    'BS' => 'Bahamas',
    'BH' => 'Bahrain',
    'BD' => 'Bangladesh',
    'BB' => 'Barbados',
    'BY' => 'Belarus',
    'BE' => 'Belgium',
    'BZ' => 'Belize',
    'BJ' => 'Benin',
    'BM' => 'Bermuda',
    'BT' => 'Bhutan',
    'BO' => 'Bolivia',
    'BA' => 'Bosnia And Herzegovina',
    'BW' => 'Botswana',
    'BV' => 'Bouvet Island',
    'BR' => 'Brazil',
    'IO' => 'British Indian Ocean Territory',
    'BN' => 'Brunei Darussalam',
    'BG' => 'Bulgaria',
    'BF' => 'Burkina Faso',
    'BI' => 'Burundi',
    'KH' => 'Cambodia',
    'CM' => 'Cameroon',
    'CA' => 'Canada',
    'CV' => 'Cape Verde',
    'KY' => 'Cayman Islands',
    'CF' => 'Central African Republic',
    'TD' => 'Chad',
    'CL' => 'Chile',
    'CN' => 'China',
    'CX' => 'Christmas Island',
    'CC' => 'Cocos (Keeling) Islands',
    'CO' => 'Colombia',
    'KM' => 'Comoros',
    'CG' => 'Congo',
    'CD' => 'Congo, Democratic Republic',
    'CK' => 'Cook Islands',
    'CR' => 'Costa Rica',
    'CI' => 'Cote D\'Ivoire',
    'HR' => 'Croatia',
    'CU' => 'Cuba',
    'CY' => 'Cyprus',
    'CZ' => 'Czech Republic',
    'DK' => 'Denmark',
    'DJ' => 'Djibouti',
    'DM' => 'Dominica',
    'DO' => 'Dominican Republic',
    'EC' => 'Ecuador',
    'EG' => 'Egypt',
    'SV' => 'El Salvador',
    'GQ' => 'Equatorial Guinea',
    'ER' => 'Eritrea',
    'EE' => 'Estonia',
    'ET' => 'Ethiopia',
    'FK' => 'Falkland Islands (Malvinas)',
    'FO' => 'Faroe Islands',
    'FJ' => 'Fiji',
    'FI' => 'Finland',
    'FR' => 'France',
    'GF' => 'French Guiana',
    'PF' => 'French Polynesia',
    'TF' => 'French Southern Territories',
    'GA' => 'Gabon',
    'GM' => 'Gambia',
    'GE' => 'Georgia',
    'DE' => 'Germany',
    'GH' => 'Ghana',
    'GI' => 'Gibraltar',
    'GR' => 'Greece',
    'GL' => 'Greenland',
    'GD' => 'Grenada',
    'GP' => 'Guadeloupe',
    'GU' => 'Guam',
    'GT' => 'Guatemala',
    'GG' => 'Guernsey',
    'GN' => 'Guinea',
    'GW' => 'Guinea-Bissau',
    'GY' => 'Guyana',
    'HT' => 'Haiti',
    'HM' => 'Heard Island & Mcdonald Islands',
    'VA' => 'Holy See (Vatican City State)',
    'HN' => 'Honduras',
    'HK' => 'Hong Kong',
    'HU' => 'Hungary',
    'IS' => 'Iceland',
    'IN' => 'India',
    'ID' => 'Indonesia',
    'IR' => 'Iran, Islamic Republic Of',
    'IQ' => 'Iraq',
    'IE' => 'Ireland',
    'IM' => 'Isle Of Man',
    'IL' => 'Israel',
    'IT' => 'Italy',
    'JM' => 'Jamaica',
    'JP' => 'Japan',
    'JE' => 'Jersey',
    'JO' => 'Jordan',
    'KZ' => 'Kazakhstan',
    'KE' => 'Kenya',
    'KI' => 'Kiribati',
    'KR' => 'Korea',
    'KW' => 'Kuwait',
    'KG' => 'Kyrgyzstan',
    'LA' => 'Lao People\'s Democratic Republic',
    'LV' => 'Latvia',
    'LB' => 'Lebanon',
    'LS' => 'Lesotho',
    'LR' => 'Liberia',
    'LY' => 'Libyan Arab Jamahiriya',
    'LI' => 'Liechtenstein',
    'LT' => 'Lithuania',
    'LU' => 'Luxembourg',
    'MO' => 'Macao',
    'MK' => 'Macedonia',
    'MG' => 'Madagascar',
    'MW' => 'Malawi',
    'MY' => 'Malaysia',
    'MV' => 'Maldives',
    'ML' => 'Mali',
    'MT' => 'Malta',
    'MH' => 'Marshall Islands',
    'MQ' => 'Martinique',
    'MR' => 'Mauritania',
    'MU' => 'Mauritius',
    'YT' => 'Mayotte',
    'MX' => 'Mexico',
    'FM' => 'Micronesia, Federated States Of',
    'MD' => 'Moldova',
    'MC' => 'Monaco',
    'MN' => 'Mongolia',
    'ME' => 'Montenegro',
    'MS' => 'Montserrat',
    'MA' => 'Morocco',
    'MZ' => 'Mozambique',
    'MM' => 'Myanmar',
    'NA' => 'Namibia',
    'NR' => 'Nauru',
    'NP' => 'Nepal',
    'NL' => 'Netherlands',
    'AN' => 'Netherlands Antilles',
    'NC' => 'New Caledonia',
    'NZ' => 'New Zealand',
    'NI' => 'Nicaragua',
    'NE' => 'Niger',
    'NG' => 'Nigeria',
    'NU' => 'Niue',
    'NF' => 'Norfolk Island',
    'MP' => 'Northern Mariana Islands',
    'NO' => 'Norway',
    'OM' => 'Oman',
    'PK' => 'Pakistan',
    'PW' => 'Palau',
    'PS' => 'Palestinian Territory, Occupied',
    'PA' => 'Panama',
    'PG' => 'Papua New Guinea',
    'PY' => 'Paraguay',
    'PE' => 'Peru',
    'PH' => 'Philippines',
    'PN' => 'Pitcairn',
    'PL' => 'Poland',
    'PT' => 'Portugal',
    'PR' => 'Puerto Rico',
    'QA' => 'Qatar',
    'RE' => 'Reunion',
    'RO' => 'Romania',
    'RU' => 'Russian Federation',
    'RW' => 'Rwanda',
    'BL' => 'Saint Barthelemy',
    'SH' => 'Saint Helena',
    'KN' => 'Saint Kitts And Nevis',
    'LC' => 'Saint Lucia',
    'MF' => 'Saint Martin',
    'PM' => 'Saint Pierre And Miquelon',
    'VC' => 'Saint Vincent And Grenadines',
    'WS' => 'Samoa',
    'SM' => 'San Marino',
    'ST' => 'Sao Tome And Principe',
    'SA' => 'Saudi Arabia',
    'SN' => 'Senegal',
    'RS' => 'Serbia',
    'SC' => 'Seychelles',
    'SL' => 'Sierra Leone',
    'SG' => 'Singapore',
    'SK' => 'Slovakia',
    'SI' => 'Slovenia',
    'SB' => 'Solomon Islands',
    'SO' => 'Somalia',
    'ZA' => 'South Africa',
    'GS' => 'South Georgia And Sandwich Isl.',
    'ES' => 'Spain',
    'LK' => 'Sri Lanka',
    'SD' => 'Sudan',
    'SR' => 'Suriname',
    'SJ' => 'Svalbard And Jan Mayen',
    'SZ' => 'Swaziland',
    'SE' => 'Sweden',
    'CH' => 'Switzerland',
    'SY' => 'Syrian Arab Republic',
    'TW' => 'Taiwan',
    'TJ' => 'Tajikistan',
    'TZ' => 'Tanzania',
    'TH' => 'Thailand',
    'TL' => 'Timor-Leste',
    'TG' => 'Togo',
    'TK' => 'Tokelau',
    'TO' => 'Tonga',
    'TT' => 'Trinidad And Tobago',
    'TN' => 'Tunisia',
    'TR' => 'Turkey',
    'TM' => 'Turkmenistan',
    'TC' => 'Turks And Caicos Islands',
    'TV' => 'Tuvalu',
    'UG' => 'Uganda',
    'UA' => 'Ukraine',
    'AE' => 'United Arab Emirates',
    'UM' => 'United States Outlying Islands',
    'UY' => 'Uruguay',
    'UZ' => 'Uzbekistan',
    'VU' => 'Vanuatu',
    'VE' => 'Venezuela',
    'VN' => 'Viet Nam',
    'VG' => 'Virgin Islands, British',
    'VI' => 'Virgin Islands, U.S.',
    'WF' => 'Wallis And Futuna',
    'EH' => 'Western Sahara',
    'YE' => 'Yemen',
    'ZM' => 'Zambia',
    'ZW' => 'Zimbabwe',
);
$possibleDiscovery = TDOTeamAccount::getPossibleDiscoveryAnswers();
$systemSettingTeamMonthlyPricePerUser = TDOUtil::getStringSystemSetting('SYSTEM_SETTING_TEAM_MONTHLY_PRICE_PER_USER', DEFAULT_SYSTEM_SETTING_TEAM_MONTHLY_PRICE_PER_USER);
$systemSettingTeamYearlyPricePerUser = TDOUtil::getStringSystemSetting('SYSTEM_SETTING_TEAM_YEARLY_PRICE_PER_USER', DEFAULT_SYSTEM_SETTING_TEAM_YEARLY_PRICE_PER_USER);
?>
<header class="main-header">
    <div class="container-bt">
        <div class="row">
            <div class="col-md-3 visible-md visible-lg">
                <a href="/" class="btn-default btn-size-sm btn-info"><?php _e('Get a Personal Account'); ?></a>
            </div>
            <div class="col-md-6">
                <div class="logo-wrapper">
                    <a href="/" class="main-home-link">
                        <img src="/images/todo-cloud-landing-page-logo.png" alt="Todo Cloud" class="small-s" />
                        <img src="/images/todo-cloud-landing-page-logo@2x.png" alt="Todo Cloud" class="retina-s"/>
                    </a>
                </div>
            </div>
            <div class="col-md-3 text-right visible-md visible-lg">
                <?php if($userid) : ?>
                <a href="/?appSettings=show&option=general" class="btn-default btn-size-sm btn-green"><?php _e('Settings'); ?></a>
                <?php else : ?>
                <a href="/?sign-in" class="btn-default btn-size-sm btn-green"><?php _e('Sign In'); ?></a>
                <?php endif; ?>
            </div>
            <div class="hidden-md hidden-lg text-center col-sm-12">
                <a href="#" class="btn-default btn-size-sm btn-info"><?php _e('Try Todo for Business'); ?></a>
                <?php if($userid) : ?>
                    <a href="/?appSettings=show&option=general" class="btn-default btn-size-sm btn-green"><?php _e('Settings'); ?></a>
                <?php else : ?>
                    <a href="/?sign-in" class="btn-default btn-size-sm btn-green m-l-20"><?php _e('Sign In'); ?></a>
                <?php endif; ?>
            </div>
            <div class="clearfix"></div>
        </div>
    </div>
</header>
<div class="main-wrapper">
    <div class="main-container-wrapper">
        <div class="main-container tfb-sign-up">
            <div class="container-bt">
                <div class="row">
                    <h1 class="slogan"><?php _e('The Best Collaborative To-do list and Task Manager Service'); ?></h1>
                    <div class="col-md-12">
                        <div class="auth-forms-wrapper todo-for-business clearfix">
                            <div class="col-md-8">
                                <form action="#" onsubmit="return false;">
                                    <section class="team-create-account-info row">
                                        <div class="col-sm-12">
                                            <h3 class="m-t-0">1. <?php _e('Select administrator account'); ?></h3>
                                            <p><?php _e('You need to either create a new Todo Cloud Account or login into an existing one.'); ?></p>
                                        </div>
                                        <div class="col-sm-6">
                                            <label id="first_name_label" class="team_create_input_label" for="first_name"><?php _e('First Name'); ?></label>
                                            <input type="text" id="first_name" onchange="validateFirstName()" autofocus/>

                                            <div class="input_status" id="first_name_status"></div>
                                        </div>
                                        <div class="col-sm-6">
                                            <label id="last_name_label" class="team_create_input_label" for="last_name"><?php _e('Last Name'); ?></label>
                                            <input type="text" id="last_name" onchange="validateLastName()" />

                                            <div class="input_status" id="last_name_status"></div>
                                        </div>
                                        <div class="col-sm-6">
                                            <label id="email_label" class="team_create_input_label" for="email"><?php _e('Email'); ?></label>
                                            <input type="email" id="email" onchange="validateEmail()" />

                                            <div class="input_status" id="email_status"></div>
                                        </div>
                                        <div class="col-sm-3">
                                            <label id="password_1_label" class="team_create_input_label" for="password_1"><?php _e('Password'); ?></label>
                                            <input type="password" id="password_1" onchange="validatePasswords()" />

                                            <div class="input_status" id="password_status"></div>
                                        </div>
                                        <div class="col-sm-3">
                                            <label id="password_2_label" class="team_create_input_label" for="verifyPassword"><?php _e('Confirm Password'); ?></label>
                                            <input type="password" id="verifyPassword" onchange="validateConfirmPasswords()" />

                                            <div class="input_status" id="confirm_password_status"></div>
                                        </div>
                                        <div>
                                            <div class="input_status" id="sign_up_status"></div>
                                            <div class="input_status" id="error_status_message"></div>
                                        </div>
                                    </section>

                                    <!-- ABOUT YOUR TEAM -->
                                    <section class="team-create-about">
                                        <h3>2. <?php _e('Tell us about your team/organization'); ?></h3>
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
                                                                <small>$<?php echo number_format(TDOUtil::getStringSystemSetting("SYSTEM_SETTING_TEAM_MONTHLY_PRICE_PER_USER", DEFAULT_SYSTEM_SETTING_TEAM_MONTHLY_PRICE_PER_USER), 2); ?> <?php _e('per user / month'); ?></small>
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
                                                                <small>$<?php echo number_format(round(TDOUtil::getStringSystemSetting("SYSTEM_SETTING_TEAM_YEARLY_PRICE_PER_USER", DEFAULT_SYSTEM_SETTING_TEAM_YEARLY_PRICE_PER_USER) / 12, 2), 2);?> <?php _e('per user / month'); ?></small>

                                                            </label>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div id="team_create_pricing_options_custom" class="team_item_hidden">
                                                    <?php _e('You have entered more than 500 members (the maximum allowed using this page). Please contact us (<a href="mailto:business@appigo.com?subject=Todo Cloud High Volume Licensing">business@appigo.com</a>) to complete your team purchase.'); ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="team_create_pricing_detail_paragraph">
                                            <?php printf(_('Choosing the yearly option will save you %s compared to the monthly option.'),
                                                '<span id="yearly_savings">$' . (($systemSettingTeamMonthlyPricePerUser * 12) - $systemSettingTeamYearlyPricePerUser) . '</span>'
                                            ); ?>
                                        </div>
                                    </section>

                                    <!-- CONTACT INFORMATION -->

                                    <section class="team-create-contact-info">
                                        <h3>3. <?php _e('Contact Information'); ?></h3>
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

                                    <!-- BILLING INFORMATION -->

                                    <section class="team-create-contact-info">
                                        <h3 class="pull-left">4. <?php _e('Billing Information'); ?></h3>
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

                                    <section class="team-create-submit-form">
                                        <div class="row">

                                            <div class="col-sm-8">
                                                <label class="team_create_input_label" >&nbsp;</label>
                                                <div class="colteam_create_input_row">
                                                    <div class="team_tos_agree_field">
                                                        <label id="team_tos_agree_label" for="team_tos_agree">
                                                            <input id="team_tos_agree" type="checkbox"  name="team_tos_agree" onclick="updatePurchaseButtonEnablement(event, this, false);"/>
                                                            <?php _e('I agree to the'); ?> <a href="/terms" target="_blank" title="<?php _e('Review the Terms of Service'); ?>"> <?php _e('Todo Cloud Terms of Service'); ?></a>.</label>
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
                                            <div id="team_validate_error" class="team_validation_error_message team_item_hidden"><?php _e('Please enter the missing information above indicated in red.'); ?></div>
                                        </div>
                                        <input id="stripe_token" type="hidden" value=""/>
                                    </section>
                                </form>
                            </div>
                            <div class="col-md-4">
                                <div class="info-block b-border">
                                    <h3><?php _e('What&#39;s included in the free trial?'); ?></h3>
                                    <p><?php _e('Full access to Todo for Business.'); ?></p>

                                    <p><?php _e('Admin controls to add members and create shared lists.'); ?></p>

                                    <p><?php _e('Free Todo Cloud app on iOS, OS X, Android and Web.'); ?></p>

                                    <p><?php _e('Doesn&#39;t require IT.'); ?></p>
                                </div>
                                <div class="info-block b-border">
                                    <h3><?php _e('Who is using Todo for Business?'); ?></h3>

                                    <p><?php _e('Teams at these companies are using Todo'); ?></p>
                                    <div class="team_create_detail_paragraph">
                                        <div class="companies-logo nike"></div>
                                        <div class="companies-logo disney"></div>
                                        <div class="companies-logo virgin-mobile"></div>
                                        <div class="companies-logo atnt"></div>
                                    </div>
                                </div>
                                <div class="info-block">
                                    <h3><?php _e('Will my credit card charged right now?'); ?></h3>

<?php
	$trialIntervalSetting = TDOUtil::getStringSystemSetting('SYSTEM_SETTING_TEAM_TRIAL_PERIOD_DATE_INTERVAL', DEFAULT_SYSTEM_SETTING_TEAM_TRIAL_PERIOD_DATE_INTERVAL);
	$trialInterval = new DateInterval($trialIntervalSetting);
	$numOfDays = $trialInterval->format('%d');
?>

                                    <p><?php printf(_('Don&#39;t worry, you won&#39;t be charged now. You&#39;ll only be charged if you continue using Todo for Business after your %s  day trial ends. Your trial will end on &quot;%s&quot;.'),
                                            $numOfDays,
                                            date('F d, Y', strtotime('+' . $numOfDays . 'days'))); ?></p>

                                    <h3><?php _e('Can I change my payment method?'); ?></h3>

                                    <p><?php _e('Of course, you can change your payment method at any time.'); ?></p>

                                    <h3><?php _e('Can I cancel my trial at any time?'); ?></h3>

                                    <p><?php _e('Yes, you under no obligation to continue to use Todo for Business. You can cancel at any time.'); ?></p>
                                    <h3><?php _e('Accepted payment types'); ?></h3>
                                    <div class="team_create_detail_paragraph">
                                        <div class="credit-cards-list ccl-visa"></div>
                                        <div class="credit-cards-list ccl-mastercard"></div>
                                        <div class="credit-cards-list ccl-amex"></div>
                                        <div class="credit-cards-list ccl-discover"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="clearfix"></div>
                    <div class="col-md-12">
                        <span class="terms_agree_statement">
                            <?php printf(_('By clicking Sign Up, you agree to our %s and that you have read and understand our %s'),
                                '<a href="/terms" target="_blank">'._('Terms of Service').'</a>',
                                '<a href="/privacy" target="_blank">'._('Privacy Policy').'</a>'
                            ); ?>
                        </span>
                        <div class="text-center">
                            <label class="emailoptin-label">
                                <input type="checkbox" id="emailoptin" checked/>
                                <?php _e('Receive helpful information about using Todo. We promise not to spam you and you can unsubscribe at any time.'); ?>

                            </label>
                        </div>
                        <div class="change-language additional-info">
                            <select>
                                <?php
                                $language_labels = TDOInternalization::getLanguageLabels();
                                $language = DEFAULT_LOCALE;
                                if ($_COOKIE['interface_language']) {
                                    $language = $_COOKIE['interface_language'];
                                }
                                foreach(TDOInternalization::getAvailableLocales() as $k=>$v) : ?>
                                    <option value="<?php echo $v; ?>" <?php echo ($language == $v) ? 'selected' : ''; ?>><?php echo $language_labels[$k]; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="<?php echo TP_JS_PATH_LANDING_PAGE_FUNCTIONS; ?>"></script>
<script src="/js/jquery.webui-popover.js"></script>
<script type="text/javascript" src="https://js.stripe.com/v1/"></script>
<script type="text/javascript">
    Stripe.setPublishableKey('<?php echo APPIGO_STRIPE_PUBLIC_KEY; ?>');
</script>
<script type="text/javascript">
    var monthlyTeamPrice = <?php echo $systemSettingTeamMonthlyPricePerUser;?>;
    var yearlyTeamPrice = <?php echo $systemSettingTeamYearlyPricePerUser;?>;
</script>