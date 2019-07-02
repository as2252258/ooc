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
	public $commandDir = APP_PATH . '/command';


	public $route = [];

	/**
	 * @param $request
	 * @return mixed
	 * @throws RequestException
	 * @throws \ReflectionException|\Exception
	 */
	public function requestHandler($param)
	{
		$this->regRequest($param);

		$commands = $this->getCommandList();
		$commands['list'] = ['', $this];

		if (!isset($commands[$param[1]])) {
			return '命令不存在！请仔细检查';
		}

		list($desc, $command) = $commands[$param[1]];
		return $command;
	}


	/**
	 * @throws \ReflectionException
	 * 加载全部命令
	 */
	protected function getCommandList()
	{
		$controllers = [];
		foreach (glob($this->commandDir . '/*') as $value) {
			$explode = explode('/', $value);

			$_contro = str_replace('.php', '', $explode[count($explode) - 1]);

			array_push($controllers, $_contro);
		}

		$methods = [];
		foreach ($controllers as $val) {
			$this->reflect('commands\\' . $val, $methods);
		}
		return $methods;
	}

	/**
	 * @throws \ReflectionException
	 */
	public function exec()
	{
		$methods = $this->getCommandList();

		echo str_pad('Commands', 24, ' ',STR_PAD_RIGHT) . '注释' . PHP_EOL;
		foreach ($methods as $key => $val) {
			list($method, $ts) = $val;
			echo str_pad("\033[32;40;1;1m" . $key . " \033[0m", 40, ' ',STR_PAD_RIGHT) . $method;
			echo PHP_EOL;
		}
	}

	/**
	 * @param $class
	 * @param $methods
	 * @return array
	 * @throws \ReflectionException
	 */
	private function reflect($class, &$methods)
	{
		$class = new \ReflectionClass($class);

		$object = $class->newInstance();

		$methods[$object->command] = [$object->description, $object];

		return $methods;
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
			if (empty($val)) {
				continue;
			}
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
