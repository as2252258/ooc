<?php
/**
 * Created by PhpStorm.
 * User: qv
 * Date: 2018/10/9 0009
 * Time: 9:31
 */

namespace Yoc\console;


use Yoc\base\BUrlManager;
use Yoc\exception\RequestException;
use Yoc\http\HttpHeaders;
use Yoc\http\HttpParams;

class UrlManager extends BUrlManager
{


	/** @var \swoole_http_request */
	public $request;

	/** @var \swoole_http_response */
	public $response;

	public $namespace = 'commands\\';

	public $route = [];

	/**
	 * @param \swoole_http_request $request
	 * @return \Yoc\web\Action
	 * @throws RequestException
	 * @throws \ReflectionException|\Exception
	 */
	public function requestHandler($param)
	{
		$this->regRequest($param);
		$controller = $this->createController();
		return $controller->action;
	}

	/**
	 * @param array $request
	 * @throws \Exception
	 */
	private function regRequest($request)
	{
		if (!isset($request[1])) {
			throw new \Exception('Page not find.');
		}
		$data = [];
		$header['request_uri'] = $request[1];
		if (count($request) > 2) $data = $this->resolveParam($request);
		\Yoc::$app->set('request', [
			'class' => 'Yoc\http\Request',
			'startTime' => microtime(TRUE),
			'params' => new HttpParams([], $data, []),
			'headers' => new HttpHeaders($header),
		]);
	}

	/**
	 * @param array $param
	 * @return array
	 * 解析参数
	 */
	public function resolveParam(array $param)
	{
		$arr = [];
		$data = array_slice($param, 2);
		if (empty($data)) {
			return $arr;
		}
		foreach ($data as $key => $val) {
			if (strpos($val, '=') === FALSE) {
				continue;
			}
			$_tmp = explode('=', $val);

			if (!isset($_tmp[0]) || !isset($_tmp[1])) {
				continue;
			}

			$arr[$_tmp[0]] = $_tmp[1];
		}
		return $arr;
	}
}
