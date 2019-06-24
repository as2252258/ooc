<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/3/30 0030
 * Time: 14:10
 */

namespace Yoc\base;


use Yoc\error\Logger;

class BObject implements Configure
{

	/**
	 * BaseAbstract constructor.
	 *
	 * @param array $config
	 */
	public function __construct(array $config = [])
	{
		if (!empty($config) && is_array($config)) {
			\Yoc::configure($this, $config);
		}
		$this->init();
	}

	public function init()
	{

	}

	/**
	 * @return string
	 */
	public static function className()
	{
		return get_called_class();
	}

	/**
	 * @param $name
	 * @param $value
	 *
	 * @throws \Exception
	 */
	public function __set($name, $value)
	{
		$method = 'set' . ucfirst($name);
		if (method_exists($this, $method)) {
			$this->{$method}($value);
		} else {
			throw new \Exception('The set name ' . $name . ' not find in class ' . get_class($this));
		}
	}

	/**
	 * @param $name
	 *
	 * @return mixed
	 * @throws \Exception
	 */
	public function __get($name)
	{
		$method = 'get' . ucfirst($name);
		if (method_exists($this, $method)) {
			return $this->$method();
		} else {
			throw new \Exception('The get name ' . $name . ' not find in class ' . get_class($this));
		}
	}

	/**
	 * @param $name
	 * @param $arguments
	 *
	 * @return mixed
	 * @throws \Exception
	 */
	public function __call($name, $arguments)
	{
		if (!method_exists($this, $name)) {
			throw new \Exception("Not find " . get_called_class() . "::($name)");
		} else {
			return $this->$name(...$arguments);
		}
	}

	/**
	 * @param $message
	 * @param string $model
	 * @return bool
	 * @throws \Exception
	 */
	public function addError($message, $model = 'app')
	{
		if ($message instanceof \Exception) {
			$message = $message->getCode() . ' ' . $message->getMessage();
		}
		Logger::error($message, $model);
		return FALSE;
	}

	/**
	 * @param       $callback
	 * @param array $param
	 *
	 * @return bool
	 * @throws \Exception
	 */
	public function task($callback, $param = [])
	{
		$server = \Yoc::$app->socket;
		if (!is_array($param)) {
			$param = [$param];
		}

		$worker = $server->getRandWorker();

		$format = serialize([$callback, $param]);

		return $server->getSocket()->task($format, $worker);
	}

	private $defer = [];

	public function defer($callback)
	{
		if (!is_callable($callback, TRUE)) {
			return;
		}
		$this->defer[] = $callback;
	}

	public function triDefer()
	{
		if (empty($this->defer)) {
			return;
		}
		foreach ($this->defer as $val) {
			call_user_func($val);
		}
		$this->defer = null;
	}
}
