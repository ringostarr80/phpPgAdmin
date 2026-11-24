<?php

declare(strict_types=1);

namespace PhpPgAdmin\Database;

/**
 * SASLprep (RFC 4013), with which PostgreSQL normalizes a password before it builds a SCRAM secret, done like its
 * pg_saslprep(). The tables of RFC 3454 that it takes are those of Unicode 3.2.
 */
final class SaslPrep
{
    /**
     * RFC 3454, C.1.2, which are mapped to a space.
     */
    private const NON_ASCII_SPACES = '/[\x{A0}\x{1680}\x{2000}-\x{200B}\x{202F}\x{205F}\x{3000}]/u';

    /**
     * RFC 3454, B.1, which are mapped to nothing.
     */
    private const MAPPED_TO_NOTHING
        = '/[\x{AD}\x{34F}\x{1806}\x{180B}-\x{180D}\x{200B}-\x{200D}\x{2060}\x{FE00}-\x{FE0F}\x{FEFF}]/u';

    /**
     * RFC 3454, C.1.2 to C.9, but the surrogates of C.5, which no UTF-8 has.
     */
    private const PROHIBITED = '/['
        // C.1.2, non-ASCII spaces, which are already mapped to a space
        . '\x{A0}\x{1680}\x{2000}-\x{200B}\x{202F}\x{205F}\x{3000}'
        // C.2.1, ASCII control characters
        . '\x{0}-\x{1F}\x{7F}'
        // C.2.2, non-ASCII control characters
        . '\x{80}-\x{9F}\x{6DD}\x{70F}\x{180E}\x{200C}\x{200D}\x{2028}\x{2029}\x{2060}-\x{2063}\x{206A}-\x{206F}'
        . '\x{FEFF}\x{FFF9}-\x{FFFC}\x{1D173}-\x{1D17A}'
        // C.3, private use
        . '\x{E000}-\x{F8FF}\x{F0000}-\x{FFFFD}\x{100000}-\x{10FFFD}'
        // C.4, non-character code points
        . '\x{FDD0}-\x{FDEF}\x{FFFE}-\x{FFFF}\x{1FFFE}-\x{1FFFF}\x{2FFFE}-\x{2FFFF}\x{3FFFE}-\x{3FFFF}'
        . '\x{4FFFE}-\x{4FFFF}\x{5FFFE}-\x{5FFFF}\x{6FFFE}-\x{6FFFF}\x{7FFFE}-\x{7FFFF}\x{8FFFE}-\x{8FFFF}'
        . '\x{9FFFE}-\x{9FFFF}\x{AFFFE}-\x{AFFFF}\x{BFFFE}-\x{BFFFF}\x{CFFFE}-\x{CFFFF}\x{DFFFE}-\x{DFFFF}'
        . '\x{EFFFE}-\x{EFFFF}\x{FFFFE}-\x{FFFFF}\x{10FFFE}-\x{10FFFF}'
        // C.6, inappropriate for plain text
        . '\x{FFF9}-\x{FFFD}'
        // C.7, inappropriate for canonical representation
        . '\x{2FF0}-\x{2FFB}'
        // C.8, change display properties or are deprecated
        . '\x{340}\x{341}\x{200E}\x{200F}\x{202A}-\x{202E}\x{206A}-\x{206F}'
        // C.9, tagging characters
        . '\x{E0001}\x{E0020}-\x{E007F}'
        . ']/u';

    /**
     * The age that IntlChar gives a code point that is not assigned.
     */
    private const AGE_OF_UNASSIGNED = [0, 0, 0, 0];

    private const AGE_OF_UNICODE_3_2 = [3, 2, 0, 0];

