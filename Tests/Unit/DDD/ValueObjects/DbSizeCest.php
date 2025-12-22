<?php

declare(strict_types=1);

namespace Tests\Unit\DDD\ValueObjects;

use PhpPgAdmin\DDD\ValueObjects\DbSize;
use Tests\Support\UnitTester;

final class DbSizeCest
{
    public function tryToTestProperties(UnitTester $i): void
    {
        $dbSize0 = new DbSize(0);
        $i->assertEquals(0, $dbSize0->Value);

        $dbSize1 = new DbSize(1);
        $i->assertEquals(1, $dbSize1->Value);

        $dbSize1000 = new DbSize(1_000);
        $i->assertEquals(1_000, $dbSize1000->Value);

        $dbSize1024 = new DbSize(1_024);
        $i->assertEquals(1_024, $dbSize1024->Value);

        $dbSize1MB = new DbSize(1_048_576);
        $i->assertEquals(1_048_576, $dbSize1MB->Value);
    }

    public function tryToTestNegativeSizeThrowsException(UnitTester $i): void
    {
        $i->expectThrowable(\InvalidArgumentException::class, function () {
            new DbSize(-1);
        });
    }

    public function tryToTestToString(UnitTester $i): void
    {
        $dbSize = new DbSize(0);
        $i->assertEquals('0', (string)$dbSize);

        $dbSize1 = new DbSize(1);
        $i->assertEquals('1', (string)$dbSize1);

        $dbSize1000 = new DbSize(1_000);
        $i->assertEquals('1000', (string)$dbSize1000);

        $dbSize1024 = new DbSize(1_024);
        $i->assertEquals('1024', (string)$dbSize1024);

        $dbSize1MB = new DbSize(1_048_576);
        $i->assertEquals('1048576', (string)$dbSize1MB);
    }

    public function tryToTestPrettyFormat(UnitTester $i): void
    {
        $dbSize0 = new DbSize(0);
        $i->assertEquals('0 bytes', $dbSize0->prettyFormat());

        $dbSize1 = new DbSize(1);
        $i->assertEquals('1 bytes', $dbSize1->prettyFormat());

        $dbSize1000 = new DbSize(1_000);
        $i->assertEquals('1000 bytes', $dbSize1000->prettyFormat());

        $dbSize1024 = new DbSize(1_024);
        $i->assertEquals('1024 bytes', $dbSize1024->prettyFormat());

        $dbSize10KBMinus1 = new DbSize(10 * 1_024 - 1);
        $i->assertEquals('10239 bytes', $dbSize10KBMinus1->prettyFormat());

        $dbSize10KB = new DbSize(10 * 1_024);
        $i->assertEquals('10 kB', $dbSize10KB->prettyFormat());

        $dbSize10MBMinus1 = new DbSize(10 * 1_024 * 1_024 - 1);
        $i->assertEquals('10240 kB', $dbSize10MBMinus1->prettyFormat());

        $dbSize10MB = new DbSize(10 * 1_024 * 1_024);
        $i->assertEquals('10 MB', $dbSize10MB->prettyFormat());

        $dbSize10GBMinus1 = new DbSize(10 * 1_024 * 1_024 * 1_024 - 1);
        $i->assertEquals('10240 MB', $dbSize10GBMinus1->prettyFormat());

        $dbSize10GB = new DbSize(10 * 1_024 * 1_024 * 1_024);
        $i->assertEquals('10 GB', $dbSize10GB->prettyFormat());

        $dbSize10TBMinus1 = new DbSize(10 * 1_024 * 1_024 * 1_024 * 1_024 - 1);
        $i->assertEquals('10240 GB', $dbSize10TBMinus1->prettyFormat());

        $dbSize10TB = new DbSize(10 * 1_024 * 1_024 * 1_024 * 1_024);
        $i->assertEquals('10 TB', $dbSize10TB->prettyFormat());

        $dbSize10PBMinus1 = new DbSize(10 * 1_024 * 1_024 * 1_024 * 1_024 * 1_024 - 1);
        $i->assertEquals('10240 TB', $dbSize10PBMinus1->prettyFormat());

        $dbSize10PB = new DbSize(10 * 1_024 * 1_024 * 1_024 * 1_024 * 1_024);
        $i->assertEquals('10240 TB', $dbSize10PB->prettyFormat());

        $dbSize10PB = new DbSize(10 * 1_024 * 1_024 * 1_024 * 1_024 * 1_024 * 100);
        $i->assertEquals('1024000 TB', $dbSize10PB->prettyFormat());
    }
}
