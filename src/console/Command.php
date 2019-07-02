<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/10/7 0007
 * Time: 2:17
 */

namespace Yoc\console;


use Yoc\base\Component;
use Yoc\http\formatter\IFormatter;
use Yoc\web\Action;

abstract class Command extends Component implements ICommand
{
	/**
	 * @return mixed
	 */
	public function exec()
	{
    	return $this->handler();
	}

	/**
	 * @param mixed ...$param
	 * @return string
	 * @throws
	 */
	public function output(...$param)
	{
		/** @var IFormatter $build */
		$build = \Yoc::$app->response->sender(...$param);
		if (!empty($build)) {
			print_r($build);
		}
		echo 'Command Success!' . PHP_EOL;
		exit;
	}

	/**
	 * @param Action $action
	 * @return bool
	 */
	public function beforeAction(Action $action)
	{
		return TRUE;
	}

	/**
	 * @param $action
	 * @param $result
	 * @return mixed
	 */
	public function afterAction($action, $result = NULL)
	{
		return TRUE;
	}
}
