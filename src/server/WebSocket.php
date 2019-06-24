<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/11/8 0008
 * Time: 18:15
 */

namespace Yoc\server;


abstract class WebSocket extends Base
{
	
	/**
	 * @param mixed ...$data
	 */
	public function onHandler(...$data)
	{
		$this->server->on('handshake', [$this, 'onHandshake']);
		$this->server->on('message', [$this, 'onMessage']);
	}
	
	/**
	 * @param \swoole_websocket_server $server
	 * @param \swoole_websocket_frame $frame
	 * @throws
	 * @return mixed|void
	 */
	public function onMessage(\swoole_websocket_server $server, \swoole_websocket_frame $frame)
	{
		if ($frame->opcode == 0x08) {
			echo "Close frame received: \n";
		}
	}
	
	/**
	 * @param \swoole_http_request $request
	 * @param \swoole_http_response $response
	 *
	 * @return bool
	 * @throws \Exception
	 */
	public function onHandshake(\swoole_http_request $request, \swoole_http_response $response)
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

		$this->server->protect($request->fd, TRUE);
		return TRUE;
	}
}
