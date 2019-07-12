<?php


namespace Yoc\tcp;


use Swoole\Server;
use Yoc\core\ArrayAccess;
use Yoc\http\HttpHeaders;
use Yoc\http\HttpParams;
use Yoc\http\Request;

class Tcp extends Socket
{

	public $host = '0.0.0.0';
	public $port = 8973;

	public $tcp6 = false;

	public $path = APP_PATH . '/app/tcp';

	protected $type = 'tcp:8973';


	/**
	 * 启动tcp监听进程
	 */
	public function listen()
	{
		$tcpType = $this->tcp6 ? SWOOLE_TCP6 : SWOOLE_TCP;
		$this->server = new Server($this->host, $this->port, $tcpType);
		$this->server->on('WorkerStart', [$this, 'onWorkerStart']);
		$this->server->on('WorkerError', [$this, 'onWorkerError']);
		$this->server->on('WorkerStop', [$this, 'onWorkerStop']);
		$this->server->on('WorkerExit', [$this, 'onWorkerExit']);
		$this->server->on('Connect', [$this, 'onConnect']);
		$this->server->on('Receive', [$this, 'onReceive']);
		$this->server->on('Close', [$this, 'onClose']);
		$this->server->start();
	}

	/**
	 * @param Server $server
	 * @param int $fd
	 * @param int $reactor_id
	 * @param string $data
	 * @return mixed
	 * @throws
	 * route\s\sjson
	 */
	public function onReceive(Server $server, int $fd, int $reactor_id, string $data)
	{
		$decode = $this->decode($data);
		if ($decode === false) {
			return $server->send($fd, $this->encode('500'));
		}
		list($route, $params) = explode("\r\r\n\n", $decode);
		if (!empty($params)) {
			$params = json_decode($params, true);
		}

		\Yoc::$app->set('request', [
			'class' => Request::class,
			'params' => new HttpParams($params, [], []),
			'headers' => new HttpHeaders([])
		]);

		$node = app()->getRouter()->findByPath($route);
		if ($node === null) {
			return $server->send($fd, $this->encode('404'));
		}

		return $this->encode($node->run($node));
	}

	/**
	 * @param $data
	 * @return string
	 * @throws
	 */
	private function encode($data)
	{
		$password = \Yoc::$app->id;

		if (!is_string($data)) {
			$data = json_encode(ArrayAccess::toArray($data));
		}

		return openssl_encrypt($data, 'AES-256-CBC', $password);
	}

	/**
	 * @param $data
	 * @return string
	 */
	private function decode($data)
	{
		$password = \Yoc::$app->id;

		return openssl_decrypt($data, 'AES-256-CBC', $password);
	}

	/**
	 * @param Server $server
	 * @param int $fd
	 */
	public function onClose(Server $server, int $fd)
	{

	}
}
