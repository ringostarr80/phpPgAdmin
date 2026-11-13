<?php

declare(strict_types=1);

namespace Tests\Unit\DDD\ValueObjects;

use PhpPgAdmin\DDD\ValueObjects\DbSize;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(DbSize::class)]
final class DbSizeTest extends TestCase
{
    private const KIB = 1_024;
    private const MIB = 1_024 * self::KIB;
    private const GIB = 1_024 * self::MIB;
    private const TIB = 1_024 * self::GIB;
    private const PIB = 1_024 * self::TIB;

    #[DataProvider('validSizes')]
    public function testValue(int $size): void
    {
        self::assertSame($size, (new DbSize($size))->Value);
    }

    #[DataProvider('validSizes')]
    public function testToString(int $size): void
    {
        self::assertSame((string)$size, (string)new DbSize($size));
    }

    public function testNegativeSizeThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $dbSize = new DbSize(-1);

        self::fail("Expected an exception for a negative size, got a DbSize of {$dbSize}.");
    }

    #[DataProvider('prettyFormats')]
    public function testPrettyFormat(int $size, string $expected): void
    {
        self::assertSame($expected, (new DbSize($size))->prettyFormat());
    }

    /**
     * @return iterable<string, array{int}>
     */
    public static function validSizes(): iterable
    {
        yield 'zero' => [0];
        yield 'one byte' => [1];
        yield '1000 bytes' => [1_000];
        yield '1 KiB' => [self::KIB];
        yield '1 MiB' => [self::MIB];
    }

    /**
     * A unit is used until the size reaches 10 of the next larger one; TB is the largest unit.
     *
     * @return iterable<string, array{int, string}>
     */
    public static function prettyFormats(): iterable
    {
        yield 'zero' => [0, '0 bytes'];
        yield 'one byte' => [1, '1 bytes'];
        yield '1000 bytes' => [1_000, '1000 bytes'];
        yield '1 KiB' => [self::KIB, '1024 bytes'];
        yield 'just below 10 KiB' => [10 * self::KIB - 1, '10239 bytes'];
        yield '10 KiB' => [10 * self::KIB, '10 kB'];
        yield 'just below 10 MiB' => [10 * self::MIB - 1, '10240 kB'];
        yield '10 MiB' => [10 * self::MIB, '10 MB'];
        yield 'just below 10 GiB' => [10 * self::GIB - 1, '10240 MB'];
        yield '10 GiB' => [10 * self::GIB, '10 GB'];
        yield 'just below 10 TiB' => [10 * self::TIB - 1, '10240 GB'];
        yield '10 TiB' => [10 * self::TIB, '10 TB'];
        yield 'just below 10 PiB' => [10 * self::PIB - 1, '10240 TB'];
        yield '10 PiB stays in TB' => [10 * self::PIB, '10240 TB'];
        yield '1000 PiB stays in TB' => [1_000 * self::PIB, '1024000 TB'];
    }
}
