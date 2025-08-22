<?php

declare(strict_types=1);

namespace BriceFab\InvisibleCleaner;

/**
 * Utility class to remove invisible, zero-width, control, and exotic space characters
 * from text in order to improve readability, prevent hidden payloads,
 * and mitigate Unicode-based attacks (e.g., Trojan Source).
 *
 * Modes:
 * - MODE_DEFAULT    : Conservative cleaning (removes common invisibles and exotic spaces).
 * - MODE_HARDENED   : Extended cleaning (also removes soft hyphen, math invisibles, deprecated bidi, tags, annotations).
 * - MODE_AGGRESSIVE : Aggressive cleaning (also removes variation selectors, may alter emoji/typography rendering).
 */
class IaInvisibleCleaner
{
    // C0 control range (without TAB/LF/CR) and full C0 range
    private const C0_WITHOUT_TNR = '\x{0000}-\x{0008}\x{000B}\x{000C}\x{000E}-\x{001F}';
    private const C0_FULL        = '\x{0000}-\x{001F}';

    // C1 control range
    private const C1             = '\x{007F}-\x{009F}';

    // Zero-width characters and related artifacts
    private const ZERO_WIDTH     =
        '\x{200B}-\x{200D}' .  // ZWSP, ZWNJ, ZWJ
        '\x{2060}' .           // WORD JOINER
        '\x{FEFF}' .           // BOM / ZERO WIDTH NO-BREAK SPACE
        '\x{034F}' .           // COMBINING GRAPHEME JOINER
        '\x{061C}' .           // ARABIC LETTER MARK
        '\x{17B4}\x{17B5}' .   // Khmer inherent vowels
        '\x{180E}';            // MONGOLIAN VOWEL SEPARATOR

    // Space variants (U+0020 normal space is preserved)
    private const SPACES         =
        '\x{00A0}' .           // NO-BREAK SPACE
        '\x{1680}' .           // OGHAM SPACE MARK
        '\x{2000}-\x{200A}' .  // EN/EM/THIN/HAIR/FIGURE/PUNCT/etc.
        '\x{202F}' .           // NARROW NO-BREAK SPACE
        '\x{205F}' .           // MEDIUM MATHEMATICAL SPACE
        '\x{3000}';            // IDEOGRAPHIC SPACE

    // Line/paragraph separators and bidi controls
    private const SEPARATORS_BIDI =
        '\x{2028}\x{2029}' .   // LINE SEPARATOR / PARAGRAPH SEPARATOR
        '\x{200E}\x{200F}' .   // LEFT-TO-RIGHT MARK / RIGHT-TO-LEFT MARK
        '\x{202A}-\x{202E}' .  // LRE/RLE/PDF/LRO/RLO
        '\x{2066}-\x{2069}';   // LRI/RLI/FSI/PDI

    // Optional strengthening (extended removal set)
    private const OPTIONAL_SOFT_HYPHEN  = '\x{00AD}';                     // SOFT HYPHEN
    private const OPTIONAL_MATH_INVIS   = '\x{2061}-\x{2064}';            // Math invisibles
    private const OPTIONAL_BIDI_DEPR    = '\x{206A}-\x{206F}';            // Deprecated bidi controls
    private const OPTIONAL_TAGS         = '\x{E0000}-\x{E007F}';          // TAG characters
    private const OPTIONAL_ANNOTATION   = '\x{FFF9}-\x{FFFB}';            // Interlinear annotation
    private const OPTIONAL_VARIATION_SEL = '\x{FE00}-\x{FE0F}\x{E0100}-\x{E01EF}'; // Variation selectors (aggressive)

    // Cleaning modes
    public const MODE_DEFAULT    = 0; // Conservative
    public const MODE_HARDENED   = 1; // Extended cleaning
    public const MODE_AGGRESSIVE = 2; // Aggressive (may affect rendering)

    /**
     * Clean a string by removing invisible, zero-width, control, and exotic space characters.
     *
     * @param string $text                 Input text.
     * @param bool   $removeTabsAndNewlines If true, also removes TAB, LF, and CR characters.
     * @param int    $mode                 Cleaning mode (MODE_DEFAULT, MODE_HARDENED, MODE_AGGRESSIVE).
     *
     * @return string Cleaned text.
     */
    public static function clean(
        string $text,
        bool $removeTabsAndNewlines = false,
        int $mode = self::MODE_DEFAULT
    ): string {
        $pattern = self::buildPattern($removeTabsAndNewlines, $mode);
        $clean = \preg_replace($pattern, '', $text);
        return $clean ?? $text; // Defensive fallback if regex fails
    }

    /**
     * Normalize text (if intl Normalizer is available and enabled) and then clean it.
     * Normalization is performed in NFC (Normalization Form C).
     *
     * @param string $text                  Input text.
     * @param bool   $removeTabsAndNewlines If true, also removes TAB, LF, and CR characters.
     * @param int    $mode                  Cleaning mode (MODE_DEFAULT, MODE_HARDENED, MODE_AGGRESSIVE).
     * @param bool   $useIntlNormalizer     If true, normalize text using intl Normalizer before cleaning.
     *
     * @return string Normalized and cleaned text.
     */
    public static function normalizeAndClean(
        string $text,
        bool $removeTabsAndNewlines = false,
        int $mode = self::MODE_DEFAULT,
        bool $useIntlNormalizer = false
    ): string {
        if ($useIntlNormalizer && \class_exists(\Normalizer::class)) {
            $text = \Normalizer::normalize($text, \Normalizer::FORM_C) ?? $text;
        }
        return self::clean($text, $removeTabsAndNewlines, $mode);
    }

    /**
     * Build the regex pattern used for cleaning.
     * Caches patterns for performance.
     *
     * @param bool $removeTabsAndNewlines If true, includes TAB, LF, and CR in the removal set.
     * @param int  $mode                   Cleaning mode.
     *
     * @return string Regex pattern.
     */
    private static function buildPattern(bool $removeTabsAndNewlines, int $mode): string
    {
        static $cache = [];
        $key = ($removeTabsAndNewlines ? '1' : '0') . ':' . $mode;
        if (isset($cache[$key])) {
            return $cache[$key];
        }

        $controls = $removeTabsAndNewlines ? self::C0_FULL : self::C0_WITHOUT_TNR;

        $charClass =
            $controls .
            self::C1 .
            self::ZERO_WIDTH .
            self::SPACES .
            self::SEPARATORS_BIDI;

        if ($mode >= self::MODE_HARDENED) {
            $charClass .=
                self::OPTIONAL_SOFT_HYPHEN .
                self::OPTIONAL_MATH_INVIS .
                self::OPTIONAL_BIDI_DEPR .
                self::OPTIONAL_TAGS .
                self::OPTIONAL_ANNOTATION;
        }
        if ($mode >= self::MODE_AGGRESSIVE) {
            $charClass .= self::OPTIONAL_VARIATION_SEL;
        }

        return $cache[$key] = '/[' . $charClass . ']/u';
    }
}
