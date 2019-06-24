<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2019-03-20
 * Time: 02:14
 */

namespace Yoc\route;


use Yoc\exception\NotFindClassException;
use Yoc\exception\RequestException;

class Route
{
	private $method = 'POST';
	private $uri = '';
	private $callback = null;
	private $bindParam = [];
	private $bindHeader = [];
	private $middleware = [];
	private $namespace = '';

	private $after;
	private $config;

	/**
	 * route constructor.
	 * @param $uri
	 * @param $method
	 * @param $callback
	 * @throws
	 */
	public function __construct($uri, $method, $callback)
	{
		$this->method = $method;
		$this->uri = $uri;
		$this->callback = $callback;
	}


	/**
	 * @param $callback
	 * @return array|null|\Closure
	 * @throws
	 */
	private function getReflect($callback)
	{
		if ($callback instanceof \Closure) {
			return $callback;
		}

		$callback = explode('@', $callback);
		if (count($callback) < 2) {
			return null;
		}

		$prefix = 'app\\controller\\';
		if (!empty($this->namespace)) {
			$prefix .= rtrim($this->namespace, '\\\\') . '\\';
		}
		$class = $prefix . $callback[0];

		if (!class_exists($class)) {
			return null;
		}
		return [new $class(), $callback[1]];
	}

	/**
	 * @param string $name
	 * @return $this
	 * 设置命名空间
	 */
	public function namespace(string $name)
	{
		$this->namespace = $name;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getMethod()
	{
		return $this->method;
	}

	/**
	 * @param $method
	 * @return $this
	 */
	public function addMethod($method)
	{
		if (!is_array($this->method)) {
			$this->method = [$this->method];
		}

		array_push($this->method, $method);
		return $this;
	}


	/**
	 * @return string
	 */
	public function getUri()
	{
		return $this->uri;
	}

	/**
	 * @return null
	 */
	public function getCallback()
	{
		return $this->callback;
	}

	/**
	 * @return $this
	 * 清除中间件
	 */
	public function clearMiddleware()
	{
		$this->middleware = [];
		return $this;
	}

	/**
	 * @param $call
	 * @return $this
	 */
	public function middleware($call)
	{
		if (is_array($call)) {
			return $this->middlewares($call);
		}
		$this->resolveMiddleware($call);
		return $this;
	}

	/**
	 * @param array $call
	 * @return $this
	 */
	public function middlewares(array $call)
	{
		foreach ($call as $key => $val) {
			if (empty($val)) {
				continue;
			}
			$this->resolveMiddleware($val);
		}
		return $this;
	}

	/**
	 * @param $call
	 * @throws
	 */
	private function resolveMiddleware($call)
	{
		if ($call instanceof \Closure) {
			$this->middleware[] = $call;
			return;
		}

		if (class_exists($call)) {
			$call = new \ReflectionClass($call);

			if (!$call->isInstantiable()) {
				return;
			}

			$call = $call->newInstance();
		}

		//If callback is a object and check Object is hav handler action
		if (is_object($call) && method_exists($call, 'handler')) {
			$this->middleware[] = $call;
			return;
		}

		return;
	}

	/**
	 * @param $key
	 * @param $value
	 * @return $this
	 */
	public function bindHeader($key, $value)
	{
		$this->bindHeader[$key] = $value;
		return $this;
	}

	/**
	 * @param $name
	 * @param $value
	 * @return $this
	 */
	public function bindParam($name, $value)
	{
		$this->bindParam[$name] = $value;
		return $this;
	}

	/**
	 * @param $call
	 * @return $this
	 */
	public function setAfter($call)
	{
		$this->after = $call;
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getAfter()
	{
		return $this->after;
	}

	/**
	 * @param $callback
	 * @return $this
	 */
	public function before($callback)
	{
		$this->resolveMiddleware($callback);
		return $this;
	}

	/**
	 * @return $this
	 */
	public function resolve()
	{
		$this->callback = $this->getReflect($this->callback);
		return $this;
	}

	/**
	 * @param $call
	 * @return mixed
	 * @throws
	 */
	public function run($call)
	{
		if (empty($call)) {
			throw new NotFindClassException();
		}
		$mid = new MiddlewareHandler();
		$mid->bindParams(\Yoc::$app->request);
		foreach ($this->middleware as $val) {
			$mid->set($val);
		}
		$resp = $mid->exec($call);
		if ($this->after) {
			return ($this->after)($resp);
		}
		return $resp;
	}

}
