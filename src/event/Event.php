<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/4/24 0024
 * Time: 18:32
 */

namespace Beauty\event;


use Beauty\base\BObject;

class Event extends BObject
{

	public $event;

	public $sender;

	public $isVild = FALSE;

	public $data = NULL;

	public static $_events = [];

	private $listens = [];

	/**
	 * @param $name
	 * @param $handler
	 * @param array $params
	 * @param bool $append
	 * @return static
	 */
	public function listen($name, $handler, $params = [], $append = true)
	{
		if (!isset($this->listens[$name])) {
			$this->listens[$name] = [[$handler, $params]];
		} else {
			foreach ($this->listens[$name] as $listen) {
				if ($handler === $listen) {
					return $this;
				}
			}

			if (!$append) {
				array_unshift($this->listens[$name], [$handler, $params]);
			} else {
				array_push($this->listens[$name], [$handler, $params]);
			}
		}
		return $this;
	}


	/**
	 * @param $name
	 * @param null $callback
	 * @param bool $isRemove
	 * @return bool
	 */
	public function try($name, $callback = null, $isRemove = false)
	{
		if (!isset($this->listens[$name])) {
			return false;
		}

		$handlers = [];
		foreach ($this->listens[$name] as $key => $listen) {

			if (!isset($listen[0]) || !is_callable($listen[0])) {
				continue;
			}

			if ($callback !== null) {
				if ($callback !== $listen) {
					continue;
				}

				$handlers[] = $listen;

				if ($isRemove) {
					unset($this->listens[$name][$key]);
				}

				continue;
			}
			$handlers[] = $listen;
		}

		foreach ($handlers as $key => $val) {
			call_user_func(...$val);
		}

		return true;
	}

	public function off($name, $handler = null)
	{
		if (!isset($this->listens[$name])) {
			return $this;
		}

		if ($handler === null) {
			$this->listens[$name] = [];
			return $this;
		}

		foreach ($this->listens[$name] as $index => $listen) {

			if ($handler === $listen) {
				unset($this->listens[$name][$index]);
				break;
			}

		}

		return $this;
	}

	public function closeAll()
	{
		$this->listens = [];
	}

	/**
	 * @param $class
	 * @param $name
	 * @param $callback
	 * @param array $param
	 * @param bool $append
	 * @throws
	 */
	public static function on($name, $callback, $param = [], $append = TRUE)
	{
		if (!isset(static::$_events[$name])) {
			static::$_events[$name][] = [$callback, $param];
		} else {
			$class = static::$_events[$name];

			foreach ($class as $handler) {
				if ($handler[0] == $callback) {
					return;
				}
			}

			if ($append) {
				array_push($class, [$callback, $param]);
			} else {
				array_unshift($class, [$callback, $param]);
			}
			static::$_events = $class;
		}
	}

	/**
	 * @param $class
	 * @param $name
	 * @param null $event
	 * @throws \Exception
	 */
	public static function trigger($name, $event = NULL)
	{
		$events = static::$_events[$name] ?? NULL;
		if ($events === NULL) {
			return;
		}

		if (!$event) {
			$event = new Event();
		}

		foreach ($events as $handlers) {
			$event->data = $handlers[1];
			call_user_func($handlers[0], $event);
			if (!$event->isVild) {
				break;
			}
		}
	}

	public static function offAll()
	{
		static::$_events = [];
	}
}
