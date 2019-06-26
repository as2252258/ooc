<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/11/8 0008
 * Time: 18:18
 */

namespace Yoc\server;

use Yoc\core\JSON;
use Yoc\core\Xml;
use Yoc\error\Logger;
use Yoc\event\Event;
use Yoc\exception\ExitException;
use Yoc\http\Response;
use Yoc\route\Router;
use Yoc\http\HttpParams;
use Yoc\http\HttpHeaders;
use Swoole\Coroutine\Http\Client;

class Request extends Base
{

	/**
	 * @param mixed ...$data
	 * @throws \Exception
	 */
	public function onHandler(...$data)
	{
		$this->server->addListener($data[0], $data[1], SWOOLE_TCP);

		$this->server->on('request', [$this, 'onRequest']);
	}

	/**
	 * @param \swoole_http_request $request
	 * @param \swoole_http_response $response
	 * @throws \Exception
	 */
	public function onRequest(\swoole_http_request $request, \swoole_http_response $response)
	{
		Event::on('AFTER_REQUEST', [$this, 'requestLog'], $request);

		try {

			$this->setRequestDi($request);
			$this->setResponseDi($response);

			/** @var Response $resp */
			$resp = \Yoc::$app->get('response');

			if ($request->server['request_uri'] == '/favicon.ico') {
				throw new \Exception('Page not exists.', 404);
			}

			$resp->send(router()->findByRoute());
		} catch (\Error | \Exception $exception) {
			$this->addError($exception->getMessage(), 'app');

			$code = $exception->getCode();
			if ($code == 0) $code = 500;

			$resp->send(JSON::to($code, $exception->getMessage(), [
				'file' => $exception->getFile(),
				'line' => $exception->getLine()
			]));
		}
	}

	/**
	 * @param $request
	 * @throws \Exception
	 */
	public function requestLog($request)
	{
		Logger::insert();
		
		$request = $request->data;

		$client = new Client('47.92.194.207', 9201);
		$client->setHeaders(['Content-Type' => 'application/json']);
		$time = array_merge($request->header, $request->server);
		$time['request_day'] = date('d', $time['request_time']);
		$time['request_month'] = date('m', $time['request_time']);
		$time['request_year'] = date('Y', $time['request_time']);
		$client->post('/request/json', json_encode($time));
		$client->close();
	}


	/**
	 * @param $response
	 * @return static
	 * @throws \Exception
	 */
	public function setResponseDi($response)
	{
		app()->set('response', [
			'class' => Response::class,
			'response' => $response
		]);
		return $this;
	}

	/**
	 * @param \swoole_http_request $request
	 * @return static
	 * @throws \Exception
	 */
	public function setRequestDi($request)
	{
		$data = $request->rawContent();
		if (!Xml::isXml($data)) {
			$data = JSON::decode($request->rawContent());
		}

		$headers = $request->server;
		if (!empty($request->header)) {
			$headers = array_merge($headers, $request->header);
		}

		app()->set('request', [
			'class' => 'Yoc\http\Request',
			'startTime' => microtime(TRUE),
			'params' => new HttpParams($data, $request->get, $request->files),
			'headers' => new HttpHeaders($headers),
		]);

		if (!empty($request->post)) {
			$req = \Yoc::$app->get('request');
			$req->params->setPosts($request->post);
		}
		return $this;
	}

}
