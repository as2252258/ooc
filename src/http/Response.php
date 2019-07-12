<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/4/24 0024
 * Time: 19:39
 */

namespace Yoc\http;

use Yoc\base\Component;
use Yoc\core\ArrayAccess;
use Yoc\core\JSON;
use Yoc\db\ActiveRecord;
use Yoc\db\Collection;
use Yoc\event\Event;
use Yoc\http\formatter\HtmlFormatter;
use Yoc\http\formatter\IFormatter;
use Yoc\http\formatter\JsonFormatter;
use Yoc\http\formatter\XmlFormatter;

class Response extends Component
{

	const JSON = 'json';
	const XML = 'xml';
	const HTML = 'html';

	/** @var string */
	public $format = self::JSON;

	/** @var int */
	public $statusCode = 200;

	/** @var \swoole_http_response */
	public $response;

	public $headers = [];

	/**
	 * @return string
	 */
	public function getContentType()
	{
		if ($this->format == self::JSON) {
			return 'application/json;charset=utf-8';
		} else if ($this->format == self::XML) {
			return 'application/xml;charset=utf-8';
		} else {
			return 'text/html;charset=utf-8';
		}
	}

	/**
	 * @return mixed
	 * @throws \Exception
	 */
	public function sender()
	{
		return $this->send(func_get_args());
	}

	/**
	 * @param $key
	 * @param $value
	 */
	public function addHeader($key, $value)
	{
		$this->headers[$key] = $value;
	}

	/**
	 * @param $context
	 * @param $statusCode
	 * @return mixed
	 * @throws \Exception
	 */
	public function send($context, $statusCode = 200)
	{
		$this->statusCode = $statusCode;

		/** @var IFormatter $formatter */
		if (getIsCommand()) {
			$this->printResult($context);
		} else if ($this->response instanceof \swoole_http_response) {
			$this->sendContext($context);
		}

		$this->triDefer();
		Event::trigger('AFTER_REQUEST');

		$this->response = null;
		$formatter = null;
		return true;
	}

	/**
	 * @param $context
	 * @throws \Exception
	 */
	private function sendContext($context)
	{
		if ($this->format == self::JSON) {
			$config['class'] = JsonFormatter::class;
		} else if ($this->format == self::XML) {
			$config['class'] = XmlFormatter::class;
		} else {
			$config['class'] = HtmlFormatter::class;
		}
		$formatter = \Yoc::createObject($config);

		$this->setHeaders()->end($formatter->send($context)->getData());
	}

	/**
	 * @param $result
	 * @throws \Exception
	 */
	private function printResult($result)
	{
		echo 'Command Result:' . PHP_EOL;
		if (is_object($result)) {
			if ($result instanceof Collection) {
				$result = $result->toArray();
			} else if ($result instanceof ActiveRecord) {
				$result = $result->toArray();
			} else {
				$result = get_object_vars($result);
			}
		} else if (is_array($result)) {
			$result = ArrayAccess::toArray($result);
		}
		if (is_array($result)) {
			$result = JSON::encode($result);
		}
		echo str_pad($result, 5, ' ', STR_PAD_LEFT) . PHP_EOL;
		echo 'Command Success!' . PHP_EOL;
	}

	/**
	 * @return \swoole_http_response
	 */
	private function setHeaders()
	{
		$this->response->status($this->statusCode);
		$this->response->header('Content-Type', $this->getContentType());
		$this->response->header('Access-Control-Allow-Origin', '*');
		$this->response->header('Run-Time', request()->getRuntime());

		foreach ($this->headers as $key => $val) {
			$this->response->header($key, $val);
		}
		$this->headers = [];

		return $this->response;
	}

	/**
	 * @param $url
	 * @param array $param
	 * @return int
	 */
	public function redirect($url, array $param = [])
	{
		if (!empty($param)) {
			$url .= '?' . http_build_query($param);
		}

		$url = ltrim($url, '/');
		if (!preg_match('/^http/', $url)) {
			$url = '/' . $url;
		}

		return redirect($url);
	}

}
