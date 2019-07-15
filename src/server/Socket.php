<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/4/26 0026
 * Time: 14:53
 */

namespace Yoc\server;

use Swoole\Event;
use Swoole\WebSocket\Server;
use Yoc\base\Config;
use Yoc\di\Service;

class Socket extends Service
{

	public $host;

	public $port;

	public $http;

	public $config = [];

	/** @var Request $request */
	public $request;

	/** @var \swoole_websocket_server */
	private $server;
	public $isRun = false;


	/**
	 * @return array
	 * return server default config
	 */
	private function getDefaultConfig()
	{
		return [
			'worker_num' => 6,
			'reactor_num' => 4,
			'backlog' => 20000,
			'max_conn' => 2000,
			'open_eof_check' => FALSE,
			'http_parse_post' => TRUE,
			'package_eof' => "\r\n\r\n",
			'dispatch_mode' => 1,
			'daemonize' => 1,
			'open_cpu_affinity' => 1,
			'open_tcp_nodelay' => 1,
			'tcp_defer_accept' => 1,
			'task_worker_num' => 10,
			'enable_port_reuse' => TRUE,
			'discard_timeout_request' => FALSE,
			'open_mqtt_protocol' => TRUE,
			'task_ipc_mode' => 1,
			'task_max_request' => 50000,
			'message_queue_key' => 0x72000120,
			'tcp_fastopen' => TRUE,
			'reload_async' => TRUE,
			'heartbeat_idle_time' => 600,
			'heartbeat_check_interval' => 50,
			'package_max_length' => 3096000,
			'open_websocket_close_frame' => TRUE,
			'websocket_subprotocol' => TRUE,
			'http_compression' => true,
		];
	}


	/**
	 * @var array
	 *
	 * @uses $listens =[
	 *      ['pro'=>TCP, 'port'=>100, 'address'=> '', 'callback' => ''],
	 *      ['pro'=>UDP, 'callback' => '']
	 *      ['pro'=>TCP6, 'callback' => '']
	 * ]
	 */
	public $listens = [];

	/**
	 * @throws \Exception
	 */
	public function run()
	{
		echo '启动中. 请稍后....' . PHP_EOL;
		sleep(1.5);

		$this->server = new Server($this->host, $this->port);

		$this->server->set(array_merge($this->getDefaultConfig(), $this->config));

		$this->socketListen();
		if (isset($this->config['task_worker_num'])) {
			new Task();
		}
		if (Config::get('usePipeMessage')) {
			new PipeMessage();
		}

		$pro = new \Swoole\Process([Process::class, 'listen']);
		$this->server->addProcess($pro);

		//进程执行
		$this->server->on('start', [$this, 'onStart']);
		$this->server->start();
	}

	/**
	 * @throws \Yoc\exception\ConfigException
	 */
	public function socketListen()
	{
		new Worker();

		$callback = Config::get('wss', false, WebSocket::class);
		new $callback($this->host, $this->port);

		//监听HTTP_SERVER
		if ($this->http['host'] && $this->http['port']) {
			$this->request = new Request($this->http['host'], $this->http['port']);
		}
	}

	/**
	 * @param $key
	 * @param $value
	 */
	public function setConfig($key, $value)
	{
		$this->config[$key] = $value;
	}

	/**
	 * @return \swoole_http_response
	 */
	public function getResponse()
	{
		return $this->response;
	}

	/**
	 * @return int
	 */
	public function getRandWorker()
	{
		return rand(0, $this->config['task_worker_num'] - 1);
	}

	/**
	 * @return \swoole_websocket_server
	 */
	public function getSocket()
	{
		return $this->server;
	}

	/**
	 * @param \swoole_server $server
	 *
	 * @return mixed|void
	 *
	 * 启动函数
	 */
	public function onStart(\swoole_server $server)
	{
		$time = \Yoc::$app->runtimePath . '/socket.sock';
		file_put_contents($time, $server->master_pid);

		if (function_exists('swoole_set_process_name')) {
			swoole_set_process_name(\Yoc::$app->id);
		}

		$this->isRun = true;

		$this->triDefer();

		Event::wait();
	}
}
