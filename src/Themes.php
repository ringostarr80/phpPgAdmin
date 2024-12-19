<?php

declare(strict_types=1);

namespace PhpPgAdmin;

class Themes
{
    /**
     * @return array<string, string>
     */
    public static function available(): array
    {
        return [
            'default' => 'Default',
            'cappuccino' => 'Cappuccino',
            'gotar' => 'Blue/Green',
            'bootstrap' => 'Bootstrap3',
            'dark' => 'Dark'
        ];
    }

    public static function cssExists(string $theme): bool
    {
        return is_file(__DIR__ . "/Themes/{$theme}/global.css");
    }
}
