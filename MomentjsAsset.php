<?php
/**
 * @link https://github.com/borodulin/yii2-momentjs
 * @copyright Copyright (c) 2015 Andrey Borodulin
 * @license https://github.com/borodulin/yii2-momentjs/blob/master/LICENSE.md
 */
namespace conquer\moment;

class MomentjsAsset extends \yii\web\AssetBundle
{
	// The files are not web directory accessible, therefore we need
	// to specify the sourcePath property. Notice the @bower alias used.
	public $sourcePath = '@bower/moment/min';
	
	public static $language=false;
	
	public function registerAssetFiles($view)
	{
		if(self::$language){
			$this->js=['moment-with-locales.min.js'];
			//set locale globally
			$view->registerJs('moment.locale("'.self::$language.'");');
		}else
			$this->js=['moment.min.js'];
		
		parent::registerAssetFiles($view);
	}
	
}