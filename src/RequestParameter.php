<?php

declare(strict_types=1);

namespace PhpPgAdmin;

final class RequestParameter
{
    /**
     * @param ?array<mixed> $default
     * @return ?array<mixed>
     */
    public static function getArray(string $name, ?array $default = null): ?array
    {
        $value = null;

        if (filter_has_var(INPUT_GET, $name) && is_array($_GET[$name])) {
            $value = $_GET[$name];
        }

        if (!is_array($value) && filter_has_var(INPUT_POST, $name) && is_array($_POST[$name])) {
            $value = $_POST[$name];
        }

        if (!is_array($value)) {
            $value = $default;
        }

        return $value;
    }

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
