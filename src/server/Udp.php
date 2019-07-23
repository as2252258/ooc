<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/11/8 0008
 * Time: 18:27
 */

namespace Beauty\server;


use Swoole\Server;
use Beauty\base\Component;

class Udp
{

	/**
	 * @param Server $server
	 * @param $data
	 * @param $clientInfo
	 * udp回调
	 */
	public function onPacket(Server $server, $data, $clientInfo)
	{

	}
	
	/**
	 * @param $addr
	 * @return array
	 */
	private function unpack($addr)
	{
		$fd = unpack('L', pack('N', ip2long($addr['address'])))[1];
		$reactor_id = ($addr['server_socket'] << 16) + $addr['port'];
		return [$fd, $reactor_id];
	}
	
}
