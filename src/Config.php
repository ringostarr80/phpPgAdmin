<?php

declare(strict_types=1);

namespace PhpPgAdmin;

class Config
{
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

            $_SESSION['ppaTheme'] = self::$data['theme'];
        }

        return self::$data['theme'];
    }
}
