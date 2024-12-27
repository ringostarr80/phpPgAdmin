<?php

declare(strict_types=1);

namespace PhpPgAdmin;

use Symfony\Component\Yaml\Parser as YamlParser;

/**
 * @phpstan-type ConfigData=array{
 *  'default_lang'?: string,
 *  'left_width'?: int,
 *  'servers'?: array<mixed>
 * }
 */
class Config
{
    /**
     * @var array<string>|null
     */
    private static ?array $availableLocales = null;
    /**
     * @var ConfigData|null
     */
    private static ?array $conf = null;
    /**
     * @var array{'locale'?: string, 'theme'?: string}
     */
    private static array $data = [];

    /**
     * @return array<string>
     */
    public static function getAvailableLocales(): array
    {
        $localeDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'locale';
        $langDir = dir($localeDir);
        if ($langDir === false) {
            return [];
        }

        self::$availableLocales = [];
        while (false !== ($entry = $langDir->read())) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            if (!is_dir($localeDir . DIRECTORY_SEPARATOR . $entry)) {
                continue;
            }
            if (preg_match('/^(?P<language>[a-z]{2})_(?P<region>[A-Z]{2})$/', $entry, $matches)) {
                self::$availableLocales[] = $entry;
            }
        }

        return self::$availableLocales;
    }

    private static function getNormalizedLocaleFromLocaleOrLanguage(string $localeOrLanguage): ?string
    {
        if (preg_match('/^(?P<language>[a-z]{2})[_-](?P<region>[A-Z]{2})$/i', $localeOrLanguage, $matches)) {
            return strtolower($matches['language']) . '_' . strtoupper($matches['region']);
        }

        return match (strtolower($localeOrLanguage)) {
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
            'ukrainian' => 'uk_UA',
            default => null,
        };
    }

    public static function leftWidth(): int
    {
        $conf = self::tryGetConfigFileData();
        return $conf['left_width'] ?? 200;
    }

    public static function locale(): string
    {
        if (!isset(self::$data['locale'])) {
            $locale = null;
            if (
                isset($_REQUEST['language']) &&
                is_string($_REQUEST['language']) &&
                self::languageIsAvailable($_REQUEST['language'])
            ) {
                $locale = self::getNormalizedLocaleFromLocaleOrLanguage($_REQUEST['language']);
                if (!is_null($locale)) {
                    setcookie(
                        name: 'webdbLanguage',
                        value: $locale,
                        expires_or_options: time() + 31_536_000 // 1 year.
                    );
                }
            }

            if (
                is_null($locale) &&
                isset($_SESSION['webdbLanguage']) &&
                is_string($_SESSION['webdbLanguage']) &&
                self::languageIsAvailable($_SESSION['webdbLanguage'])
            ) {
                $locale = self::getNormalizedLocaleFromLocaleOrLanguage($_SESSION['webdbLanguage']);
            }

            if (
                is_null($locale) &&
                isset($_COOKIE['webdbLanguage']) &&
                is_string($_COOKIE['webdbLanguage']) &&
                self::languageIsAvailable($_COOKIE['webdbLanguage'])
            ) {
                $locale = self::getNormalizedLocaleFromLocaleOrLanguage($_COOKIE['webdbLanguage']);
            }

            $conf = self::tryGetConfigFileData();
            if (
                is_null($locale) &&
                isset($conf['default_lang']) &&
                $conf['default_lang'] === 'auto' &&
                isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) &&
                is_string($_SERVER['HTTP_ACCEPT_LANGUAGE'])
            ) {
                // extract acceptable language tags
                // (http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html#sec14.4)
                preg_match_all(
                    '/\s*(?P<language>[a-z]{1,8}(?:-[a-z]{1,8})*)(?:;q=(?P<quality>[01](?:.\d{0,3})?))?\s*(?:,|$)/',
                    strtolower($_SERVER['HTTP_ACCEPT_LANGUAGE']),
                    $matches,
                    PREG_SET_ORDER
                );

                $acceptLanguages = [];
                foreach ($matches as $match) {  // $match[1] = language tag, [2] = quality
                    if (!isset($match['quality'])) {
                        $match['quality'] = 1;  // Default quality to 1
                    }
                    if ($match['quality'] <= 0 || $match['quality'] > 1) {
                        continue;
                    }
                    if (!self::languageIsAvailable($match['language'])) {
                        continue;
                    }
                    $normalizedLocale = self::getNormalizedLocaleFromLocaleOrLanguage($match['language']);
                    if (is_null($normalizedLocale)) {
                        continue;
                    }

                    $acceptLanguages[$match['quality']] = $normalizedLocale;
                }

                if (!empty($acceptLanguages)) {
                    // Sort acceptable languages by quality
                    krsort($acceptLanguages, SORT_NUMERIC);
                    $locale = self::getNormalizedLocaleFromLocaleOrLanguage(reset($acceptLanguages));
                    unset($acceptLanguages);
                }
            }

            // 5. Otherwise resort to the default set in the config file
            if (
                is_null($locale) &&
                (
                    isset($conf['default_lang']) &&
                    $conf['default_lang'] !== 'auto' &&
                    self::languageIsAvailable($conf['default_lang'])
                )
            ) {
                $locale = self::getNormalizedLocaleFromLocaleOrLanguage($conf['default_lang']);
            }

            // 6. Otherwise, default to english.
            if (is_null($locale)) {
                $locale = 'en_US';
            }

            $_SESSION['webdbLanguage'] = $locale;
            self::$data['locale'] = $locale;
        }

        return self::$data['locale'];
    }

    private static function languageIsAvailable(string $language): bool
    {
        $normalizedLocale = self::getNormalizedLocaleFromLocaleOrLanguage($language);
        if (is_null($normalizedLocale)) {
            return false;
        }
        $availableLocales = self::getAvailableLocales();
        foreach ($availableLocales as $locale) {
            if ($locale === $normalizedLocale) {
                return true;
            }
        }

        return false;
    }

    public static function theme(): string
    {
        if (!isset(self::$data['theme'])) {
            self::$data['theme'] = 'default';
            if (
                isset($_REQUEST['theme']) &&
                is_string($_REQUEST['theme']) &&
                Themes::cssExists($_REQUEST['theme'])
            ) {
                setcookie(
                    name: 'ppaTheme',
                    value: $_REQUEST['theme'],
                    expires_or_options: time() + 31_536_000 // 1 year.
                );
                self::$data['theme'] = $_REQUEST['theme'];
            }

            if (
                isset($_SESSION['ppaTheme']) &&
                is_string($_SESSION['ppaTheme']) &&
                Themes::cssExists($_SESSION['ppaTheme'])
            ) {
                self::$data['theme'] = $_SESSION['ppaTheme'];
            }

            if (
                isset($_COOKIE['ppaTheme']) &&
                is_string($_COOKIE['ppaTheme']) &&
                Themes::cssExists($_COOKIE['ppaTheme'])
            ) {
                self::$data['theme'] = $_COOKIE['ppaTheme'];
            }

            if (isset($_REQUEST['server']) && is_string($_REQUEST['server'])) {
                $serverIdTheme = self::tryGetThemeByServerId($_REQUEST['server']);
                if ($serverIdTheme !== '') {
                    self::$data['theme'] = $serverIdTheme;
                }
            }

            $_SESSION['ppaTheme'] = self::$data['theme'];
        }

        return self::$data['theme'];
    }

    /**
     * @return ConfigData
     */
    private static function tryGetConfigFileData(): array
    {
        if (is_null(self::$conf)) {
            self::$conf = [];

            $yamlConfigFile = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'conf' . DIRECTORY_SEPARATOR . 'config.yaml';
            if (!file_exists($yamlConfigFile)) {
                $yamlConfigFile = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'conf' . DIRECTORY_SEPARATOR . 'config.yml';
            }
            if (file_exists($yamlConfigFile)) {
                $yaml = (new YamlParser())->parseFile($yamlConfigFile);
                if (is_array($yaml)) {
                    if (isset($yaml['default_lang']) && is_string($yaml['default_lang'])) {
                        self::$conf['default_lang'] = $yaml['default_lang'];
                    }
                    if (isset($yaml['left_width']) && is_int($yaml['left_width'])) {
                        self::$conf['left_width'] = $yaml['left_width'];
                    }
                }
            }
        }

        return self::$conf;
    }

    private static function tryGetThemeByServerId(string $serverId): string
    {
        $conf = self::tryGetConfigFileData();

        if (!isset($conf['servers'])) {
            return '';
        }

        $tmpTheme = '';
        foreach ($conf['servers'] as $info) {
            if (!is_array($info)) {
                continue;
            }
            if (!isset($info['host'], $info['port'], $info['sslmode'])) {
                continue;
            }
            if (!is_string($info['host']) || !is_numeric($info['port']) || !is_string($info['sslmode'])) {
                continue;
            }
            if ($serverId !== $info['host'] . ':' . $info['port'] . ':' . $info['sslmode']) {
                continue;
            }

            if (!isset($info['theme']) || !is_array($info['theme'])) {
                continue;
            }

            if (
                isset($info['theme']['default']) &&
                is_string($info['theme']['default']) &&
                Themes::cssExists($info['theme']['default'])
            ) {
                $tmpTheme = $info['theme']['default'];
            }

            if (
                isset($_REQUEST['database'])
                && is_string($_REQUEST['database'])
                && is_array($info['theme']['db'])
                && isset($info['theme']['db'][$_REQUEST['database']])
                && is_string($info['theme']['db'][$_REQUEST['database']])
                && Themes::cssExists($info['theme']['db'][$_REQUEST['database']])
            ) {
                $tmpTheme = $info['theme']['db'][$_REQUEST['database']];
            }

            if (
                isset($info['username'])
                && is_string($info['username'])
                && is_array($info['theme']['user'])
                && isset($info['theme']['user'][$info['username']])
                && is_string($info['theme']['user'][$info['username']])
                && Themes::cssExists($info['theme']['user'][$info['username']])
            ) {
                $tmpTheme = $info['theme']['user'][$info['username']];
            }
        }

        return $tmpTheme;
    }
}
