<?php

declare(strict_types=1);

namespace Acme\InvisibleCleaner\Tests;

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
        $s = "e\u{0301}"; // 'e' + combining acute
        $out = IaInvisibleCleaner::normalizeAndClean($s, false, true);
        $this->assertSame(mb_strlen($out), 1);
    }
}
