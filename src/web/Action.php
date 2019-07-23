<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/4/24 0024
 * Time: 19:24
 */

namespace Beauty\web;


use Beauty\base\Component;
use Beauty\exception\RequestException;

class Action extends Component
{
	
	const BEFORE_ACTION = 'beforeAction';
	const AFTER_ACTION = 'afterAction';
	
	/**
	 * @var string
	 *
	 * action name
	 */
	public $id;
	
	/**
	 * @var Controller
	 */
	public $controller;
	
	/**
	 * @return mixed
	 *
	 * return string
	 */
	public function getUniqueId()
	{
		return $this->id;
	}
	
	/**
	 * @return Controller
	 * return implements Beauty\web\controller
	 */
	public function getController()
	{
		return $this->controller;
	}
	
	/**
	 * @param array $param
	 * @return mixed
	 * @throws
	 */
	public function runWithParam($param = [])
	{
		$request = \Beauty::$app->request;
		
		if (empty($this->id) || empty($this->controller)) {
			throw new RequestException("Page not found.", 404);
		}
		
		$control = $this->controller;
		$action = 'action' . ucfirst($this->id);
		if (!method_exists($control, $action)) {
			$message = "Unable to resolve the request {$request->headers->getHeader('request_uri')}.";
			
			throw new RequestException($message, 404);
		}

		return $control->$action($request);
	}
}
