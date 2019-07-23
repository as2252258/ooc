<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2019-03-20
 * Time: 01:43
 */

namespace Beauty\server;


interface WebSocketInterface
{

	/**
	 * @param \swoole_websocket_server $server
	 * @param \swoole_websocket_frame $frame
	 * @return mixed
	 */
	public function onMessage(\swoole_websocket_server $server, \swoole_websocket_frame $frame);

	/**
	 * @param \swoole_http_request $request
	 * @param \swoole_http_response $response
	 * @return mixed
	 */
	public function onHandshake(\swoole_http_request $request, \swoole_http_response $response);

}
