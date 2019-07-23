<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/11/8 0008
 * Time: 18:15
 */

namespace Beauty\server;


use Swoole\Http\Response;
use Swoole\WebSocket\Frame;
use Swoole\WebSocket\Server;

class WebSocket
{
	/**
	 * @param Server $server
	 * @param Frame $frame
	 */
	public function onMessage(Server $server, Frame $frame)
	{
		if ($frame->opcode == 0x08) {
			echo "Close frame received: \n";
		}
		$server->send($frame->fd, 'ok');
	}

	/**
	 * @param \Swoole\Http\Request $request
	 * @param Response $response
	 * @return bool|string
	 */
	public function onHandshake(\Swoole\Http\Request $request, Response $response)
	{
		/** @var \swoole_websocket_server $server */
		$secWebSocketKey = $request->header['sec-websocket-key'];
		$patten = '#^[+/0-9A-Za-z]{21}[AQgw]==$#';
		if (0 === preg_match($patten, $secWebSocketKey) || 16 !== strlen(base64_decode($secWebSocketKey))) {
			return 'Auth Token error';
		}
		$key = base64_encode(sha1(
			$request->header['sec-websocket-key'] . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11',
			TRUE
		));
		$headers = [
			'Upgrade' => 'websocket',
			'Connection' => 'Upgrade',
			'Sec-websocket-Accept' => $key,
			'Sec-websocket-Version' => '13',
		];
		if (isset($request->header['sec-websocket-protocol'])) {
			$headers['Sec-websocket-Protocol'] = $request->header['sec-websocket-protocol'];
		}
		foreach ($headers as $key => $val) {
			$response->header($key, $val);
		}
		return TRUE;
	}

	public function onClose(Server $server, int $fd)
	{

	}
}
