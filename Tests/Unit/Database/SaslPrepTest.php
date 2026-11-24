<?php

declare(strict_types=1);

namespace Tests\Unit\Database;

use PhpPgAdmin\Database\SaslPrep;
use PHPUnit\Framework\Attributes\{CoversClass, TestWith};
use PHPUnit\Framework\TestCase;

/**
 * The expected results come from PostgreSQL's pg_saslprep().
 */
#[CoversClass(SaslPrep::class)]
final class SaslPrepTest extends TestCase
{
    public function testTakesAsciiAsItIsEvenWithAControlCharacter(): void
    {
        self::assertSame("se\tcret", SaslPrep::prepare("se\tcret"));
    }

    public function testRefusesAStringThatIsNoUtf8(): void
    {
        self::assertNull(SaslPrep::prepare("a\xFFb"));
    }

    #[TestWith(["a\u{A0}b"])]
    #[TestWith(["a\u{200B}b"])]
    public function testMapsANonAsciiSpaceToASpace(string $string): void
    {
        self::assertSame('a b', SaslPrep::prepare($string));
    }

    public function testMapsASoftHyphenToNothing(): void
    {
        self::assertSame('ab', SaslPrep::prepare("a\u{AD}b"));
    }

    public function testRefusesAStringThatIsMappedToNothing(): void
    {
        self::assertNull(SaslPrep::prepare("\u{AD}"));
    }

    #[TestWith(["\u{FB01}x", 'fix'])]
    #[TestWith(["gru\u{308}n", "gr\u{FC}n"])]
    #[TestWith(["\u{FF21}b", 'Ab'])]
    public function testNormalizesWithNfkc(string $string, string $prepared): void
    {
        self::assertSame($prepared, SaslPrep::prepare($string));
    }

    #[TestWith(["\u{E000}"])]
    #[TestWith(["\t\u{E4}"])]
    public function testRefusesAStringWithAProhibitedCharacter(string $string): void
    {
        self::assertNull(SaslPrep::prepare($string));
    }

    public function testRefusesAProhibitedCharacterThatTheNormalizationWouldReplace(): void
    {
        // Unlike RFC 4013, PostgreSQL checks the characters before they are normalized, and NFKC makes the combining
        // grave tone mark a combining grave accent.
        self::assertNull(SaslPrep::prepare("\u{340}"));
    }

    #[TestWith(["\u{378}"])]
    #[TestWith(["\u{3F9}"])]
    public function testRefusesACodePointThatUnicode32DidNotAssign(string $string): void
    {
        // No version of Unicode assigned U+0378 yet. Unicode 4.0 added the lunate sigma, which NFKC would make a sigma.
        self::assertNull(SaslPrep::prepare($string));
    }

    public function testTakesACharacterThatUnicode32Added(): void
    {
        self::assertSame("\u{220}", SaslPrep::prepare("\u{220}"));
    }

    #[TestWith(["\u{5D0}"])]
    #[TestWith(["\u{5D0}\u{5D1}"])]
    #[TestWith(["\u{5D0}1\u{5D1}"])]
    public function testTakesAStringFromRightToLeft(string $string): void
    {
        self::assertSame($string, SaslPrep::prepare($string));
    }

    #[TestWith(["\u{5D0}a\u{5D0}"])]
    #[TestWith(["\u{5D0}\u{17B4}\u{5D0}"])]
    public function testRefusesAStringFromRightToLeftWithACharacterFromLeftToRight(string $string): void
    {
        // Unicode 3.2 took the Khmer vowel inherent aq as one from left to right, later versions do not.
        self::assertNull(SaslPrep::prepare($string));
    }

    public function testTakesTheDirectionsOfUnicode32(): void
    {
        // Unicode 3.2 did not take the Braille patterns as characters from left to right, later versions do.
        self::assertSame("\u{5D0}\u{2800}\u{5D0}", SaslPrep::prepare("\u{5D0}\u{2800}\u{5D0}"));
    }

    #[TestWith(["\u{5D0}1"])]
    #[TestWith(["1\u{5D0}"])]
    public function testRefusesAStringFromRightToLeftThatDoesNotStartAndEndWithSuchACharacter(string $string): void
    {
        self::assertNull(SaslPrep::prepare($string));
    }
}
