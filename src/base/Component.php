<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/3/30 0030
 * Time: 14:28
 */

namespace Yoc\base;


use app\model\server\Auth;
use Yoc\error\Logger;
use Yoc\event\Event;

class Component extends BObject
{


	/**
	 * @var array
	 */
	private $_events = [];

	/**
	 * @param $name [事件名称]
	 * @param $class [类]
	 * @param $callback [回调函数]
	 * @param $param [函数参数]
	 *
	 * {
	 *      事件名, 回调, 参数
	 * }
	 */
	public function on($name, $callback, $param = [])
	{
		if (isset($this->_events[$name])) {
			array_push($this->_events[$name], [$callback, $param]);
		} else {
			$this->_events[$name][] = [$callback, $param];
		}
	}

	/**
	 * @param $name
	 * @param null $event
	 * @throws \Exception
	 */
	public function trigger($name, $event = NULL)
	{
		if (!empty($this->_events[$name])) {

			if ($event === null) {
				$event = new Event();
			}

			if($event->sender === null){
				$event->sender = $this;
			}

			foreach ($this->_events[$name] as $key => $val) {
				if(!is_callable($val[0],true)){
					continue;
				}
				call_user_func($val[0]);
				if(!$event->isVild){
					return;
				}
			}
			unset($this->_events[$name]);
		}
		Event::trigger($name, $event);
	}

	/**
	 * @param $name
	 * @param null $handler
	 */
	public function off($name, $handler = NULL)
	{
		if (!isset($this->_events[$name])) {
			return;
		}

		if (empty($handler)) {
			unset($this->_events[$name]);
		} else {
			foreach ($this->_events[$name] as $key => $val) {
				if ($val[0] != $handler) {
					continue;
				}
				unset($this->_events[$name][$key]);
				break;
			}
		}
	}


	public function offAll()
	{
		$this->_events = [];
		Event::offAll();
	}


	/**
	 * @param $name
	 * @return mixed
	 * @throws \Exception
	 */
	public function __get($name)
	{
		$method = 'get' . ucfirst($name);
		if (property_exists($this, $name)) {
			return $this->$name;
		} elseif (method_exists($this, $method)) {
			return $this->$method();
		} else {
			return parent::__get($name);
		}
	}
}
