<?php


namespace Yoc\server;


use Yoc\base\Component;

class Listen extends Component
{

	public $listens = [];
	public $callback = [];

	/** @var Socket */
	private $server;

	/**
	 * 监听端口，回调函数注册
	 */
	public function listen()
	{
		$this->server = \Yoc::$app->socket;
		if ($this->server->isRun || empty($this->listens)) {
			return;
		}
		$socket = $this->server->getSocket();
		foreach ($this->listens as $val) {
			if (!isset($val['callback']) || empty($val['callback'])) {
				continue;
			}

			if (!is_callable($val['callback'], true)) {
				continue;
			}

			$socket->addListener($val['host'], $val['port'], $val['type']);

			$hash = md5($val['host'] . $val['port']);
			$this->callback[$hash] = $val['callback'];
		}
	}

	/**
	 * @param $address
	 * @param $port
	 * @param $data
	 * @return mixed|void
	 * @throws
	 * @uses $data = [
	 *      'from' => array ['address', 'port']
	 *      'message' => any ''
	 * ]
	 *
	 *
	 * @onWorkerStart
	 */
	public function message($address, $port, $data)
	{
		$hash = md5($address . $port);
		if (!isset($this->callback[$hash])) {
			return;
		}

		$callback = $this->callback[$hash];

		if ($callback instanceof \Closure) {
			return call_user_func($callback, $data);
		}

		if (class_exists($callback)) {
			if (!method_exists($callback, 'handler')) {
				throw new \Exception('类需注册handler执行函数');
			}

			$calss = \Yoc::createObject($callback);

			return $calss->handler($data);
		}

		throw new \Exception('需注册handler执行函数');
	}


	public function runBefore()
	{
		if (empty($this->callback)) {
			return;
		}

		foreach ($this->callback as $val) {
			if (!method_exists($val, 'before')) {
				continue;
			}

			call_user_func([$val, 'before']);
		}
	}

}
