<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/11/8 0008
 * Time: 18:37
 */

namespace Yoc\server;


use Swoole\Server;

class PipeMessage
{
	
	/**
	 * @param Server $server
	 * @param int $src_worker_id
	 * @param $message
	 */
	public function onCallback(Server $server, int $src_worker_id, $message)
	{
		$redis = \Yoc::$app->redis;
		if ($redis->sCard('debug_list') < 1) {
			return;
		}
		$remove = [];
		foreach ($redis->sMembers('debug_list') as $val) {
			if (!$server->exist($val)) {
				$remove[] = $val;
			} else {
				if (is_array($message)) {
					$message = print_r($message, TRUE);
				} else if (is_object($message)) {
					$message = print_r(get_object_vars($message), TRUE);
				}
				$server->push($val, $message);
			}
		}
		if (!empty($remove)) {
			$redis->sRem('debug_list', ...$remove);
		}
	}
}
