<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/11/8 0008
 * Time: 18:37
 */

namespace Yoc\server;


class PipeMessage extends Base
{
	
	/**
	 * @param mixed ...$value
	 */
	public function onHandler(...$value)
	{
		$this->server->on('pipeMessage', [$this, 'onCallback']);
	}
	
	/**
	 * @param \swoole_server $server
	 * @param int $src_worker_id
	 * @param $message
	 */
	public function onCallback(\swoole_server $server, int $src_worker_id, $message)
	{
		$redis = \Yoc::$app->redis;
		$socket = $this->server;
		if ($redis->sCard('debug_list') < 1) {
			return;
		}
		$remove = [];
		foreach ($redis->sMembers('debug_list') as $val) {
			if (!$socket->exist($val)) {
				$remove[] = $val;
			} else {
				if (is_array($message)) {
					$message = print_r($message, TRUE);
				} else if (is_object($message)) {
					$message = print_r(get_object_vars($message), TRUE);
				}
				$socket->push($val, $message);
			}
		}
		if (!empty($remove)) {
			$redis->sRem('debug_list', ...$remove);
		}
	}
}