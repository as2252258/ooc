<?php


namespace Yoc\socket;


use Swoole\Server;
use Yoc\base\Component;

abstract class BSocket extends Component
{

	public $host = '';

	public $port = '';

	protected $config = [];

	/** @var Server $server */
	protected $server;

	abstract public function start();

	/**
	 * @param $key
	 * @param $value
	 * 设置配置项。 仅监听开始前生效
	 */
	public function setConfig($key, $value)
	{
		$this->config[$key] = $value;
	}


	/**
	 * @param $key
	 * @return mixed|null
	 * 获取配置项
	 */
	public function getConfig($key)
	{
		return $this->config[$key] ?? null;
	}


	public function workerListen()
	{
		$this->server->on('WorkerStart', [$this, 'onWorkerStart']);
		$this->server->on('WorkerStop', [$this, 'onWorkerStop']);
		$this->server->on('WorkerExit', [$this, 'onWorkerExit']);
	}

	public function onWorkerStart(Server $server, $workerId)
	{
		$classname = $this->getClass() . ': Worker';
		if ($workerId >= $server->setting['worker_num']) {
			$classname = $this->getClass() . ': Task';
		}

		if (function_exists('swoole_set_process_name')) {
			swoole_set_process_name($classname);
		}
	}


	public function getClass()
	{
		return get_called_class();
	}
}
