<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/11/8 0008
 * Time: 18:02
 */

namespace Yoc\server;


use Yoc\base\Component;
use Yoc\db\DbPool;
use Yoc\error\Logger;
use Yoc\route\Router;

class Worker extends Base
{

	public function onHandler(...$server)
	{
		$this->server->on('workerStart', [$this, 'onWorkerStart']);
		$this->server->on('workerError', [$this, 'onWorkerError']);
		$this->server->on('workerStop', [$this, 'onWorkerStop']);
		$this->server->on('workerExit', [$this, 'onWorkerExit']);
	}

	public function onWorkerError(\swoole_server $server, int $worker_id)
	{
		$redis = \Yoc::$app->redis;
		$redis->hIncrBy('workerStatus', 'onWorkerError', 1);
		\Yoc::trance('The server error. at No.' . $worker_id);
	}

	public function onWorkerStop(\swoole_server $server, int $worker_id)
	{
		$redis = \Yoc::$app->redis;
		$redis->hIncrBy('workerStatus', 'onWorkerStop', 1);
		\Yoc::trance('The server stop. at No.' . $worker_id);
	}

	/**
	 * @param \swoole_server $server
	 * @param int $worker_id
	 */
	public function onWorkerExit(\swoole_server $server, int $worker_id)
	{
		$redis = \Yoc::$app->redis;
		$redis->hIncrBy('workerStatus', 'onWorkerExit', 1);
		\Yoc::trance('The server exit. at No.' . $worker_id);
	}

	/**
	 * @param \swoole_server $request
	 * @param                $workeer_id
	 *
	 * @return mixed|void
	 * @throws \Exception
	 */
	public function onWorkerStart(\swoole_server $request, $workeer_id)
	{
		$socket = \Yoc::$app->socket;

		/** @var DbPool $dbPool */
		try {
			\router()->loader();
			$worker_name = ': task: No.' . $workeer_id;
			if ($workeer_id < $socket->config['worker_num']) {
				$worker_name = ': worker: No.' . $workeer_id;
			}

			$worker_name = 'PHP_' . \Yoc::$app->id . $worker_name;
			if (function_exists('swoole_set_process_name')) {
				swoole_set_process_name($worker_name);
			}
		} catch (\Exception $exception) {
			echo 'Error: ' . $exception->getMessage() . PHP_EOL;
			$this->addError($exception);
		}
		Logger::insert();
	}
}
