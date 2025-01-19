<?php

declare(strict_types=1);

namespace PhpPgAdmin;

use PhpPgAdmin\Website;

class Session extends Website
{
    public const NAME = 'PPA_ID';

    public static function start(): void
    {
        if (Config::extraSessionSecurity()) {
            if (ini_get('session.auto_start')) {
                if (session_name() !== self::NAME) {
                    $setting = strtolower(ini_get('session.cookie_samesite') ?: '');
                    if ($setting !== 'lax' && $setting !== 'strict') {
                        session_destroy();
                        session_name(self::NAME);
                        ini_set('session.cookie_samesite', 'Strict');
                        session_start();
                    }
                }
            } elseif (session_status() !== PHP_SESSION_ACTIVE) {
                session_name(self::NAME);
                ini_set('session.cookie_samesite', 'Strict');
                session_start();
            }
        } elseif (!ini_get('session.auto_start')) {
            session_name(self::NAME);
            session_start();
        }
    }
}
