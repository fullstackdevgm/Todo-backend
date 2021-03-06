<?php
/**
 * Translation system
 */
if (isset($_SERVER) && !empty($_SERVER['REQUEST_URI']) && ($_SERVER['REQUEST_URI'] == '' || strpos($_SERVER['REQUEST_URI'], 'pigeon47') === FALSE)) {
    include_once('TodoOnline/base_sdk.php');

    include_once('TodoOnline/php/SessionHandler.php');
    include_once('TodoOnline/classes/TDOInternalization.php');

    $session = TDOSession::getInstance();
    $user_locale = FALSE;
    if ($session && $session->isLoggedIn()) {
        if ($session->getUserId()) {
            $user_locale = TDOUser::getLocaleForUser($session->getUserId());
            if (!isset($_COOKIE['interface_language'])) {
                setcookie('interface_language', $user_locale, strtotime('+1 year'), '/');
            }
        }
    } elseif (isset($_COOKIE['interface_language']) && $_COOKIE['interface_language'] !== '') {
        $user_locale = $_COOKIE['interface_language'];
    } else {
        $user_locale = DEFAULT_LOCALE;
    }
    if ($user_locale && $user_locale !== '') {
        $locale = $user_locale;
    } else {
        $locale = TDOInternalization::getUserBestMatchLocale();
    }
    define('DEFAULT_LOCALE_IN_USE', $locale);
    setlocale(LC_ALL, $locale . '.' . DEFAULT_LOCALE_ENCODING);
    bindtextdomain(DEFAULT_LOCALE_TEXTDOMAIN, BASE_DIR.'/../locale');
    textdomain(DEFAULT_LOCALE_TEXTDOMAIN);
}else{
    setlocale(LC_ALL, DEFAULT_LOCALE . '.' . DEFAULT_LOCALE_ENCODING);
    define('DEFAULT_LOCALE_IN_USE', DEFAULT_LOCALE);
    bindtextdomain(DEFAULT_LOCALE_TEXTDOMAIN, BASE_DIR.'/../locale');
    textdomain(DEFAULT_LOCALE_TEXTDOMAIN);
}


function _e($str)
{
    echo gettext($str);
}

/*Translates for dynamic strings*/
_('Jan');
_('Feb');
_('Mar');
_('Apr');
_('May');
_('Jun');
_('Jul');
_('Aug');
_('Sep');
_('Oct');
_('Nov');
_('Dec');

_('Mon');
_('Tue');
_('Wed');
_('Thu');
_('Fri');
_('Sat');
_('Sun');

