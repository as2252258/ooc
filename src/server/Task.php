<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/11/8 0008
 * Time: 18:06
 */

namespace Yoc\server;

use Swoole\Server;
use Yoc\error\Logger;
use Yoc\event\Event;
use Yoc\task\InterfaceTask;

class Task
{

	/**
	 * @param Server $server
	 * @param int $task_id
	 * @param int $from_id
	 * @param string $data
	 *
	 * @return mixed|void
	 * @throws \Exception
	 * 异步任务
	 */
	public function onTask(Server $server, $task_id, $from_id, $data)
	{
		$time = microtime(TRUE);
		if (empty($data)) {
			return;
		}
		$serialize = unserialize($data);
		try {
			if (empty($serialize) || !($serialize instanceof InterfaceTask)) {
				return;
			}
			$finish = ['status' => 'success', 'info' => $serialize->handler()];
		} catch (\Exception $exception) {
			Logger::error($exception, 'task');
			$message = "info : " . $exception->getMessage() . " on line " . $exception->getLine() . " at file " . $exception->getFile();

			$finish = ['status' => 'error', 'info' => $message];
		}

		$runTime = [
			'startTime' => $time,
			'runTime' => microtime(TRUE) - $time,
			'endTime' => microtime(TRUE),
		];

		$class = get_class($serialize);

		$finish = array_merge(['runTime' => $runTime, 'class' => $class], $finish);
		$server->finish(json_encode($finish));
	}

	/**
	 * @param Server $server
	 * @param $task_id
	 * @param $data
	 * @throws \Exception
	 */
	public function onFinish(Server $server, $task_id, $data)
	{
		$data = json_decode($data, true);
		$data['work_id'] = $task_id;

		redis()->rPush('task_' . date('Y_m_d'), json_encode($data));
		Event::trigger('AFTER_TASK');
	}
}
