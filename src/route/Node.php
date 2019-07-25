<?php


namespace Beauty\route;


use Exception;
use Beauty\error\Logger;
use Beauty\exception\NotFindClassException;

/**
 * Class Node
 * @package Beauty\route
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
			$this->handler = $this->getReflect($controller, $action);
		} else if ($handler instanceof \Closure) {
			$this->handler = $handler;
		} else if ($handler != null && !is_callable($handler, true)) {
			Logger::error('Controller is con\'t exec.');
		} else {
			$this->handler = $handler;
		}
	}

	/**
	 * @param string $controller
	 * @param string $action
	 * @return null|array
	 * @throws Exception
	 */
	private function getReflect(string $controller, string $action)
	{
		try {
			$reflect = new \ReflectionClass($controller);
			if (!$reflect->isInstantiable()) {
				throw new Exception($controller . ' Class is con\'t Instantiable.');
			}

			if (!empty($action) && !$reflect->hasMethod($action)) {
				throw new Exception('method ' . $action . ' not exists at ' . $controller . '.');
			}
			return [$reflect->newInstance(), $action];
		} catch (Exception $exception) {
			Logger::error($exception->getMessage(), 'router');
			return null;
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
		$mid->bindParams(\Beauty::$app->request);

		$this->runFilter();
		foreach ($this->middleware as $val) {
			$mid->set($val);
		}
		return $mid->exec($call);
	}

	/**
	 * @throws Exception
	 */
	private function runFilter()
	{
		if (empty($this->rules)) {
			return;
		}
		foreach ($this->rules as $rule) {
			if (!isset($rule['class'])) {
				$rule['class'] = Filter::class;
			}

			$object = \Beauty::createObject($rule);
			$object->handler();
		}
	}

	/**
	 * @param $options
	 * @return $this
	 */
	public function bindOptions($options)
	{
		if (is_object($options)) {
			$this->options = $options;
		} else {
			$options = array_filter($options);
			$last = $options[count($options) - 1];
			if (empty($last)) {
				return $this;
			}
			$this->options = $last;
		}
		return $this;
	}

	/**
	 * @param $middles
	 * @throws
	 */
	public function bindMiddleware(array $middles)
	{
		$_tmp = [];
		if (empty($middles)) {
			return;
		}
		foreach ($middles as $class) {
			if (empty($class)) {
				continue;
			}
			$_tmp[] = \Beauty::createObject($class);
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
