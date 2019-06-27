<?php


namespace Yoc\route;


use Exception;
use Yoc\base\Component;
use Yoc\exception\NotFindClassException;
use Yoc\exception\RequestException;

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

		if (strpos($path, '/') === false) {
			$path = '/' . $path;
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

		array_unshift($name, 'app\\controller');
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

		$options = array_column($this->groupTacks, 'options');
		if (!empty($options) && is_array($options)) {
			$options = array_filter($options);

			$last = $options[count($options) - 1];
			if (!empty($last)) {
				$node->bindOptions($last);
			}

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
	public function group(array $config, callable $callback)
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
	 * @throws
	 */
	public function findByRoute($url = '')
	{
		if (empty($url)) {
			$url = request()->getUri();
		}

		$explode = explode('/', $url);
		if (empty($explode)) {
			throw new NotFindClassException();
		}

		$_tmp = $this->transform($explode);
		/** @var \Yoc\route\Node $node */
		$node = $this->findByPath('/' . implode('/', $_tmp));
		if (!($node instanceof Node)) {
			throw new NotFindClassException();
		}

		if (request()->getMethod() == 'options') {
			return $node->execOptions();
		}

		if ($node->method != 'any' && $node->method != request()->getMethod()) {
			throw new \Exception('method mot allowed.', 403);
		}


		return $node->run($node->handler);
	}

	/**
	 * @param $explode
	 * @return array
	 * @throws NotFindClassException
	 */
	private function transform($explode)
	{
		$_tmp = [];
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

		return $_tmp;
	}


	public function loader()
	{
		$routes = glob(APP_PATH . '/routes/*');
		foreach ($routes as $val) {
			require_once $val;
		}
	}

	public function printTree()
	{
		print_r($this->nodes);
	}
}
