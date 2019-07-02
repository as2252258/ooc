<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/10/7 0007
 * Time: 2:16
 */

namespace Yoc\console;


use Yoc\base\BApp;
use Yoc\core\Str;
use Yoc\web\Action;

class Application extends BApp
{

	/**
	 * @var string
	 */
	public $id = 'uniqueId';


	/**
	 * @throws
	 */
	public function run()
	{
		global $argv;

		/** @var UrlManager $urlManager */
		$urlManager = $this->get('urlManager');
		try {
			/** @var Action $action */
			$action = $urlManager->requestHandler($argv);
			$action->runWithParam();
		} catch (\Exception $exception) {
			echo 'error: ' . $exception->getMessage() . PHP_EOL;
		}
	}

}
