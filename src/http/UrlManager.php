<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/4/26 0026
 * Time: 14:57
 */

namespace Beauty\http;


use Beauty\base\BUrlManager;
use Beauty\exception\RequestException;
use Beauty\core\Xml;
use Beauty\core\JSON;
use Beauty\web\Controller;

class UrlManager extends BUrlManager
{

	/** @var \swoole_http_request */
	public $request;

	/** @var \swoole_http_response */
	public $response;

	public $namespace = 'app\\controller\\';

	public $route = [];

	/**
	 * @param \swoole_http_request|array $request
	 * @return Controller
	 * @throws RequestException
	 * @throws \ReflectionException|\Exception
	 */
	public function requestHandler($request)
	{
		$this->setRequest($request);
		$controller = $this->createController();

		if (!method_exists($controller, 'actions')) {
			return $controller;
		}

		$data = $controller->actions();
		if (empty($data) || !is_array($data)) {
			return $controller;
		}

		return $controller;
	}

	/**
	 * @param \swoole_http_request $request
	 * @param \swoole_http_response $response
	 * @throws \Exception
	 */
	private function setRequest($request)
	{
		$data = $request->rawContent();
		if (!Xml::isXml($data)) {
			$data = JSON::decode($request->rawContent());
		}

		$headers = $request->server;
		if (!empty($request->header)) {
			$headers = array_merge($headers, $request->header);
		}

		\Beauty::$app->set('request', [
			'class' => 'Beauty\http\Request',
			'startTime' => microtime(TRUE),
			'params' => new HttpParams($data, $request->get, $request->files),
			'headers' => new HttpHeaders($headers),
		]);

		if (!empty($request->post)) {
			$request = \Beauty::$app->request;
			$request->params->setPosts($request->isPost);
		}

	}
}