    /**
     * RFC 3454, D.1, the characters from right to left, without those that are prohibited. ICU would give the same,
     * but these do not depend on its version of Unicode.
     */
    private const RIGHT_TO_LEFT_CHARACTER = '['
        . '\x{5BE}\x{5C0}\x{5C3}\x{5D0}-\x{5EA}\x{5F0}-\x{5F4}\x{61B}\x{61F}\x{621}-\x{63A}\x{640}-\x{64A}'
        . '\x{66D}-\x{66F}\x{671}-\x{6D5}\x{6E5}-\x{6E6}\x{6FA}-\x{6FE}\x{700}-\x{70D}\x{710}\x{712}-\x{72C}'
        . '\x{780}-\x{7A5}\x{7B1}\x{FB1D}\x{FB1F}-\x{FB28}\x{FB2A}-\x{FB36}\x{FB38}-\x{FB3C}\x{FB3E}\x{FB40}-\x{FB41}'
        . '\x{FB43}-\x{FB44}\x{FB46}-\x{FBB1}\x{FBD3}-\x{FD3D}\x{FD50}-\x{FD8F}\x{FD92}-\x{FDC7}\x{FDF0}-\x{FDFC}'
        . '\x{FE70}-\x{FE74}\x{FE76}-\x{FEFC}'
        . ']';

    private const RIGHT_TO_LEFT = '/' . self::RIGHT_TO_LEFT_CHARACTER . '/u';

    private const STARTS_AND_ENDS_RIGHT_TO_LEFT
        = '/\A' . self::RIGHT_TO_LEFT_CHARACTER . '(?:.*' . self::RIGHT_TO_LEFT_CHARACTER . ')?\z/su';

