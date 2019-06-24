<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/11/8 0008
 * Time: 18:06
 */

namespace Yoc\server;

use Yoc\error\Logger;
use Yoc\event\Event;

class Task extends Base
{

	/**
	 * @param mixed ...$data
	 * @throws \Exception
	 */
	public function onHandler(...$data)
	{
		$this->server->on('task', [$this, 'onTask']);
		$this->server->on('finish', [$this, 'onFinish']);
	}


	/**
	 * @param \swoole_server $serv
	 * @param                $task_id
	 * @param                $from_id
	 * @param                $data
	 *
	 * @return mixed|void
	 * @throws \Exception
	 * 异步任务
	 */
	public function onTask(\swoole_server $serv, $task_id, $from_id, $data)
	{
		$time = microtime(TRUE);
		try {
			if (empty($data)) {
				return;
			}
			$serialize = unserialize($data);

			$finish = ['status' => 'success', 'info' => $serialize->handler()];
		} catch (\Exception $exception) {
			Logger::error($exception, 'task');
			$message = "info : " . $exception->getMessage() . " on line " . $exception->getLine() . " at file " . $exception->getFile();

			$finish = ['status' => 'error', 'info' => $message];
		}
		$finish = array_merge(['taskId' => $task_id, 'data' => [
			'class' => get_class($serialize)
		], 'runTime' => [
			'startTime' => $time,
			'runTime' => microtime(TRUE) - $time,
			'endTime' => microtime(TRUE),
		]], $finish);
		$serv->finish(json_encode($finish));
	}

	/**
	 * @param \swoole_server $server
	 * @param $task_id
	 * @param $data
	 * @throws \Exception
	 */
	public function onFinish(\swoole_server $server, $task_id, $data)
	{
		\Yoc::$app->redis->rPush('task_' . date('Y_m_d'), $data);


		Event::trigger('AFTER_TASK');
	}
}
