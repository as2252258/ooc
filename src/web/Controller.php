<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/4/24 0024
 * Time: 19:28
 */

namespace Yoc\web;


use Yoc\base\Component;
use Yoc\base\Config;
use Yoc\db\DbPool;
use Yoc\pool\ObPool;
use Yoc\server\Request;

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
	 * @throws \Yoc\exception\RequestException
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
		$response = \Yoc::$app->response;
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
