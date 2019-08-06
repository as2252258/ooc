<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2019-03-20
 * Time: 01:43
 */

namespace Beauty\server;


use Swoole\Http\Response;
use Swoole\WebSocket\Frame;
use Swoole\WebSocket\Server;

interface WebSocketInterface
{

	/**
	 * @param \swoole_websocket_server $server
	 * @param \swoole_websocket_frame $frame
	 * @return mixed
	 */
	public function onMessage(Server $server, Frame $frame);

	/**
	 * @param \swoole_http_request $request
	 * @param \swoole_http_response $response
	 * @return mixed
	 */
	public function onHandshake(\Swoole\Http\Request $request,Response $response);

}
