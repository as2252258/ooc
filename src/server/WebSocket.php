<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/11/8 0008
 * Time: 18:15
 */

namespace Beauty\server;


use Beauty\base\Component;
use Beauty\core\JSON;
use Beauty\exception\NotFindClassException;
use Swoole\Http\Response;
use Swoole\WebSocket\Frame;
use Swoole\WebSocket\Server;

class WebSocket extends Component
{

	public $namespace = 'app\\sockets\\';

	/**
	 * @param Server $server
	 * @param Frame $frame
	 * @return mixed
	 * @throws NotFindClassException|\Exception
	 * @throws \ReflectionException
	 */
	public function onMessage(Server $server, Frame $frame)
	{
		$json = json_decode($frame->data, true);

		\response()->setIsWebSocket($frame->fd);
		if (is_null($json) || !isset($json['route'])) {
			$message = JSON::to(404, '错误的地址!');

			return \response()->send($message);
		}
		/** @var \ReflectionClass $class */
		list($class, $action) = $this->resolveUrl($json['route']);
		if (isset($json['body']) && !empty($json['body'])) {
			Input()->setPosts($json['body']);
		}

		$controller = $class->newInstance();
		$response = $controller->{$action}(...$json['body']);
		return \response()->send($response);
	}

	/**
	 * @param $route
	 * @return array
	 * @throws NotFindClassException
	 * @throws \ReflectionException
	 *
	 * 解析URL
	 */
	private function resolveUrl($route)
	{
		if (strpos($route, '-') !== false) {
			$explode = $this->split($route);
		} else {
			$explode = explode('/', $route);
		}
		$explode = array_filter($explode);

		if (count($explode) < 2) {
			throw new NotFindClassException($route);
		}

		$action = end($explode);
		$explode[count($explode) - 1] = lcfirst($explode[count($explode) - 1]);
		$class = $this->namespace . implode('\\', $explode) . 'Controller';

		if (!class_exists($class)) {
			throw new NotFindClassException($route);
		}

		$class = new \ReflectionClass($class);
		if (!$class->isInstantiable()) {
			throw new NotFindClassException($route);
		}

		if (!$class->hasMethod($action)) {
			throw new NotFindClassException($action);
		}

		return [$class, $action];
	}

	/**
	 * @param $route
	 * @return array
	 */
	private function split($route)
	{
		$explode = [];
		foreach (explode('-', $route) as $key => $value) {
			if ($key != 0) {
				$explode[] = ucfirst($value);
			} else {
				$explode[] = $value;
			}
		}
		if (empty($explode)) {
			return [];
		}
		$explode = implode('', $explode);
		return explode('/', $explode);
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