    /**
     * RFC 3454, D.2, the characters from left to right, without those that are prohibited or that Unicode 3.2 did
     * not assign. Since Unicode 3.2, a few characters changed their direction, like the Braille patterns, which ICU
     * now takes as left to right.
     */
    private const LEFT_TO_RIGHT = '/['
        . 'A-Za-z'
        . '\x{AA}\x{B5}\x{BA}\x{C0}-\x{D6}\x{D8}-\x{F6}\x{F8}-\x{220}\x{222}-\x{233}\x{250}-\x{2AD}\x{2B0}-\x{2B8}'
        . '\x{2BB}-\x{2C1}\x{2D0}-\x{2D1}\x{2E0}-\x{2E4}\x{2EE}\x{37A}\x{386}\x{388}-\x{38A}\x{38C}\x{38E}-\x{3A1}'
        . '\x{3A3}-\x{3CE}\x{3D0}-\x{3F5}\x{400}-\x{482}\x{48A}-\x{4CE}\x{4D0}-\x{4F5}\x{4F8}-\x{4F9}\x{500}-\x{50F}'
        . '\x{531}-\x{556}\x{559}-\x{55F}\x{561}-\x{587}\x{589}\x{903}\x{905}-\x{939}\x{93D}-\x{940}\x{949}-\x{94C}'
        . '\x{950}\x{958}-\x{961}\x{964}-\x{970}\x{982}-\x{983}\x{985}-\x{98C}\x{98F}-\x{990}\x{993}-\x{9A8}'
        . '\x{9AA}-\x{9B0}\x{9B2}\x{9B6}-\x{9B9}\x{9BE}-\x{9C0}\x{9C7}-\x{9C8}\x{9CB}-\x{9CC}\x{9D7}\x{9DC}-\x{9DD}'
        . '\x{9DF}-\x{9E1}\x{9E6}-\x{9F1}\x{9F4}-\x{9FA}\x{A05}-\x{A0A}\x{A0F}-\x{A10}\x{A13}-\x{A28}\x{A2A}-\x{A30}'
        . '\x{A32}-\x{A33}\x{A35}-\x{A36}\x{A38}-\x{A39}\x{A3E}-\x{A40}\x{A59}-\x{A5C}\x{A5E}\x{A66}-\x{A6F}'
        . '\x{A72}-\x{A74}\x{A83}\x{A85}-\x{A8B}\x{A8D}\x{A8F}-\x{A91}\x{A93}-\x{AA8}\x{AAA}-\x{AB0}\x{AB2}-\x{AB3}'
        . '\x{AB5}-\x{AB9}\x{ABD}-\x{AC0}\x{AC9}\x{ACB}-\x{ACC}\x{AD0}\x{AE0}\x{AE6}-\x{AEF}\x{B02}-\x{B03}'
        . '\x{B05}-\x{B0C}\x{B0F}-\x{B10}\x{B13}-\x{B28}\x{B2A}-\x{B30}\x{B32}-\x{B33}\x{B36}-\x{B39}\x{B3D}-\x{B3E}'
        . '\x{B40}\x{B47}-\x{B48}\x{B4B}-\x{B4C}\x{B57}\x{B5C}-\x{B5D}\x{B5F}-\x{B61}\x{B66}-\x{B70}\x{B83}'
        . '\x{B85}-\x{B8A}\x{B8E}-\x{B90}\x{B92}-\x{B95}\x{B99}-\x{B9A}\x{B9C}\x{B9E}-\x{B9F}\x{BA3}-\x{BA4}'
        . '\x{BA8}-\x{BAA}\x{BAE}-\x{BB5}\x{BB7}-\x{BB9}\x{BBE}-\x{BBF}\x{BC1}-\x{BC2}\x{BC6}-\x{BC8}\x{BCA}-\x{BCC}'
        . '\x{BD7}\x{BE7}-\x{BF2}\x{C01}-\x{C03}\x{C05}-\x{C0C}\x{C0E}-\x{C10}\x{C12}-\x{C28}\x{C2A}-\x{C33}'
        . '\x{C35}-\x{C39}\x{C41}-\x{C44}\x{C60}-\x{C61}\x{C66}-\x{C6F}\x{C82}-\x{C83}\x{C85}-\x{C8C}\x{C8E}-\x{C90}'
        . '\x{C92}-\x{CA8}\x{CAA}-\x{CB3}\x{CB5}-\x{CB9}\x{CBE}\x{CC0}-\x{CC4}\x{CC7}-\x{CC8}\x{CCA}-\x{CCB}'
        . '\x{CD5}-\x{CD6}\x{CDE}\x{CE0}-\x{CE1}\x{CE6}-\x{CEF}\x{D02}-\x{D03}\x{D05}-\x{D0C}\x{D0E}-\x{D10}'
        . '\x{D12}-\x{D28}\x{D2A}-\x{D39}\x{D3E}-\x{D40}\x{D46}-\x{D48}\x{D4A}-\x{D4C}\x{D57}\x{D60}-\x{D61}'
        . '\x{D66}-\x{D6F}\x{D82}-\x{D83}\x{D85}-\x{D96}\x{D9A}-\x{DB1}\x{DB3}-\x{DBB}\x{DBD}\x{DC0}-\x{DC6}'
        . '\x{DCF}-\x{DD1}\x{DD8}-\x{DDF}\x{DF2}-\x{DF4}\x{E01}-\x{E30}\x{E32}-\x{E33}\x{E40}-\x{E46}\x{E4F}-\x{E5B}'
        . '\x{E81}-\x{E82}\x{E84}\x{E87}-\x{E88}\x{E8A}\x{E8D}\x{E94}-\x{E97}\x{E99}-\x{E9F}\x{EA1}-\x{EA3}\x{EA5}'
        . '\x{EA7}\x{EAA}-\x{EAB}\x{EAD}-\x{EB0}\x{EB2}-\x{EB3}\x{EBD}\x{EC0}-\x{EC4}\x{EC6}\x{ED0}-\x{ED9}'
        . '\x{EDC}-\x{EDD}\x{F00}-\x{F17}\x{F1A}-\x{F34}\x{F36}\x{F38}\x{F3E}-\x{F47}\x{F49}-\x{F6A}\x{F7F}\x{F85}'
        . '\x{F88}-\x{F8B}\x{FBE}-\x{FC5}\x{FC7}-\x{FCC}\x{FCF}\x{1000}-\x{1021}\x{1023}-\x{1027}\x{1029}-\x{102A}'
        . '\x{102C}\x{1031}\x{1038}\x{1040}-\x{1057}\x{10A0}-\x{10C5}\x{10D0}-\x{10F8}\x{10FB}\x{1100}-\x{1159}'
        . '\x{115F}-\x{11A2}\x{11A8}-\x{11F9}\x{1200}-\x{1206}\x{1208}-\x{1246}\x{1248}\x{124A}-\x{124D}'
        . '\x{1250}-\x{1256}\x{1258}\x{125A}-\x{125D}\x{1260}-\x{1286}\x{1288}\x{128A}-\x{128D}\x{1290}-\x{12AE}'
        . '\x{12B0}\x{12B2}-\x{12B5}\x{12B8}-\x{12BE}\x{12C0}\x{12C2}-\x{12C5}\x{12C8}-\x{12CE}\x{12D0}-\x{12D6}'
        . '\x{12D8}-\x{12EE}\x{12F0}-\x{130E}\x{1310}\x{1312}-\x{1315}\x{1318}-\x{131E}\x{1320}-\x{1346}'
        . '\x{1348}-\x{135A}\x{1361}-\x{137C}\x{13A0}-\x{13F4}\x{1401}-\x{1676}\x{1681}-\x{169A}\x{16A0}-\x{16F0}'
        . '\x{1700}-\x{170C}\x{170E}-\x{1711}\x{1720}-\x{1731}\x{1735}-\x{1736}\x{1740}-\x{1751}\x{1760}-\x{176C}'
        . '\x{176E}-\x{1770}\x{1780}-\x{17B6}\x{17BE}-\x{17C5}\x{17C7}-\x{17C8}\x{17D4}-\x{17DA}\x{17DC}'
        . '\x{17E0}-\x{17E9}\x{1810}-\x{1819}\x{1820}-\x{1877}\x{1880}-\x{18A8}\x{1E00}-\x{1E9B}\x{1EA0}-\x{1EF9}'
        . '\x{1F00}-\x{1F15}\x{1F18}-\x{1F1D}\x{1F20}-\x{1F45}\x{1F48}-\x{1F4D}\x{1F50}-\x{1F57}\x{1F59}\x{1F5B}'
        . '\x{1F5D}\x{1F5F}-\x{1F7D}\x{1F80}-\x{1FB4}\x{1FB6}-\x{1FBC}\x{1FBE}\x{1FC2}-\x{1FC4}\x{1FC6}-\x{1FCC}'
        . '\x{1FD0}-\x{1FD3}\x{1FD6}-\x{1FDB}\x{1FE0}-\x{1FEC}\x{1FF2}-\x{1FF4}\x{1FF6}-\x{1FFC}\x{2071}\x{207F}'
        . '\x{2102}\x{2107}\x{210A}-\x{2113}\x{2115}\x{2119}-\x{211D}\x{2124}\x{2126}\x{2128}\x{212A}-\x{212D}'
        . '\x{212F}-\x{2131}\x{2133}-\x{2139}\x{213D}-\x{213F}\x{2145}-\x{2149}\x{2160}-\x{2183}\x{2336}-\x{237A}'
        . '\x{2395}\x{249C}-\x{24E9}\x{3005}-\x{3007}\x{3021}-\x{3029}\x{3031}-\x{3035}\x{3038}-\x{303C}'
        . '\x{3041}-\x{3096}\x{309D}-\x{309F}\x{30A1}-\x{30FA}\x{30FC}-\x{30FF}\x{3105}-\x{312C}\x{3131}-\x{318E}'
        . '\x{3190}-\x{31B7}\x{31F0}-\x{321C}\x{3220}-\x{3243}\x{3260}-\x{327B}\x{327F}-\x{32B0}\x{32C0}-\x{32CB}'
        . '\x{32D0}-\x{32FE}\x{3300}-\x{3376}\x{337B}-\x{33DD}\x{33E0}-\x{33FE}\x{3400}-\x{4DB5}\x{4E00}-\x{9FA5}'
        . '\x{A000}-\x{A48C}\x{AC00}-\x{D7A3}\x{F900}-\x{FA2D}\x{FA30}-\x{FA6A}\x{FB00}-\x{FB06}\x{FB13}-\x{FB17}'
        . '\x{FF21}-\x{FF3A}\x{FF41}-\x{FF5A}\x{FF66}-\x{FFBE}\x{FFC2}-\x{FFC7}\x{FFCA}-\x{FFCF}\x{FFD2}-\x{FFD7}'
        . '\x{FFDA}-\x{FFDC}\x{10300}-\x{1031E}\x{10320}-\x{10323}\x{10330}-\x{1034A}\x{10400}-\x{10425}'
        . '\x{10428}-\x{1044D}\x{1D000}-\x{1D0F5}\x{1D100}-\x{1D126}\x{1D12A}-\x{1D166}\x{1D16A}-\x{1D172}'
        . '\x{1D183}-\x{1D184}\x{1D18C}-\x{1D1A9}\x{1D1AE}-\x{1D1DD}\x{1D400}-\x{1D454}\x{1D456}-\x{1D49C}'
        . '\x{1D49E}-\x{1D49F}\x{1D4A2}\x{1D4A5}-\x{1D4A6}\x{1D4A9}-\x{1D4AC}\x{1D4AE}-\x{1D4B9}\x{1D4BB}'
        . '\x{1D4BD}-\x{1D4C0}\x{1D4C2}-\x{1D4C3}\x{1D4C5}-\x{1D505}\x{1D507}-\x{1D50A}\x{1D50D}-\x{1D514}'
        . '\x{1D516}-\x{1D51C}\x{1D51E}-\x{1D539}\x{1D53B}-\x{1D53E}\x{1D540}-\x{1D544}\x{1D546}\x{1D54A}-\x{1D550}'
        . '\x{1D552}-\x{1D6A3}\x{1D6A8}-\x{1D7C9}\x{20000}-\x{2A6D6}\x{2F800}-\x{2FA1D}'
        . ']/u';

