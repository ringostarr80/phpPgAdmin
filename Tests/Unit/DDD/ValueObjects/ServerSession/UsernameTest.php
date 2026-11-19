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

    public function testABlankUsernameIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Username(" \n");
    }
}
