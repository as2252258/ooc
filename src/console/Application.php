<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/10/7 0007
 * Time: 2:16
 */

namespace Beauty\console;


use Beauty\base\BApp;
use Beauty\core\Str;
use Beauty\web\Action;

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

		try {
			setCommand(true);
			/** @var UrlManager $urlManager */
			$urlManager = $this->get('urlManager');

			/** @var Command $action */
			$action = $urlManager->requestHandler($argv);
			return response()->send($action->exec());
		} catch (\Exception $exception) {
			return response()->send($exception->getMessage());
		}
	}

}
