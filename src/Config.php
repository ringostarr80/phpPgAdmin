<?php

declare(strict_types=1);

namespace PhpPgAdmin;

use Symfony\Component\Yaml\Parser as YamlParser;

class Config
{
    /**
     * @var array<mixed>|null
     */
    private static ?array $conf = null;
    /**
     * @var array{'theme'?: string}
     */
    private static array $data = [];

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

        $yamlConfigFile = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'conf' . DIRECTORY_SEPARATOR . 'config.yaml';
        if (!file_exists($yamlConfigFile)) {
            $yamlConfigFile = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'conf' . DIRECTORY_SEPARATOR . 'config.yml';
        }
        if (file_exists($yamlConfigFile)) {
            $yaml = (new YamlParser())->parseFile($yamlConfigFile);
            print '<pre>';
            var_dump($yaml);
            die();
        }

        return self::$data['theme'];
    }

    private static function tryGetThemeByServerId(string $serverId): string
    {
        self::$conf = [];

        if (!isset(self::$conf['servers']) || !is_array(self::$conf['servers'])) {
            return '';
        }

        $tmpTheme = '';
        foreach (self::$conf['servers'] as $info) {
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
