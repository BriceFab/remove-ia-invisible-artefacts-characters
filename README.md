# Remove IA invisible artefacts characters

This library can help clean texts from invisible Unicode artefacts,
which may also reduce false positives from AI text detectors such as
[ZeroGPT](https://www.zerogpt.com/).

PHP library to remove invisible Unicode characters and artefacts
(zero-width, BOM, NBSP, bidi marks) often found in AI-generated texts.\
It helps sanitize text by stripping invisible or unexpected characters
while keeping normal whitespace intact, unless explicitly removed.

## ✨ Features

-   Removes zero-width characters (ZWSP, ZWNJ, ZWJ, BOM, etc.).
-   Removes exotic Unicode spaces (non-breaking space, narrow no-break
    space, thin space, ideographic space...).
-   Removes bidi formatting and paragraph/line separators.
-   Preserves normal spaces, tabs, and newlines by default.
-   Optionally removes **all whitespace including tabs/newlines**.
-   Optional Unicode normalization (requires PHP `ext/intl`).
-   Provides a CLI tool for quick text cleanup.

## 🚀 Installation

``` bash
composer require acme/ia-remove-invisible-artefacts-characters
```

## 📖 Usage

### Basic cleaning

``` php
use BriceFab\InvisibleCleaner\IaInvisibleCleaner;

$dirty = "A\u{200B}B\u{202F}C\u{FEFF}D\u{2009}E"; // contains invisible chars
$clean = IaInvisibleCleaner::clean($dirty);

echo $clean; // "ABCDE"
```

### Preserve tabs/newlines (default behavior)

``` php
$dirty = "A B\tC\nD\r\nE";
$clean = IaInvisibleCleaner::clean($dirty);

// Tabs and newlines are preserved
```

### Remove **all whitespace** (including space, tabs, newlines)

``` php
$dirty = "A B\tC\nD\r\nE";
$clean = IaInvisibleCleaner::clean($dirty, true);

echo $clean; // "ABCDE"
```

### Normalize and clean

``` php
// 'e' + combining acute accent
$dirty = "e\u{0301}";

$clean = IaInvisibleCleaner::normalizeAndClean($dirty, false, IaInvisibleCleaner::MODE_DEFAULT, true);

// With ext/intl installed, $clean will be a single "é"
```

### Hardened / Aggressive cleaning modes

``` php
$dirty = "A\u{00AD}B"; // contains a soft hyphen

// Hardened mode removes soft hyphen, math invisibles, deprecated bidi, tags, annotations
$clean = IaInvisibleCleaner::clean($dirty, false, IaInvisibleCleaner::MODE_HARDENED);

// Aggressive mode also removes variation selectors (may change emoji rendering)
$clean = IaInvisibleCleaner::clean($dirty, false, IaInvisibleCleaner::MODE_AGGRESSIVE);
```

## 🖥 CLI usage

The package includes a CLI binary:

``` bash
echo "Some text with\u{200B}invisible chars" | vendor/bin/invisible-cleaner
```

Remove all whitespace as well:

``` bash
echo "Some text with spaces and tabs" | vendor/bin/invisible-cleaner --no-whitespace
```

## ✅ Tests

Run the test suite with PHPUnit:

``` bash
vendor/bin/phpunit tests
```

## 📦 Requirements

- PHP **8.1+**
- `ext-mbstring`

## 📜 License

MIT License -- feel free to use in commercial or open-source projects.
