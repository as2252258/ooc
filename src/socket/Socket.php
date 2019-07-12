<?php


namespace Yoc\socket;


use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\WebSocket\Frame;
use Swoole\WebSocket\Server;

class Socket extends BSocket
{

	public function start()
	{
		$this->server = new Server($this->host, $this->port);
		$this->server->set($this->config);

		$this->server->on('handshake', [$this, 'onHandshake']);
		$this->server->on('message', [$this, 'onMessage']);
		$this->server->on('close', [$this, 'onClose']);

		$port = intval($this->port) + 1;
		$this->server->addlistener($this->host, $port, SWOOLE_TCP);
		$this->server->on('');

		$this->workerListen();

		$this->server->start();
	}

	public function onMessage(Server $server, Frame $frame)
	{

	}


	public function onClose(Server $server, int $fd)
	{

	}

	public function onHandshake(Request $request, Response $response)
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
