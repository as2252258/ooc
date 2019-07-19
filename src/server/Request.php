<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/11/8 0008
 * Time: 18:18
 */

namespace Yoc\server;

use Yoc\base\Component;
use Yoc\core\JSON;
use Yoc\core\Xml;
use Yoc\http\Response;
use Yoc\http\HttpParams;
use Yoc\http\HttpHeaders;

class Request extends Component
{

	/**
	 * @param \Swoole\Http\Request $request
	 * @param \Swoole\Http\Response $response
	 * @throws \Exception
	 */
	public function onRequest(\Swoole\Http\Request $request, \Swoole\Http\Response $response)
	{
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
			$message = $exception->getMessage();
			$this->addError($message, 'app');

			$code = $exception->getCode();
			if ($code == 0) $code = 500;

			$trance = array_slice($exception->getTrace(), 0, 10);

			$resp->send(JSON::to($code, $message, array_values($trance)));
		}
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
