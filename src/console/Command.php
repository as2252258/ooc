<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/10/7 0007
 * Time: 2:17
 */

namespace Beauty\console;


use Beauty\base\Component;
use Beauty\web\Action;

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
