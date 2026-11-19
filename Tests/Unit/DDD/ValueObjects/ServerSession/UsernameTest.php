<?php

declare(strict_types=1);

namespace Tests\Unit\DDD\ValueObjects\ServerSession;

use PhpPgAdmin\DDD\ValueObjects\ServerSession\Username;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Username::class)]
final class UsernameTest extends TestCase
{
    public function testZeroIsAUsername(): void
    {
        // PostgreSQL takes "0" as a role name, although PHP's empty() takes it for an empty string.
        self::assertSame('0', (string)new Username('0'));
    }

    public function testTheBlanksAroundAUsernameArePartOfIt(): void
    {
        // PostgreSQL keeps them in a quoted role name, so without them the username is another role.
        self::assertSame(' alice ', (string)new Username(' alice '));
    }

    public function testBlanksAloneAreAUsername(): void
    {
        // PostgreSQL takes them as a quoted role name, and the login lets them through.
        self::assertSame(" \n", (string)new Username(" \n"));
    }

    public function testAnEmptyUsernameIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Username('');
    }
}
