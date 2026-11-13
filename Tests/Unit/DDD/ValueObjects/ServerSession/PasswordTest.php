<?php

declare(strict_types=1);

namespace Tests\Unit\DDD\ValueObjects\ServerSession;

use PhpPgAdmin\DDD\ValueObjects\ServerSession\Password;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Password::class)]
final class PasswordTest extends TestCase
{
    #[DataProvider('passwords')]
    public function testPasswordIsKeptAsEntered(string $password): void
    {
        self::assertSame($password, (string)new Password($password));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function passwords(): iterable
    {
        yield 'empty' => [''];
        yield 'zero' => ['0'];
        yield 'surrounding whitespace' => [' secret '];
        yield 'plain' => ['secret'];
    }
}
