<?php

declare(strict_types=1);

namespace BriceFab\InvisibleCleaner\Tests;

use PHPUnit\Framework\TestCase;
use BriceFab\InvisibleCleaner\IaInvisibleCleaner;

class IaInvisibleCleanerTest extends TestCase
{
    public function testRemovesZeroWidthAndExoticSpaces(): void
    {
        $s = "A\u{200B}B\u{202F}C\u{FEFF}D\u{2009}E";
        $this->assertSame("ABCDE", IaInvisibleCleaner::clean($s));
    }

    public function testPreservesNormalWhitespaceByDefault(): void
    {
        $s = "A B\tC\nD\r\nE";
        $this->assertSame($s, IaInvisibleCleaner::clean($s));
    }

    public function testRemoveTabsAndNewlinesWhenRequested(): void
    {
        $s = "A B\tC\nD\r\nE";
        $this->assertSame("A BCDE", IaInvisibleCleaner::clean($s, true));
    }

    public function testNormalizeAndClean(): void
    {
        if (!\class_exists(\Normalizer::class)) {
            $this->markTestSkipped('intl extension (Normalizer) not available.');
        }

        $s = "e\u{0301}"; // 'e' + combining acute
        // 3e arg = mode (int), 4e = useIntlNormalizer (bool)
        $out = IaInvisibleCleaner::normalizeAndClean(
            $s,
            false,
            IaInvisibleCleaner::MODE_DEFAULT,
            true
        );

        $this->assertSame(1, \mb_strlen($out));
    }
}
