<?php
/**
 *      TDOInternalization
 */

include_once('TodoOnline/base_sdk.php');

class TDOInternalization
{

    private static $available_locales = array(
        'en' => 'en_US',
//        'de' => 'de_DE',
//        'es' => 'es_ES',
//        'fi' => 'fi_FI',
//        'fr' => 'fr_FR',
//        'it' => 'it_IT',
//        'ja' => 'ja_JP',
//        'nl' => 'nl_NL',
//        'pt' => 'pt_PT',
//        'ru' => 'ru_RU',
//        'sv' => 'sv_SE',
        'zh_cn' => 'zh_CN',
//        'zh_tw' => 'zh_TW',
    );
    private static $language_labels = array(
        'en' => 'English',
//        'de' => 'Deutsch',
//        'es' => 'Spanish',
//        'fi' => 'Finnish-Testing',
//        'fr' => 'French',
//        'it' => 'Italian',
//        'ja' => 'Japanese',
//        'nl' => 'Dutch',
//        'pt' => 'Portuguese',
//        'ru' => 'Russian',
//        'sv' => 'Swedish',
        'zh_cn' => '中文(简体)',
//        'zh_tw' => 'Chinese (Taiwan)',
    );
    private $path_to_translations = '../todo_root/locale';

    function __construct()
    {
    }

    private function getLocales()
    {
        return self::$available_locales;
    }

    /**
     * @return array
     */
    public static function getAvailableLocales()
    {
        return self::$available_locales;
    }

    public static function getLanguageLabels()
    {
        return self::$language_labels;
    }

    /**
     * @param string $locale
     * @return bool
     */
    public static function isAvailableLocale($locale)
    {
        return in_array($locale, self::$available_locales);
    }

    /**
     * @return string
     */
    public static function getUserPreferredLocale()
    {
        return locale_accept_from_http($_SERVER['HTTP_ACCEPT_LANGUAGE']);
    }

    /**
     * @param bool $user_locale
     * @return string
     */
    public static function getUserBestMatchLocale($user_locale = FALSE)
    {
        $interface_locale = DEFAULT_LOCALE;
        $supported = self::$available_locales;
        if ($user_locale) {
            $user_locale = str_replace('-', '_', $user_locale);
            if (locale_get_primary_language($user_locale) !== 'zh') {
                if ($user_locale) {
                    $user_lang = locale_get_primary_language($user_locale);
                } else {
                    $user_lang = locale_get_primary_language(self::getUserPreferredLocale());
                }
            } else {
                $user_lang = mb_strtolower($user_locale);
            }
            if (array_key_exists($user_lang, $supported)) {
                $interface_locale = $supported[$user_lang];
            }
        }
        return $interface_locale;
    }


}