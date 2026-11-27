<?php

declare(strict_types=1);

namespace Tests\Unit\Website;

use PhpPgAdmin\Config;
use PhpPgAdmin\Website\SqlEdit;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SqlEdit::class)]
final class SqlEditTest extends TestCase
{
    private const FIND_FILTERS = [
        '' => 'All objects',
        'COLUMN' => 'Columns',
        'CONSTRAINT' => 'Constraints',
        'DOMAIN' => 'Domains',
        'FUNCTION' => 'Functions',
        'INDEX' => 'Indexes',
        'RULE' => 'Rules',
        'SCHEMA' => 'Schemas',
        'SEQUENCE' => 'Sequences',
        'TABLE' => 'Tables',
        'TRIGGER' => 'Triggers',
        'VIEW' => 'Views',
    ];

    /**
     * Only offered with show_advanced, as in the legacy sqledit.php.
     */
    private const ADVANCED_FIND_FILTERS = [
        'AGGREGATE' => 'Aggregates',
        'CONVERSION' => 'Conversions',
        'LANGUAGE' => 'Languages',
        'OPCLASS' => 'Op Classes',
        'OPERATOR' => 'Operators',
        'TYPE' => 'Types',
    ];

    public function testFindFiltersWithShowAdvancedOfferTheAdvancedObjectTypesAfterTheOthers(): void
    {
        self::assertSame(self::FIND_FILTERS + self::ADVANCED_FIND_FILTERS, SqlEdit::findFilters(showAdvanced: true));
    }

    public function testFindFiltersWithoutShowAdvancedHideTheAdvancedObjectTypes(): void
    {
        self::assertSame(self::FIND_FILTERS, SqlEdit::findFilters(showAdvanced: false));
    }

    public function testFindFiltersFollowShowAdvanced(): void
    {
        self::assertSame(SqlEdit::findFilters(Config::showAdvanced()), SqlEdit::findFilters());
    }
}
