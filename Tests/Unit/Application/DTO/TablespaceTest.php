<?php

declare(strict_types=1);

namespace Tests\Unit\Application\DTO;

use PhpPgAdmin\Application\DTO\Tablespace as DTOTablespace;
use PhpPgAdmin\DDD\ValueObjects\Tablespace;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversMethod(DTOTablespace::class, 'createFromDbArray')]
#[CoversMethod(DTOTablespace::class, 'createFromFormRequest')]
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

    public function testCreateFromDbArrayKeepsTheOwnerAndCommentVerbatim(): void
    {
        $owner = ' archive admin ';
        $comment = "\n  Old data\r\nRetention notes  \n";
        $tablespace = DTOTablespace::createFromDbArray(
            ['spcowner' => $owner, 'spccomment' => $comment] + self::VALID_DB_ARRAY,
        );

        self::assertSame($owner, (string)$tablespace->Owner);
        self::assertSame($comment, (string)$tablespace->Comment);
    }

    public function testCreateFromFormRequestKeepsTheOwnerAndCommentVerbatim(): void
    {
        $owner = ' archive admin ';
        $comment = "\r\n  Old data\r\nRetention notes  \r\n";
        $original = $_POST;
        $_POST = [
            Tablespace::FORM_ID_COMMENT => $comment,
            Tablespace::FORM_ID_NAME => 'archive',
            Tablespace::FORM_ID_OWNER => $owner,
        ];

        try {
            $tablespace = DTOTablespace::createFromFormRequest();
        } finally {
            $_POST = $original;
        }

        self::assertSame($owner, (string)$tablespace->Owner);
        self::assertSame($comment, (string)$tablespace->Comment);
    }

    public function testCreateFromDbArrayWithoutACommentGivesAnEmptyComment(): void
    {
        // The query gives NULL for a tablespace that has no comment in pg_shdescription.
        $tablespace = DTOTablespace::createFromDbArray(['spccomment' => null] + self::VALID_DB_ARRAY);

        self::assertSame('', (string)$tablespace->Comment);
    }

    public function testCreateFromDbArrayWithoutALocationGivesAnEmptyLocation(): void
    {
        // pg_tablespace_location() gives an empty location for the tablespaces in the data directory.
        $tablespace = DTOTablespace::createFromDbArray(
            ['spclocation' => '', 'spcname' => 'pg_default'] + self::VALID_DB_ARRAY,
        );

        self::assertSame('pg_default', (string)$tablespace->Name);
        self::assertSame('', (string)$tablespace->Location);
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
