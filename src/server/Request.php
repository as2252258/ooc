<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/11/8 0008
 * Time: 18:18
 */

namespace Beauty\server;

use Beauty\base\Component;
use Beauty\core\JSON;
use Beauty\core\Xml;
use Beauty\exception\Exception;
use Beauty\http\Response;
use Beauty\http\HttpParams;
use Beauty\http\HttpHeaders;

class Request extends Component
{

	/**
	 * @param \Swoole\Http\Request $request
	 * @param \Swoole\Http\Response $response
	 * @return mixed|void
	 * @throws \Exception
	 */
	public function onRequest(\Swoole\Http\Request $request, \Swoole\Http\Response $response)
	{
		/** @var Response $resp */
		\response()->setResponse($response);
		if ($request->server['request_uri'] == '/favicon.ico') {
			return \response()->sendNotFind();
		}
		try {
			Request::setRequestDi($request);

			$data = router()->findByRoute();
		} catch (\Error | \Exception $exception) {
			$data = $this->logger($exception);
		}

		return \response()->send($data);
	}

	/**
	 * @param \Exception $exception
	 * @return mixed
	 * @throws \Exception
	 */
	public function logger($exception)
	{
		$message = $exception->getMessage();
		$this->addError($message, 'app');

		$code = $exception->getCode();
		if ($code == 0) $code = 500;

		$trance = array_slice($exception->getTrace(), 0, 10);

		return JSON::to($code, $message, array_values($trance));
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

		/** @var \Beauty\http\Request $req */
		$req = \Beauty::$app->get('request');
		$req->startTime = microtime(true);
		$params = new HttpParams($data, $request->get, $request->files);
		if (!empty($request->post)) {
			$params->setPosts($request->post);
		}
		$req->params = $params;
		$req->headers = new HttpHeaders($headers);
	}

}
