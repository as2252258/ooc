<?php


namespace Yoc\tcp;


use Swoole\Server;
use Yoc\base\Component;

abstract class Socket extends Component
{

	protected $type = 'Swoole';

	/** @var Server $server */
	protected $server;

	/**
	 * @param Server $server
	 * @param $workerId
	 */
	protected function onWorkerStart(Server $server, $workerId)
	{
		$classname = $this->type . ': Worker';
		if ($workerId >= $server->setting['worker_num']) {
			$classname = $this->type . ': Task';
		}

		if (function_exists('swoole_set_process_name')) {
			swoole_set_process_name($classname);
		}
	}


	/**
	 * @param Server $server
	 * @param int $worker_id
	 * @param int $worker_pid
	 * @param int $exit_code
	 * @param int $signal
	 */
	protected function onWorkerError(Server $server, int $worker_id, int $worker_pid, int $exit_code, int $signal)
	{

	}


	/**
	 * @param Server $server
	 * @param int $worker_id
	 */
	protected function onWorkerExit(Server $server, int $worker_id)
	{

	}

	/**
	 * @param Server $server
	 * @param int $worker_id
	 */
	protected function onWorkerStop(Server $server, int $worker_id)
	{

	}

}
