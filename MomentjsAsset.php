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
	public $sourcePath = '@bower/moment';
	
	public $js=[
		'min/moment-with-locales.min.js',
	];
	
}