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
            'bootstrap' => 'Bootstrap3',
            'cappuccino' => 'Cappuccino',
            'dark' => 'Dark',
            'default' => 'Default',
            'gotar' => 'Blue/Green',
        ];
    }

    public static function cssExists(string $theme): bool
    {
        return is_file(dirname(__DIR__) . "/themes/{$theme}/global.css");
    }
}
