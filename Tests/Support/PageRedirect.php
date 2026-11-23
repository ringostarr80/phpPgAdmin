<?php

declare(strict_types=1);

namespace Tests\Support;

use PHPUnit\Framework\Assert;

/**
 * Finds the page that a page leads to after a posted form, for the tests of the pages that lead on to another one.
 */
final class PageRedirect
{
    /**
     * After the page handled the posted form, as its constructor does for a POST request, which then leads to the
     * page that this returns. A form that the page can carry out changes the database.
     *
     * @param class-string<\PhpPgAdmin\Website> $page
     * @return ?array{string, array<string, string|list<string>>} The page that it leads to and the parameters of that
     *     page, sorted by their names, or null when it shows itself again. A parameter that names several values, like
     *     database[], is the list of them.
     */
    public static function afterPost(string $page): ?array
    {
        $website = (new \ReflectionClass($page))->newInstanceWithoutConstructor();
        $url = (new \ReflectionMethod($website, 'handlePostRequest'))->invoke($website);

        if (is_null($url)) {
            return null;
        }

        Assert::assertIsString($url);
        $path = parse_url($url, PHP_URL_PATH);
        Assert::assertIsString($path);
        parse_str((string)parse_url($url, PHP_URL_QUERY), $query);

        $parameters = [];

        foreach ($query as $name => $value) {
            if (is_array($value)) {
                Assert::assertIsList($value);
                $parameters[(string)$name] = array_map(self::string(...), $value);

                continue;
            }

            $parameters[(string)$name] = self::string($value);
        }

        ksort($parameters);

        return [$path, $parameters];
    }

    private static function string(mixed $value): string
    {
        Assert::assertIsString($value);

        return $value;
    }
}
