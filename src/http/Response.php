<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/4/24 0024
 * Time: 19:39
 */

namespace Beauty\http;

use Beauty\base\Component;
use Beauty\core\ArrayAccess;
use Beauty\core\JSON;
use Beauty\db\ActiveRecord;
use Beauty\db\Collection;
use Beauty\error\Logger;
use Beauty\event\Event;
use Beauty\http\formatter\HtmlFormatter;
use Beauty\http\formatter\IFormatter;
use Beauty\http\formatter\JsonFormatter;
use Beauty\http\formatter\XmlFormatter;
use Beauty\server\Socket;
use Swoole\WebSocket\Server;

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
	public $isWebSocket = false;
	public $headers = [];

	public $fd = 0;

	/**
	 * 清理
	 */
	public function init()
	{
		Event::on('AFTER_REQUEST', [$this, 'clear']);
	}

	/**
	 * @param int $fd
	 */
	public function setIsWebSocket(int $fd)
	{
		if ($fd < 0) {
			return;
		}
		$this->fd = $fd;
		$this->isWebSocket = true;
	}

	/**
	 * 清理无用数据
	 */
	public function clear()
	{
		$this->response = null;
		$this->fd = 0;
		$this->isWebSocket = false;
	}

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
	 * @param $isWebSocket
	 * @param $fd
	 * @return mixed
	 * @throws \Exception
	 */
	public function send($context, $statusCode = 200)
	{
		$this->statusCode = $statusCode;
		/** @var IFormatter $formatter */
		if ($this->isWebSocket) {
			$this->format = self::JSON;

			/** @var Server $socket */
			$socket = \Beauty::getApp('socket')->getSocket();
			$socket->send($this->fd, $this->sendContext($context, true));
		} else if ($this->response instanceof \swoole_http_response) {
			$this->sendContext($context);
		} else {
			$this->printResult($context);
		}

		Event::trigger('AFTER_REQUEST');
		return Logger::insert();
	}

	/**
	 * @param $context
	 * @param $isReturn
	 * @return mixed
	 * @throws \Exception
	 */
	private function sendContext($context, $isReturn = false)
	{
		if ($this->format == self::JSON) {
			$config['class'] = JsonFormatter::class;
		} else if ($this->format == self::XML) {
			$config['class'] = XmlFormatter::class;
		} else {
			$config['class'] = HtmlFormatter::class;
		}
		$formatter = \Beauty::createObject($config);
		$sendData = $formatter->send($context)->getData();

		if ($isReturn) {
			return $sendData;
		}
		return $this->setHeaders()->end($isReturn);
	}

	/**
	 * @param $result
	 * @throws \Exception
	 */
	private function printResult($result)
	{
		if (!is_string($result)) {
			$result = ArrayAccess::toArray($result);
		}
		if (is_array($result)) {
			$result = JSON::encode($result);
		}
		echo 'Command Result: ' . PHP_EOL;
		echo '   ' . $result . PHP_EOL;
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


	public function setResponse($response)
	{
		$this->response = $response;
	}


	/**
	 * @throws \Exception
	 */
	public function sendNotFind()
	{
		$this->format = self::HTML;
		$this->send('', 404);
	}

}
