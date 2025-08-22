<?php

declare(strict_types=1);

namespace BriceFab\InvisibleCleaner;

class IaInvisibleCleaner
{
    /**
     * Remove invisible / zero-width / control and exotic space separators.
     * By default, keeps normal space U+0020, tab (\t), newline (\n) and carriage return (\r).
     */
    public static function clean(string $text, bool $removeTabsAndNewlines = false): string
    {
        // When we want to keep \t, \n, \r, we must exclude them from the C0 control range.
        //   - Keep: \x09 (TAB), \x0A (LF), \x0D (CR)
        //   - Remove: \x00-\x08, \x0B, \x0C, \x0E-\x1F
        $c0WithoutTabsNewlines = '\x{0000}-\x{0008}\x{000B}\x{000C}\x{000E}-\x{001F}';

        // Full C0 control range including \t \n \r (used if $removeTabsAndNewlines === true)
        $c0Full = '\x{0000}-\x{001F}';

        // C1 control characters
        $c1 = '\x{007F}-\x{009F}';

        // Zero-width characters and related artefacts
        $zeroWidthAndArtifacts =
            '\x{200B}-\x{200D}' .      // ZWSP, ZWNJ, ZWJ
            '\x{2060}' .               // WORD JOINER
            '\x{FEFF}' .               // BOM / ZERO WIDTH NO-BREAK SPACE
            '\x{034F}' .               // COMBINING GRAPHEME JOINER
            '\x{061C}' .               // ARABIC LETTER MARK
            '\x{17B4}\x{17B5}' .       // Khmer inherent vowels
            '\x{180E}';                // MONGOLIAN VOWEL SEPARATOR

        // Exotic spaces (normal space U+0020 is preserved)
        $spaceVariants =
            '\x{00A0}' .               // NO-BREAK SPACE
            '\x{2000}-\x{200A}' .      // EN/EM/THIN/HAIR/FIGURE/PUNCT/etc.
            '\x{202F}' .               // NARROW NO-BREAK SPACE
            '\x{205F}' .               // MEDIUM MATHEMATICAL SPACE
            '\x{3000}';                // IDEOGRAPHIC SPACE

        // Line/paragraph separators and bidi formatting characters
        $separatorsAndBidi =
            '\x{2028}\x{2029}' .       // LINE/PARAGRAPH SEPARATOR
            '\x{200E}\x{200F}' .       // LRM/RLM
            '\x{202A}-\x{202E}' .      // LRE/RLE/PDF/LRO/RLO
            '\x{2066}-\x{2069}';       // LRI/RLI/FSI/PDI

        // Choose the proper control range depending on $removeTabsAndNewlines
        $controls = $removeTabsAndNewlines ? $c0Full : $c0WithoutTabsNewlines;

        // Build regex pattern
        $pattern = '/['
            . $controls
            . $c1
            . $zeroWidthAndArtifacts
            . $spaceVariants
            . $separatorsAndBidi
            . ']/u';

        $clean = preg_replace($pattern, '', $text);
        if ($clean === null) {
            // Defensive: if PCRE fails, return the original text
            return $text;
        }

        if ($removeTabsAndNewlines) {
            // Explicitly remove tabs and newlines if requested
            $clean = str_replace(["\t", "\n", "\r"], '', $clean);
        }

        return $clean;
    }

    /**
     * Optionally normalize text (if ext/intl is available) before cleaning.
     */
    public static function normalizeAndClean(
        string $text,
        bool   $removeTabsAndNewlines = false,
        bool   $useIntlNormalizer = false
    ): string
    {
        if ($useIntlNormalizer && \class_exists(\Normalizer::class)) {
            $text = \Normalizer::normalize($text, \Normalizer::FORM_C) ?? $text;
        }
        return self::clean($text, $removeTabsAndNewlines);
    }
}
