<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/11/8 0008
 * Time: 19:18
 */

namespace Yoc\server;


use Swoole\Server;

class Receive extends Base
{
	
	public function onHandler(...$value)
	{
		$this->server->on('receive', [$this, 'onReceive']);
	}
	
	
	public function onReceive(Server $server, int $fd, int $reactor_id, string $data)
	{
	
	}
}
