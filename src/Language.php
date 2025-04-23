<?php

declare(strict_types=1);

namespace PhpPgAdmin;

class Language
{
    /**
     * @return array<string, string>
     */
    public static function getAvailableLanguageIdsWithLocales(): array
    {
        return [
            'afrikaans' => 'af_ZA',
            'arabic' => 'ar_SA',
            'catalan' => 'ca_ES',
            'chinese-zh-cn' => 'zh_CN',
            'chinese-zh-tw' => 'zh_TW',
            'czech' => 'cs_CZ',
            'danish' => 'da_DK',
            'dutch' => 'nl_NL',
            'english' => 'en_US',
            'french' => 'fr_FR',
            'galician' => 'gl_ES',
            'german' => 'de_DE',
            'greek' => 'el_GR',
            'hebrew' => 'he_IL',
            'hungarian' => 'hu_HU',
            'italian' => 'it_IT',
            'japanese' => 'ja_JP',
            'lithuanian' => 'lt_LT',
            'mongol' => 'mn_MN',
            'polish' => 'pl_PL',
            'portuguese-br' => 'pt_BR',
            'portuguese-pt' => 'pt_PT',
            'romanian' => 'ro_RO',
            'russian' => 'ru_RU',
            'slovak' => 'sk_SK',
            'spanish' => 'es_ES',
            'swedish' => 'sv_SE',
            'turkish' => 'tr_TR',
            'ukrainian' => 'uk_UA'
        ];
    }

    public static function setLocale(string $locale): void
    {
        putenv("LANGUAGE={$locale}");
        putenv("LC_ALL={$locale}.UTF-8");
        setlocale(LC_ALL, ["{$locale}.UTF-8", $locale, substr($locale, 0, 2)]);
        textdomain('messages');
    }
}
