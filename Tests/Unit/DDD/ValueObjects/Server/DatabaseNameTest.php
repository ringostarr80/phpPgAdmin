<?php

declare(strict_types=1);

namespace Tests\Unit\DDD\ValueObjects\Server;

use PhpPgAdmin\DDD\ValueObjects\Server\DatabaseName;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(DatabaseName::class)]
final class DatabaseNameTest extends TestCase
{
    public function testZeroIsADatabaseName(): void
    {
        // PostgreSQL takes "0" as a database name, although PHP's empty() takes it for an empty string.
        self::assertSame('0', (string)new DatabaseName('0'));
    }

    public function testABlankDatabaseNameIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new DatabaseName(" \n");
    }
}
