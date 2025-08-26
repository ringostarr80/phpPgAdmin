<?php

declare(strict_types=1);

namespace PhpPgAdmin;

final class RequestParameter
{
    public static function getString(string $name, ?string $default = null): ?string
    {
        $value = filter_input(INPUT_GET, $name, FILTER_DEFAULT);
        if (!is_string($value)) {
            $value = filter_input(INPUT_POST, $name, FILTER_DEFAULT);
        }
        if (!is_string($value)) {
            $value = $default;
        }

        return $value;
    }
}
