<?php

declare(strict_types=1);

namespace Tests\Unit\Application\DTO;

use PhpPgAdmin\Application\DTO\Tablespace as DTOTablespace;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversMethod(DTOTablespace::class, 'createFromDbArray')]
final class TablespaceTest extends TestCase
{
    private const VALID_DB_ARRAY = [
        'spccomment' => 'Old data',
        'spclocation' => '/srv/archive',
        'spcname' => 'archive',
        'spcowner' => 'postgres',
    ];

    public function testCreateFromDbArray(): void
    {
        $tablespace = DTOTablespace::createFromDbArray(self::VALID_DB_ARRAY);

        self::assertSame('archive', (string)$tablespace->Name);
        self::assertSame('postgres', (string)$tablespace->Owner);
        self::assertSame('/srv/archive', (string)$tablespace->Location);
        self::assertSame('Old data', (string)$tablespace->Comment);
    }

    public function testCreateFromDbArrayWithoutACommentGivesAnEmptyComment(): void
    {
        // The query gives NULL for a tablespace that has no comment in pg_shdescription.
        $tablespace = DTOTablespace::createFromDbArray(['spccomment' => null] + self::VALID_DB_ARRAY);

        self::assertSame('', (string)$tablespace->Comment);
    }

    /**
     * @param array<string, mixed> $data
     */
    #[DataProvider('invalidDbArrays')]
    public function testCreateFromDbArrayRejectsInvalidData(array $data, string $expectedMessage): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedMessage);

        DTOTablespace::createFromDbArray($data);
    }

    /**
     * @return iterable<string, array{array<string, mixed>, string}>
     */
    public static function invalidDbArrays(): iterable
    {
        foreach (['spclocation', 'spcname', 'spcowner'] as $field) {
            $data = self::VALID_DB_ARRAY;
            unset($data[$field]);

            yield "missing {$field}" => [$data, "Missing field '{$field}'"];
        }

        foreach (array_keys(self::VALID_DB_ARRAY) as $field) {
            yield "{$field} not a string" => [
                [$field => 1] + self::VALID_DB_ARRAY,
                "Type of field '{$field}' is not string.",
            ];
        }
    }
}
