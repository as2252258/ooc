<?php


namespace Yoc\route;


use Exception;
use Yoc\base\Component;
use Yoc\exception\NotFindClassException;

class Router extends Component
{
	/** @var Node[] $nodes */
	public $nodes = [];
	public $groupTacks = [];
	public $suffixes = '';

	/**
	 * @param $path
	 * @param $handler
	 * @param string $method
	 * @return mixed|Node|null
	 * @throws
	 */
	public function addRoute($path, $handler, $method = 'any')
	{
		$prefix = $this->addPrefix();
		if (!empty($prefix) && $path != rtrim($prefix, '/')) {
			$path = $prefix . $path;
		}

		list($first, $explode) = $this->split($path);

		$parent = $this->nodes[$first] ?? null;
		if (empty($parent)) {
			$parent = $this->NodeInstance($first, null, 0, $method);

			$this->nodes[$first] = $parent;
		}

		if (empty($explode)) {
			$explode = [''];
		}

		$a = 0;
		foreach ($explode as $value) {
			if (empty($value)) {
				$value = '/';
			}

			++$a;
			if ($search = $parent->findNode($value)) {
				$parent = $search;
				continue;
			}
			$node = $this->NodeInstance($value, $handler, $a, $method);
			$parent = $parent->addChild($node, $value);
		}

		return $node ?? $parent;
	}

	/**
	 * @param $route
	 * @param $handler
	 * @return mixed|Node|null
	 * @throws
	 */
	public function post($route, $handler)
	{
		return $this->addRoute($route, $handler, 'post');
	}

	/**
	 * @param $route
	 * @param $handler
	 * @return mixed|Node|null
	 * @throws
	 */
	public function get($route, $handler)
	{
		return $this->addRoute($route, $handler, 'get');
	}

	/**
	 * @param $route
	 * @param $handler
	 * @return mixed|Node|null
	 * @throws
	 */
	public function options($route, $handler)
	{
		return $this->addRoute($route, $handler, 'options');
	}

	/**
	 * @param $route
	 * @param $handler
	 * @return mixed|Node|null
	 * @throws
	 */
	public function any($route, $handler)
	{
		return $this->addRoute($route, $handler, 'any');
	}

	/**
	 * @param $route
	 * @param $handler
	 * @return mixed|Node|null
	 * @throws
	 */
	public function delete($route, $handler)
	{
		return $this->addRoute($route, $handler, 'delete');
	}

	/**
	 * @param $route
	 * @param $handler
	 * @return mixed|Node|null
	 * @throws
	 */
	public function put($route, $handler)
	{
		return $this->addRoute($route, $handler, 'put');
	}

	/**
	 * @param $value
	 * @param $handler
	 * @param $index
	 * @param $method
	 * @return Node
	 * @throws
	 */
	public function NodeInstance($value, $handler, $index = 0, $method = 'get')
	{
		$node = new Node();
		$node->childs = [];
		$node->path = $value;
		$node->index = $index;
		$node->method = $method;
		$node->handler = $handler;

		$name = array_column($this->groupTacks, 'namespace');
		if (!empty($name) && $name = array_filter($name)) {
			$node->namespace = $name;
		}

		$name = array_column($this->groupTacks, 'middleware');
		if (!empty($name) && $name = array_filter($name)) {
			$node->middleware = $name;
		}

		if (is_string($handler) && strpos($handler, '@') !== false) {
			list($controller, $action) = explode('@', $handler);
			if (!empty($node->namespace)) {
				$controller = implode('\\', $node->namespace) . '\\' . $controller;
			}

			$reflect = new \ReflectionClass($controller);
			if (!$reflect->isInstantiable()) {
				throw new Exception('Controller Class is con\'t Instantiable.');
			}

			if (!$reflect->hasMethod($action)) {
				throw new Exception('method not exists at Controller.');
			}

			$node->handler = [$reflect->newInstance(), $action];
		} else if ($handler instanceof \Closure) {
			$node->handler = $handler;
		} else if ($handler != null && !is_callable($handler, true)) {
			throw new Exception('Controller is con\'t exec.');
		}

		$rules = array_column($this->groupTacks, 'filter');
		$rules = array_shift($rules);
		if (!empty($rules) && is_array($rules)) {
			$node->filter($rules);
		}

		return $node;
	}

	/**
	 * @param $config
	 * @param callable $callback
	 * 路由分组
	 */
	public function addGroup(array $config, callable $callback)
	{
		$this->groupTacks[] = $config;

		call_user_func($callback, $this);

		array_pop($this->groupTacks);
	}

	/**
	 * @return string
	 */
	public function addPrefix()
	{
		$prefix = array_column($this->groupTacks, 'prefix');

		$prefix = array_filter($prefix);

		if (empty($prefix)) {
			return '';
		}

		return '/' . implode('/', $prefix) . '/';
	}

	/**
	 * @param $path
	 * @return mixed|Node|null
	 * 查找指定路由
	 */
	public function findByPath($path)
	{
		list($first, $explode) = $this->split($path);

		$parent = $this->nodes[$first] ?? null;
		if (empty($explode) || empty($parent)) {
			return null;
		}

		foreach ($explode as $value) {
			if (empty($value)) $value = '/';
			if (!($parent = $parent->findNode($value))) {
				break;
			}
		}

		return $parent;
	}

	/**
	 * @param $path
	 * @return array
	 */
	public function split($path)
	{
		$explode = explode('/', $path);

		$first = array_shift($explode);
		if (empty($first)) {
			$first = '/';
		}

		if (empty($explode)) {
			$explode = [''];
		}

		return [$first, $explode];
	}

	/**
	 * @param $response
	 * @param $next
	 * @return mixed
	 */
	public function beforeSend($response, $next)
	{
		return $next($response);
	}

	/**
	 * @param string $url
	 * @return mixed
	 * @throws NotFindClassException
	 */
	public function findByRoute($url = '')
	{
		$_tmp = [];
		if (empty($url)) {
			$url = request()->getUri();
		}
		$explode = explode('/', $url);
		if (empty($explode)) {
			throw new NotFindClassException();
		}
		foreach ($explode as $value) {
			if (empty($value) && !is_numeric($value)) {
				continue;
			}
			if (is_numeric($value)) {
				$value = '<(\w+)?:(.+)?>';
			}
			$_tmp[] = $value;
		}

		if (empty($_tmp)) {
			throw new NotFindClassException();
		}

		/** @var \Yoc\route\Node $node */
		$node = $this->findByPath('/' . implode('/', $_tmp));


		if (!($node instanceof Node)) {
			throw new NotFindClassException();
		}

		return $node->run($node->handler);
	}


	public function loader()
	{
		$routes = glob(APP_PATH . '/routes/*');
		foreach ($routes as $val) {
			require_once $val;
		}

		$this->printTree();
	}

	public function printTree()
	{
		print_r($this->nodes);
	}
}