    /**
     * The prepared string, or null if SASLprep refuses it, because it is no UTF-8 or has a character that it
     * prohibits.
     */
    public static function prepare(string $string): ?string
    {
        // Like PostgreSQL, takes ASCII as it is, also with control characters, which SASLprep would prohibit.
        if (mb_check_encoding($string, 'ASCII')) {
            return $string;
        }

        $mapped = preg_replace([self::NON_ASCII_SPACES, self::MAPPED_TO_NOTHING], [' ', ''], $string);

        // With the u modifier, there is no mapping for a string that is no UTF-8. Like PostgreSQL, takes nothing for no
        // string either.
        if ($mapped === null || $mapped === '') {
            return null;
        }

        // Unlike RFC 4013, PostgreSQL checks the characters before they are normalized.
        if (preg_match(self::PROHIBITED, $mapped) === 1 || !self::isAssignedInUnicode32($mapped)) {
            return null;
        }

        if (!self::hasValidDirections($mapped)) {
            return null;
        }

        $normalized = \Normalizer::normalize($mapped, \Normalizer::FORM_KC);

        return $normalized !== false
            ? $normalized
            : null;
    }

    /**
     * RFC 3454, section 6: a string with a character from right to left has none from left to right, and it starts
     * and ends with one from right to left.
     */
    private static function hasValidDirections(string $string): bool
    {
        if (preg_match(self::RIGHT_TO_LEFT, $string) !== 1) {
            return true;
        }

        return preg_match(self::LEFT_TO_RIGHT, $string) !== 1
            && preg_match(self::STARTS_AND_ENDS_RIGHT_TO_LEFT, $string) === 1;
    }

    /**
     * RFC 3454, A.1, has the code points that Unicode 3.2 did not assign. Unicode never changes the age of a code
     * point, so any version of ICU tells them.
     */
    private static function isAssignedInUnicode32(string $string): bool
    {
        foreach (mb_str_split($string) as $character) {
            $age = \IntlChar::charAge($character);

            if ($age === self::AGE_OF_UNASSIGNED || $age > self::AGE_OF_UNICODE_3_2) {
                return false;
            }
        }

        return true;
    }
}
