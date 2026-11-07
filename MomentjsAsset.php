<?php
/**
 * @link https://github.com/borodulin/yii2-momentjs
 * @copyright Copyright (c) 2015 Andrey Borodulin
 * @license https://github.com/borodulin/yii2-momentjs/blob/master/LICENSE.md
 */

namespace conquer\momentjs;

use Yii;
use yii\helpers\Json;
use yii\web\AssetBundle;
use yii\web\View;

/**
 * Moment.js with the locale of the application.
 *
 * Only the locale of [[language]] (or `Yii::$app->language`) is loaded and set globally
 * before the inline scripts of the page run.
 *
 * @author Andrey Borodulin
 */
class MomentjsAsset extends AssetBundle
{
    public $sourcePath = '@bower/moment';

    public $publishOptions = [
        'only' => ['min/*', 'locale/*'],
    ];

    /**
     * Locale set globally with `moment.locale()`.
     * null (default) uses `Yii::$app->language`, false loads Moment.js without a locale.
     * @link https://momentjs.com/docs/#/i18n/
     * @var string|bool|null
     */
    public static $language;

    /**
     * Load all locales (`moment-with-locales.min.js`, about 370 KB) instead of the selected one,
     * e.g. to switch the locale with `moment.locale()` in the browser.
     * @var bool
     */
    public static $allLocales = false;

    /**
     * @var array PHP date() format character => Moment.js format token ('' for characters Moment.js cannot output)
     */
    public static $phpFormatTokens = [
        // day
        'd' => 'DD', 'D' => 'ddd', 'j' => 'D', 'l' => 'dddd', 'N' => 'E', 'S' => '', 'w' => 'd', 'z' => '',
        // week
        'W' => 'WW',
        // month
        'F' => 'MMMM', 'm' => 'MM', 'M' => 'MMM', 'n' => 'M', 't' => '',
        // year
        'L' => '', 'o' => 'GGGG', 'X' => '', 'x' => '', 'Y' => 'YYYY', 'y' => 'YY',
        // time
        'a' => 'a', 'A' => 'A', 'B' => '', 'g' => 'h', 'G' => 'H', 'h' => 'hh', 'H' => 'HH',
        'i' => 'mm', 's' => 'ss', 'u' => 'SSSSSS', 'v' => 'SSS',
        // timezone
        'e' => '', 'I' => '', 'O' => 'ZZ', 'P' => 'Z', 'p' => '', 'T' => '', 'Z' => '',
        // full date/time
        'c' => 'YYYY-MM-DD[T]HH:mm:ssZ', 'r' => 'ddd, DD MMM YYYY HH:mm:ss ZZ', 'U' => 'X',
    ];

    /**
     * @inheritdoc
     */
    public function registerAssetFiles($view)
    {
        $this->js = ['min/moment.min.js'];
        if (static::$language !== false) {
            $language = static::$language ? (string)static::$language : Yii::$app->language;
            if (static::$allLocales) {
                $this->js = ['min/moment-with-locales.min.js'];
            } elseif (($locale = $this->findLocale($language)) !== null) {
                $this->js[] = "locale/$locale.js";
                $language = $locale;
            }
            // set the locale right after Moment.js is loaded, before the other inline scripts
            $js = 'moment.locale(' . Json::htmlEncode($language) . ');';
            $view->js[View::POS_END] = [md5($js) => $js]
                + (isset($view->js[View::POS_END]) ? (array)$view->js[View::POS_END] : []);
        }
        parent::registerAssetFiles($view);
    }

    /**
     * @param string $language language code, e.g. `ru-RU`
     * @return string|null name of the Moment.js locale file for the language, e.g. `ru`
     */
    public function findLocale($language)
    {
        $language = strtolower(str_replace('_', '-', $language));
        if (!preg_match('/^[a-z]{2,3}(-[a-z0-9]+)*$/', $language)) {
            return null;
        }
        $dir = Yii::getAlias($this->sourcePath, false) . '/locale';
        $parts = explode('-', $language);
        // "zh-hant-tw" -> "zh-hant" -> "zh"
        for ($i = count($parts); $i > 0; $i--) {
            $locale = implode('-', array_slice($parts, 0, $i));
            if (is_file("$dir/$locale.js")) {
                return $locale;
            }
        }
        return null;
    }

    /**
     * Converts a PHP date() format to a Moment.js format.
     *
     * Escaped (`\x`) and other literal characters are kept. Characters Moment.js cannot output
     * (`z`, `t`, `L`, `B`, `I`, `e`, `T`, `Z`, `p`, `X`, `x`, and `S` except after `j`) are removed.
     *
     * @param string $format PHP date format
     * @return string
     *
     * @link https://www.php.net/manual/en/datetime.format.php
     * @link https://momentjs.com/docs/#/displaying/format/
     */
    public static function fromPhpFormat($format)
    {
        $result = '';
        $literal = '';
        $length = strlen($format);
        for ($i = 0; $i < $length; $i++) {
            $char = $format[$i];
            if ($char === '\\') {
                $i++;
                if ($i < $length) {
                    $literal .= $format[$i];
                }
                continue;
            }
            if (isset(static::$phpFormatTokens[$char])) {
                $result .= static::escapeLiteral($literal);
                $literal = '';
                if ($char === 'j' && $i + 1 < $length && $format[$i + 1] === 'S') {
                    // day of month with English suffix: 7th
                    $result .= 'Do';
                    $i++;
                } else {
                    $result .= static::$phpFormatTokens[$char];
                }
                continue;
            }
            $literal .= $char;
        }
        return $result . static::escapeLiteral($literal);
    }

    /**
     * @param string $text literal text
     * @return string the text escaped for a Moment.js format
     */
    protected static function escapeLiteral($text)
    {
        if ($text === '' || !preg_match('/[A-Za-z\[\]\\\\]/', $text)) {
            // characters other than letters are not Moment.js tokens
            return $text;
        }
        if (strpbrk($text, '[]') === false) {
            return '[' . $text . ']';
        }
        return preg_replace('/./su', '\\\\$0', $text);
    }
}
