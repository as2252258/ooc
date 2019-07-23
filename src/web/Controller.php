<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/4/24 0024
 * Time: 19:28
 */

namespace Beauty\web;


use Beauty\base\Component;
use Beauty\base\Config;
use Beauty\db\DbPool;
use Beauty\pool\ObPool;
use Beauty\server\Request;

class Controller extends Component
{

	/** @var string */
	public $id;

	/** @var Action */
	public $action;

	/**
	 * @return mixed
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * @return Action
	 */
	public function getAction()
	{
		return $this->action;
	}

	/**
	 * @param $id
	 * @return mixed|Action
	 * @throws \ReflectionException
	 */
	public function createAction($id)
	{
		$class = new Action();
		$class->id = $id;
		$class->controller = $this;
		return $class;
	}

	/**
	 * @return mixed
	 * @throws \Beauty\exception\RequestException
	 */
	public function runWithParam($param = null)
	{
		return $this->getAction()->runWithParam($param);
	}

	/**
	 * @param string $message
	 * @return mixed
	 * @throws \Exception
	 */
	public function notFind($message = '')
	{
		$response = \Beauty::$app->response;
		$response->statusCode = 404;
		return $response->sender($message);
	}

	/**
	 * @return array
	 */
	public function actions()
	{
		return [];
	}
}
