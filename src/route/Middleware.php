<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2019-03-20
 * Time: 02:17
 */

namespace Beauty\route;


use Beauty\web\Controller;

class Middleware
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
		$last = function ($passable) use ($call) {
			if ($call instanceof \Closure) {
				return $call($passable);
			}

			if (!is_array($call) || !($call[0] instanceof Controller)) {
				return $call($passable);
			}

			return call_user_func($call, $passable);
		};
		$data = array_reduce(array_reverse($this->middlewares), $this->core(), $last);
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
				if ($pipe instanceof IMiddleware) {
					return $pipe->handler($passable, $stack);
				} else {
					return $pipe($passable, $stack);
				}
			};
		};
	}

}
