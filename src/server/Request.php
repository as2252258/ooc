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
			Request::setRequestDi($request);
			Request::setResponseDi($response);

			/** @var Response $resp */
			if ($request->server['request_uri'] == '/favicon.ico') {
				$response->status(404);
				$response->end();
				return;
			}

			\response()->send(router()->findByRoute());
		} catch (\Error | \Exception $exception) {
			$message = $exception->getMessage();
			$this->addError($message, 'app');

			$code = $exception->getCode();
			if ($code == 0) $code = 500;

			$trance = array_slice($exception->getTrace(), 0, 10);

			\response()->send(JSON::to($code, $message, array_values($trance)));
		}
	}

	/**
	 * @param $response
	 * @throws \Exception
	 */
	public static function setResponseDi($response)
	{
		\response()->setResponse($response);
	}

	/**
	 * @param \swoole_http_request $request
	 * @throws \Exception
	 */
	public static function setRequestDi($request)
	{
		$data = $request->rawContent();
		if (!Xml::isXml($data)) {
			$data = JSON::decode($request->rawContent());
		}

		$headers = $request->server;
		if (!empty($request->header)) {
			$headers = array_merge($headers, $request->header);
		}

		/** @var \Yoc\http\Request $req */
		$req = \Yoc::$app->get('request');
		$req->startTime = microtime(true);
		$params = new HttpParams($data, $request->get, $request->files);
		if (!empty($request->post)) {
			$params->setPosts($request->post);
		}
		$req->params = $params;
		$req->headers = new HttpHeaders($headers);
	}

}
