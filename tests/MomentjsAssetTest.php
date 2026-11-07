<?php

namespace conquer\momentjs\tests;

use conquer\momentjs\MomentjsAsset;
use PHPUnit\Framework\TestCase;
use Yii;
use yii\helpers\FileHelper;
use yii\web\Application;
use yii\web\View;

class MomentjsAssetTest extends TestCase
{
    private $assetPath;

    protected function setUp(): void
    {
        $this->assetPath = sys_get_temp_dir() . '/momentjs-test-' . uniqid();
        mkdir($this->assetPath);
        new Application([
            'id' => 'test',
            'basePath' => __DIR__,
            'vendorPath' => dirname(__DIR__) . '/vendor',
            'language' => 'ru-RU',
            'aliases' => ['@bower' => '@vendor/bower-asset', '@npm' => '@vendor/npm-asset'],
            'components' => [
                'request' => ['cookieValidationKey' => 'test', 'scriptFile' => __DIR__ . '/index.php', 'scriptUrl' => '/index.php'],
                'assetManager' => ['basePath' => $this->assetPath, 'baseUrl' => '/assets'],
            ],
        ]);
        MomentjsAsset::$language = null;
        MomentjsAsset::$allLocales = false;
    }

    protected function tearDown(): void
    {
        FileHelper::removeDirectory($this->assetPath);
        Yii::$app = null;
    }

    /**
     * Expected values are what Moment.js 2.30 outputs for the converted format,
     * checked against PHP date() for 2024-11-07 13:05:09.123 UTC (Thursday).
     */
    public function formatProvider()
    {
        return [
            ['Y-m-d H:i:s', 'YYYY-MM-DD HH:mm:ss'],
            ['D, d M Y', 'ddd, DD MMM YYYY'],
            ['l', 'dddd'],
            ['jS F Y', 'Do MMMM YYYY'],
            ['N w', 'E d'],
            ['W o', 'WW GGGG'],
            ['g:i a', 'h:mm a'],
            ['G:i A', 'H:mm A'],
            ['n/j/y', 'M/D/YY'],
            ['H:i:s.v', 'HH:mm:ss.SSS'],
            ['O P', 'ZZ Z'],
            ['U', 'X'],
            ['c', 'YYYY-MM-DD[T]HH:mm:ssZ'],
            ['r', 'ddd, DD MMM YYYY HH:mm:ss ZZ'],
            ['Y-m-d\TH:i:s', 'YYYY-MM-DD[T]HH:mm:ss'],
            ['\d\a\y d', '[day ]DD'],
            ['d.m.Y г.', 'DD.MM.YYYY г.'],
            ['\[d\]', '\[DD\]'],
            ['d t L', 'DD  '],
            ['dS', 'DD'],
            ['Y\\', 'YYYY'],
        ];
    }

    /**
     * @dataProvider formatProvider
     */
    public function testFromPhpFormat($php, $moment)
    {
        $this->assertSame($moment, MomentjsAsset::fromPhpFormat($php));
    }

    public function testFindLocale()
    {
        $bundle = new MomentjsAsset();
        $this->assertSame('ru', $bundle->findLocale('ru-RU'));
        $this->assertSame('ru', $bundle->findLocale('ru'));
        $this->assertSame('en-gb', $bundle->findLocale('en-GB'));
        $this->assertSame('zh-cn', $bundle->findLocale('zh_CN'));
        $this->assertSame('pt-br', $bundle->findLocale('pt-BR'));
        $this->assertNull($bundle->findLocale('en-US'));
        $this->assertNull($bundle->findLocale('xx'));
        $this->assertNull($bundle->findLocale('../moment'));
    }

    private function render(callable $beforeRegister = null)
    {
        $view = new View();
        if ($beforeRegister) {
            $beforeRegister($view);
        }
        MomentjsAsset::register($view);
        return $view->renderFile(__DIR__ . '/views/page.php');
    }

    public function testLocaleOfApplication()
    {
        $html = $this->render(function (View $view) {
            $view->registerJs('window.early = moment().format("MMMM");', View::POS_END);
        });
        $this->assertMatchesRegularExpression('#src="/assets/\w+/min/moment\.min\.js"#', $html);
        $this->assertMatchesRegularExpression('#src="/assets/\w+/locale/ru\.js"#', $html);
        $this->assertStringNotContainsString('moment-with-locales', $html);
        // the locale is set before the other inline scripts
        $this->assertLessThan(strpos($html, 'window.early'), strpos($html, 'moment.locale("ru");'));
        $this->assertLessThan(strpos($html, 'moment.locale('), strpos($html, 'locale/ru.js'));
    }

    public function testPublishesOnlyMinAndLocale()
    {
        $this->render();
        $dirs = glob($this->assetPath . '/*', GLOB_ONLYDIR);
        $this->assertCount(1, $dirs);
        $this->assertFileExists($dirs[0] . '/min/moment.min.js');
        $this->assertFileExists($dirs[0] . '/locale/ru.js');
        $this->assertFileDoesNotExist($dirs[0] . '/moment.js');
        $this->assertDirectoryDoesNotExist($dirs[0] . '/src');
    }

    public function testAllLocales()
    {
        MomentjsAsset::$allLocales = true;
        $html = $this->render();
        $this->assertStringContainsString('min/moment-with-locales.min.js', $html);
        $this->assertStringNotContainsString('locale/ru.js', $html);
        $this->assertStringContainsString('moment.locale("ru-RU");', $html);
    }

    public function testExplicitLanguageIsEncoded()
    {
        MomentjsAsset::$language = '</script><script>alert(1)</script>';
        $html = $this->render();
        $this->assertStringNotContainsString('<script>alert(1)', $html);
        // "<" is JSON-escaped
        $this->assertStringContainsString('moment.locale("\\' . 'u003C\/script\\' . 'u003E', $html);
    }

    public function testEnglishHasNoLocaleFile()
    {
        MomentjsAsset::$language = 'en-US';
        $html = $this->render();
        $this->assertStringNotContainsString('/locale/', $html);
        $this->assertStringContainsString('moment.locale("en-US");', $html);
    }

    public function testLanguageFalse()
    {
        MomentjsAsset::$language = false;
        $html = $this->render();
        $this->assertStringContainsString('min/moment.min.js', $html);
        $this->assertStringNotContainsString('moment.locale', $html);
        $this->assertStringNotContainsString('/locale/', $html);
    }
}
