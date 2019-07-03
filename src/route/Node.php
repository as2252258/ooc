<?php


namespace Yoc\route;


use Exception;
use Yoc\error\Logger;
use Yoc\exception\NotFindClassException;

/**
 * Class Node
 * @package Yoc\route
 */
class Node
{

	public $path;
	public $index = 0;
	public $method;

	/** @var Node[] $childs */
	public $childs = [];

	public $group = [];
	public $options = null;

	public $rules = [];
	public $handler;
	public $htmlSuffix = '.html';
	public $enableHtmlSuffix = false;
	public $namespace = [];
	public $middleware = [];


	/**
	 * @param $handler
	 * @throws
	 */
	public function bindHandler($handler)
	{
		if (is_string($handler) && strpos($handler, '@') !== false) {
			list($controller, $action) = explode('@', $handler);
			if (!empty($this->namespace)) {
				$controller = implode('\\', $this->namespace) . '\\' . $controller;
			}
			try {
				$reflect = new \ReflectionClass($controller);
				if (!$reflect->isInstantiable()) {
					throw new Exception($controller . ' Class is con\'t Instantiable.');
				}

				if (!$reflect->hasMethod($action)) {
					throw new Exception('method ' . $action . ' not exists at ' . $controller . '.');
				}
				$this->handler = [$reflect->newInstance(), $action];
			} catch (Exception $exception) {
				Logger::error($exception->getMessage(), 'router');
				return;
			}
		} else if ($handler instanceof \Closure) {
			$this->handler = $handler;
		} else if ($handler != null && !is_callable($handler, true)) {
			Logger::error('Controller is con\'t exec.');
		} else {
			$this->handler = $handler;
		}

	}

	/**
	 * @param Node $node
	 * @param string $field
	 * @return Node
	 */
	public function addChild(Node $node, string $field)
	{
		if (isset($this->childs[$field])) {
			$this->childs[$field] = $node;
		} else {
			$this->childs[$field] = $node;
		}
		return $this->childs[$field];
	}

	/**
	 * @param $rule
	 * @return $this
	 */
	public function filter($rule)
	{
		if (empty($rule)) {
			return $this;
		}
		if (!isset($rule[0])) {
			$rule = [$rule];
		}
		foreach ($rule as $value) {
			if (empty($value)) {
				continue;
			}
			array_push($this->rules, $value);
		}

		return $this;
	}

	/**
	 * @param string $search
	 * @return Node|mixed
	 * 查找子节点
	 */
	public function findNode(string $search)
	{
		if (empty($this->childs)) {
			return null;
		}
		foreach ($this->childs as $key => $val) {
			if (strpos($key, '<') !== false) {
				if (preg_match('/' . $search . '/', $key)) {
					return $this->childs[$key];
				}
			}

			if ($search === $key) {
				return $this->childs[$key];
			}
		}
		return null;
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
		$mid = new Middleware();
		$mid->bindParams(\Yoc::$app->request);

		if (!empty($this->rules)) {
			foreach ($this->rules as $rule) {
				if (!isset($rule['class'])) {
					$rule['class'] = Filter::class;
				}

				$object = \Yoc::createObject($rule);
				$object->handler();
			}
		}

		foreach ($this->middleware as $val) {
			$mid->set($val);
		}
		return $mid->exec($call);
	}

	/**
	 * @param $options
	 * @return $this
	 */
	public function bindOptions($options)
	{
		$options = array_filter($options);
		$last = $options[count($options) - 1];
		if (empty($last)) {
			return $this;
		}
		$this->options = $last;
		return $this;
	}

	/**
	 * @param $middles
	 * @throws \ReflectionException
	 */
	public function bindMiddleware($middles)
	{
		$_tmp = [];
		foreach ($middles as $class) {
			if (is_string($class) && class_exists($class)) {
				$reflect = new \ReflectionClass($class);

				$_tmp[] = $reflect->newInstance();
			} else if ($class instanceof \Closure) {
				$_tmp[] = $class;
			} else {
				$_tmp[] = $class;
			}
		}
		$this->middleware = $_tmp;
	}

	/**
	 * @return mixed
	 * @throws
	 */
	public function execOptions()
	{
		if (!is_callable($this->options, true)) {
			throw new \Exception('Option callback can\'t exec.');
		}
		return call_user_func($this->options);
	}
}
