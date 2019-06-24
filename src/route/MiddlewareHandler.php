<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2019-03-20
 * Time: 02:17
 */

namespace Yoc\route;


use app\controller\SiteController;
use app\middleware\handlers\MiddlewareTest;
use Yoc\core\JSON;
use Yoc\http\HttpFilter;
use Yoc\web\Controller;

class MiddlewareHandler
{
	private $middlewares = [];
	private $param = [];

	/**
	 * @param $call
	 * @return $this
	 */
	public function set($call)
	{
		$this->middlewares[] = $call;
		return $this;
	}

	/**
	 * @param $data
	 * @return $this
	 */
	public function bindParams($data)
	{
		$this->param = $data;
		return $this;
	}

	/**
	 * @param $call
	 * @return mixed
	 */
	public function exec($call)
	{
		$laster = function ($passable) use ($call) {
			if ($call instanceof \Closure) {
				return $call($passable);
			}
			if (!is_array($call) || !($call[0] instanceof Controller)) {
				return $call($passable);
			}

			/** @var Controller $controller */
			list($controller, $action) = $call;
			if (empty($controller->actions())) {
				return $controller->{$action}($passable);
			}


			$actionRule = $controller->actions();
			if (array_key_exists('*', $actionRule)) {
				if (!isset($actionRule['*']['class'])) {
					$actionRule['*']['class'] = HttpFilter::class;
				}

				/** @var HttpFilter $filter */
				$filter = \Yoc::createObject($actionRule['*']);
				$filter->handler();
			}

			if (array_key_exists(request()->getUri(), $actionRule)) {

				$uri = request()->getUri();
				if (!isset($actionRule[$uri]['class'])) {
					$actionRule[$uri]['class'] = HttpFilter::class;
				}

				/** @var HttpFilter $filter */
				$filter = \Yoc::createObject($actionRule[$uri]);
				$filter->handler();
			}

			return $controller->{$action}($passable);
		};
		$data = array_reduce(array_reverse($this->middlewares), $this->core(), $laster);
		$this->middlewares = [];
		return $data($this->param);
	}

	/**
	 * @return \Closure
	 */
	public function core()
	{
		return function ($stack, $pipe) {
			return function ($passable) use ($stack, $pipe) {
				if ($pipe instanceof MiddlewareTest) {
					return $pipe->handler($passable, $stack);
				} else {
					return $pipe($passable, $stack);
				}
			};
		};
	}

}