_('Africa');
_('Abidjan');
_('Accra');
_('Addis Ababa');
_('Algiers');
_('Asmara');
_('Bamako');
_('Bangui');
_('Banjul');
_('Bissau');
_('Blantyre');
_('Brazzaville');
_('Bujumbura');
_('Cairo');
_('Casablanca');
_('Ceuta');
_('Conakry');
_('Dakar');
_('Dar es Salaam');
_('Djibouti');
_('Douala');
_('El Aaiun');
_('Freetown');
_('Gaborone');
_('Harare');
_('Johannesburg');
_('Juba');
_('Kampala');
_('Khartoum');
_('Kigali');
_('Kinshasa');
_('Lagos');
_('Libreville');
_('Lome');
_('Luanda');
_('Lubumbashi');
_('Lusaka');
_('Malabo');
_('Maputo');
_('Maseru');
_('Mbabane');
_('Mogadishu');
_('Monrovia');
_('Nairobi');
_('Ndjamena');
_('Niamey');
_('Nouakchott');
_('Ouagadougou');
_('Porto-Novo');
_('Sao Tome');
_('Tripoli');
_('Tunis');
_('Windhoek');
_('America');
_('Adak');
_('Anchorage');
_('Anguilla');
_('Antigua');
_('Araguaina');
_('Argentina');
_('Buenos Aires');
_('Catamarca');
_('Cordoba');
_('Jujuy');
_('La Rioja');
_('Mendoza');
_('Rio Gallegos');
_('Salta');
_('San Juan');
_('San Luis');
_('Tucuman');
_('Ushuaia');
_('Aruba');
_('Asuncion');
_('Atikokan');
_('Bahia');
_('Bahia Banderas');
_('Barbados');
_('Belem');
_('Belize');
_('Blanc-Sablon');
_('Boa Vista');
_('Bogota');
_('Boise');
_('Cambridge Bay');
_('Campo Grande');
_('Cancun');
_('Caracas');
_('Cayenne');
_('Cayman');
_('Chicago');
_('Chihuahua');
_('Costa Rica');
_('Creston');
_('Cuiaba');
_('Curacao');
_('Danmarkshavn');
_('Dawson');
_('Dawson Creek');
_('Denver');
_('Detroit');
_('Dominica');
_('Edmonton');
_('Eirunepe');
_('El Salvador');
_('Fortaleza');
_('Glace Bay');
_('Godthab');
_('Goose Bay');
_('Grand Turk');
_('Grenada');
_('Guadeloupe');
_('Guatemala');
_('Guayaquil');
_('Guyana');
_('Halifax');
_('Havana');
_('Hermosillo');
_('Indiana');
_('Indianapolis');
_('Knox');
_('Marengo');
_('Petersburg');
_('Tell City');
_('Vevay');
_('Vincennes');
_('Winamac');
_('Inuvik');
_('Iqaluit');
_('Jamaica');
_('Juneau');
_('Louisville');
_('Louisville');
_('Monticello');
_('Kralendijk');
_('La Paz');
_('Lima');
_('Los Angeles');
_('Lower Princes');
_('Maceio');
_('Managua');
_('Manaus');
_('Marigot');
_('Martinique');
_('Matamoros');
_('Mazatlan');
_('Menominee');
_('Merida');
_('Metlakatla');
_('Mexico City');
_('Miquelon');
_('Moncton');
_('Monterrey');
_('Montevideo');
_('Montserrat');
_('Nassau');
_('New York');
_('Nipigon');
_('Nome');
_('Noronha');
_('North Dakota');
_('Beulah');
_('Center');
_('New Salem');
_('Ojinaga');
_('Panama');
_('Pangnirtung');
_('Paramaribo');
_('Phoenix');
_('Port-au-Prince');
_('Port of Spain');
_('Porto Velho');
_('Puerto Rico');
_('Rainy River');
_('Rankin Inlet');
_('Recife');
_('Regina');
_('Resolute');
_('Rio Branco');
_('Santa Isabel');
_('Santarem');
_('Santiago');
_('Santo Domingo');
_('Sao Paulo');
_('Scoresbysund');
_('Sitka');
_('St Barthelemy');
_('St Johns');
_('St Kitts');
_('St Lucia');
_('St Thomas');
_('St Vincent');
_('Swift Current');
_('Tegucigalpa');
_('Thule');
_('Thunder Bay');
_('Tijuana');
_('Toronto');
_('Tortola');
_('Vancouver');
_('Whitehorse');
_('Winnipeg');
_('Yakutat');
_('Yellowknife');
_('Antarctica');
_('Casey');
_('Davis');
_('DumontDUrville');
_('Macquarie');
_('Mawson');
_('McMurdo');
_('Palmer');
_('Rothera');
_('Syowa');
_('Troll');
_('Vostok');
_('Arctic');
_('Longyearbyen');
_('Asia');
_('Aden');
_('Almaty');
_('Amman');
_('Anadyr');
_('Aqtau');
_('Aqtobe');
_('Ashgabat');
_('Baghdad');
_('Bahrain');
_('Baku');
_('Bangkok');
_('Beirut');
_('Bishkek');
_('Brunei');
_('Chita');
_('Choibalsan');
_('Colombo');
_('Damascus');
_('Dhaka');
_('Dili');
_('Dubai');
_('Dushanbe');
_('Gaza');
_('Hebron');
_('Ho Chi Minh');
_('Hong Kong');
_('Hovd');
_('Irkutsk');
_('Jakarta');
_('Jayapura');
_('Jerusalem');
_('Kabul');
_('Kamchatka');
_('Karachi');
_('Kathmandu');
_('Khandyga');
_('Kolkata');
_('Krasnoyarsk');
_('Kuala Lumpur');
_('Kuching');
_('Kuwait');
_('Macau');
_('Magadan');
_('Makassar');
_('Manila');
_('Muscat');
_('Nicosia');
_('Novokuznetsk');
_('Novosibirsk');
_('Omsk');
_('Oral');
_('Phnom Penh');
_('Pontianak');
_('Pyongyang');
_('Qatar');
_('Qyzylorda');
_('Rangoon');
_('Riyadh');
_('Sakhalin');
_('Samarkand');
_('Seoul');
_('Shanghai');
_('Singapore');
_('Srednekolymsk');
_('Taipei');
_('Tashkent');
_('Tbilisi');
_('Tehran');
_('Thimphu');
_('Tokyo');
_('Ulaanbaatar');
_('Urumqi');
_('Ust-Nera');
_('Vientiane');
_('Vladivostok');
_('Yakutsk');
_('Yekaterinburg');
_('Yerevan');
_('Atlantic');
_('Azores');
_('Bermuda');
_('Canary');
_('Cape Verde');
_('Faroe');
_('Madeira');
_('Reykjavik');
_('South Georgia');
_('St Helena');
_('Stanley');
_('Australia');
_('Adelaide');
_('Brisbane');
_('Broken Hill');
_('Currie');
_('Darwin');
_('Eucla');
_('Hobart');
_('Lindeman');
_('Lord Howe');
_('Melbourne');
_('Perth');
_('Sydney');
_('Europe');
_('Amsterdam');
_('Andorra');
_('Athens');
_('Belgrade');
_('Berlin');
_('Bratislava');
_('Brussels');
_('Bucharest');
_('Budapest');
_('Busingen');
_('Chisinau');
_('Copenhagen');
_('Dublin');
_('Gibraltar');
_('Guernsey');
_('Helsinki');
_('Isle of Man');
_('Istanbul');
_('Jersey');
_('Kaliningrad');
_('Kiev');
_('Lisbon');
_('Ljubljana');
_('London');
_('Luxembourg');
_('Madrid');
_('Malta');
_('Mariehamn');
_('Minsk');
_('Monaco');
_('Moscow');
_('Oslo');
_('Paris');
_('Podgorica');
_('Prague');
_('Riga');
_('Rome');
_('Samara');
_('San Marino');
_('Sarajevo');
_('Simferopol');
_('Skopje');
_('Sofia');
_('Stockholm');
_('Tallinn');
_('Tirane');
_('Uzhgorod');
_('Vaduz');
_('Vatican');
_('Vienna');
_('Vilnius');
_('Volgograd');
_('Warsaw');
_('Zagreb');
_('Zaporozhye');
_('Zurich');
_('Indian');
_('Antananarivo');
_('Chagos');
_('Christmas');
_('Cocos');
_('Comoro');
_('Kerguelen');
_('Mahe');
_('Maldives');
_('Mauritius');
_('Mayotte');
_('Reunion');
_('Pacific');
_('Apia');
_('Auckland');
_('Bougainville');
_('Chatham');
_('Chuuk');
_('Easter');
_('Efate');
_('Enderbury');
_('Fakaofo');
_('Fiji');
_('Funafuti');
_('Galapagos');
_('Gambier');
_('Guadalcanal');
_('Guam');
_('Honolulu');
_('Johnston');
_('Kiritimati');
_('Kosrae');
_('Kwajalein');
_('Majuro');
_('Marquesas');
_('Midway');
_('Nauru');
_('Niue');
_('Norfolk');
_('Noumea');
_('Pago Pago');
_('Palau');
_('Pitcairn');
_('Pohnpei');
_('Port Moresby');
_('Rarotonga');
_('Saipan');
_('Tahiti');
_('Tarawa');
_('Tongatapu');
_('Wake');
_('Wallis');
_('UTC');