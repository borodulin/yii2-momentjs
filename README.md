# Moment.js widget for Yii2 framework

[![Latest Version](https://img.shields.io/packagist/v/conquer/momentjs.svg)](https://packagist.org/packages/conquer/momentjs)
[![Total Downloads](https://img.shields.io/packagist/dt/conquer/momentjs.svg)](https://packagist.org/packages/conquer/momentjs)
[![CI](https://github.com/borodulin/yii2-momentjs/actions/workflows/ci.yml/badge.svg)](https://github.com/borodulin/yii2-momentjs/actions/workflows/ci.yml)
[![License](https://img.shields.io/packagist/l/conquer/momentjs.svg)](LICENSE.md)

## Description

Parse, validate, manipulate, and display dates in JavaScript.
For more information please visit [Moment.js](https://momentjs.com/).

## Requirements

- PHP 7.4 or 8.x
- Yii 2.0
- [asset-packagist](https://asset-packagist.org) repository configured in your application's `composer.json` (standard for Yii2 app templates)

## Installation

Install the extension via [Composer](https://getcomposer.org):

```bash
composer require conquer/momentjs:^1.2
```

## Usage

```php
\conquer\momentjs\MomentjsAsset::register($this);
```

Moment.js is loaded with the locale of `Yii::$app->language` (`ru-RU` falls back to `ru`, English needs no file),
set globally before the inline scripts of the page run.

```php
use conquer\momentjs\MomentjsAsset;

MomentjsAsset::$language = 'de';     // another locale
MomentjsAsset::$language = false;    // no locale
MomentjsAsset::$allLocales = true;   // all locales (~370 KB), e.g. to call moment.locale() in the browser
```

### PHP date format

`MomentjsAsset::fromPhpFormat()` converts a PHP [`date()`](https://www.php.net/manual/en/datetime.format.php) format
to a [Moment.js format](https://momentjs.com/docs/#/displaying/format/):

```php
MomentjsAsset::fromPhpFormat('D, jS F Y H:i');   // "ddd, Do MMMM YYYY HH:mm"
MomentjsAsset::fromPhpFormat('Y-m-d\TH:i:sP');   // "YYYY-MM-DD[T]HH:mm:ssZ"
```

Escaped (`\x`) and other literal characters are kept. Characters Moment.js cannot output are removed:
`z`, `t`, `L`, `B`, `I`, `e`, `T`, `Z`, `p`, `X`, `x`, and `S` except right after `j` (`jS` → `Do`).

## Upgrading to 1.2

- Only the selected locale is loaded (`min/moment.min.js` + `locale/<lang>.js`) instead of `moment-with-locales.min.js`.
  If you switch the locale in the browser, set `MomentjsAsset::$allLocales = true`.
- The locale is set at `View::POS_END` (before the other inline scripts) instead of on document ready.
- `sourcePath` is `@bower/moment` (was `@bower/moment/min`); only `min/` and `locale/` are published.
- `fromPhpFormat()` output changed for the formats it converted wrongly (`D`, `l`, `S`, `N`, `w`, `W`, escapes):
  re-check stored or hard-coded converted formats.

## License

**conquer/momentjs** is released under the MIT License. See the bundled [`LICENSE.md`](LICENSE.md) for details.
