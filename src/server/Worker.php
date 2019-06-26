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
		if ($workeer_id < $socket->config['worker_num']) {
			/** @var DbPool $dbPool */
			try {
//				$app = \Yoc::$app;
//				$app->get('router')->loader();
			} catch (\Exception $exception) {
				$this->addError($exception);
			}
			if (function_exists('swoole_set_process_name')) {
				swoole_set_process_name('PHP_' . \Yoc::$app->id . ': worker: No.' . $workeer_id);
			}
		} else {
			if (function_exists('swoole_set_process_name')) {
				swoole_set_process_name('PHP_' . \Yoc::$app->id . ': task: No.' . $workeer_id);
			}
		}
	}
}
