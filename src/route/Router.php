<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2019-03-20
 * Time: 02:15
 */

namespace Yoc\route;


use Exception;
use \ReflectionClass;
use Yoc\base\Component;
use Yoc\core\Xml;
use Yoc\core\JSON;
use Yoc\exception\NotFindClassException;
use Yoc\exception\RequestException;
use Yoc\http\HttpParams;
use Yoc\http\HttpHeaders;
use Zxing\NotFoundException;

class Router extends Component
{
	private $routes = [];
	private $options = [];
	private $groupStack = [];
	private $prefix = [];
	private $middleware = [];

	/**
	 * @param $uri
	 * @param $method
	 * @param $callback
	 * @return Route
	 * @throws
	 */
	private function addRoute($uri, $method, $callback)
	{
		$method = strtolower($method);

		$route = new Route($uri, $method, $callback);
		if ($this->middleware != null) {
			$route->clearMiddleware()->middlewares($this->middleware);
		}

		$this->addNamespace($route);

		if ($method == 'options') {
			$this->options[$uri] = $route;
		} else {
			$this->routes[$uri] = $route;
		}

		return $route->resolve();
	}

	/**
	 * @param Route $route
	 */
	private function addNamespace(Route $route)
	{
		$_tmp = [];
		foreach ($this->groupStack as $val) {
			if (empty($val['namespace'])) {
				continue;
			}
			$_tmp[] = $val['namespace'];
		}

		if (!empty($_tmp)) {
			$route->namespace(implode('\\\\', $_tmp));
		}
	}

	/**
	 * @param $uri
	 * @param $callback
	 * @return Route
	 */
	public function any($uri, $callback)
	{
		if (!empty($this->prefix)) {
			$uri = implode('/', $this->prefix) . '/' . $uri;
		}

		return $this->addRoute($uri, '*', $callback);
	}

	/**
	 * @param $uri
	 * @param $callback
	 * @return Route
	 */
	public function get($uri, $callback)
	{
		if (!empty($this->prefix)) {
			$uri = implode('/', $this->prefix) . '/' . $uri;
		}

		return $this->addRoute($uri, 'GET', $callback);
	}

	/**
	 * @param $uri
	 * @param $callback
	 * @return Route
	 */
	public function options($uri, $callback)
	{
		if (!empty($this->prefix)) {
			$uri = implode('/', $this->prefix) . '/' . $uri;
		}

		return $this->addRoute($uri, 'OPTIONS', $callback);
	}

	/**
	 * @return int|mixed|string
	 * @throws
	 */
	public function resolve()
	{
		$url = request()->getUri();

		/** @var Route $all */
		if (request()->isOption) {
			$all = $this->options['*'] ?? $this->options[$url] ?? '';
		} else {
			$all = $this->routes[$url] ?? '';
		}

		if (!($all instanceof Route)) {
			throw new Exception('Not Found Page.', 404);
		}
		if ($all->getMethod() == '*') {
			return $all->run($all->getCallback());
		}
		if ($all->getMethod() != request()->getMethod()) {
			throw new Exception('Request method allow.', 405);
		}
		return $all->run($all->getCallback());
	}

	/**
	 * @param $uri
	 * @param $callback
	 * @return Route
	 */
	public function post($uri, $callback)
	{
		if (!empty($this->prefix)) {
			$uri = implode('/', $this->prefix) . '/' . $uri;
		}

		return $this->addRoute($uri, 'POST', $callback);
	}

	/**
	 * @param array $config
	 * @param \Closure $callback
	 * @return $this
	 */
	public function group(array $config, \Closure $callback)
	{
		$this->groupStack[] = $config;

		$length = count($this->prefix);
		if (isset($config['prefix'])) {
			$this->prefix[$length - 1] = $config['prefix'];
		}

		$this->registerMiddleware($config);

		call_user_func($callback, $this);

		array_pop($this->prefix);
		array_pop($this->groupStack);
		array_pop($this->middleware);

		return $this;
	}

	/**
	 * @param $middleware
	 * @return $this|\Closure
	 */
	private function registerMiddleware($middleware)
	{
		if (!isset($middleware['middleware'])) {
			return $this->middleware[] = null;
		}

		$middleware = $middleware['middleware'];
		if (is_callable($middleware, true)) {
			return $this->middleware[] = $middleware;
		}

		if (is_array($middleware) && !empty($middleware)) {
			foreach ($middleware as $val) {
				array_push($this->middleware, $val);
			}

			return $this;
		}

		$this->middleware[] = $middleware;

		return $this;
	}

	/**
	 * 加载路由配置
	 */
	public function loader()
	{
		foreach (glob(APP_PATH . '/routes/*') as $val) {
			require_once $val;
		}
	}
}
