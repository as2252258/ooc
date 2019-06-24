<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/11/8 0008
 * Time: 18:27
 */

namespace Yoc\server;


use Yoc\base\Component;

class Udp extends Base
{
	
	/** @var \swoole_websocket_server */
	public $server;
	
	public function onHandler(...$data)
	{
		$this->server->on('packet', [$this, 'onPacket']);
	}
	
	/**
	 * @param \swoole_server $serv
	 * @param $data
	 * @param $clientInfo
	 * udp回调
	 */
	public function onPacket(\swoole_server $serv, $data, $clientInfo)
	{
		list($fd, $reactor_id) = $this->unpack($clientInfo['address']);
		
		try {
			if (!($json = json_decode($data, TRUE))) {
				$serv->sendto($clientInfo['address'], $clientInfo['port'], json_encode(
					['code' => '400', 'message' => 'Con\'t resolve json data.']
				));
			} else if (!isset($json['cmd'])) {
				$serv->sendto($clientInfo['address'], $clientInfo['port'], json_encode(
					['code' => '404', 'message' => 'Con\'t resolve route.']
				));
			} else {
				$result = \Yoc::command($json['cmd'], $json['content'] ?? NULL);
				
				$serv->sendto($clientInfo['address'], $clientInfo['port'], $result);
			}
		} catch (\Exception $exception) {
			$serv->sendto($clientInfo['address'], $clientInfo['port'], json_encode(
				['code' => $exception->getCode(), 'message' => $exception->getMessage()]
			));
		}
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
