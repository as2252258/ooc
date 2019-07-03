<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/4/26 0026
 * Time: 14:53
 */

namespace Yoc\server;

use Yoc\base\Config;
use Yoc\di\Service;

class Socket extends Service
{

	public $host;

	public $port;

	public $serverHost;

	public $serverPort;

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
		$array = [$this->host, $this->port];
		$this->server = new \swoole_websocket_server(...$array);

		$default = $this->getDefaultConfig();
		$this->server->set(array_merge($default, $this->config));

		new Worker();

		$websock = Config::get('wss', false);
		if (empty($websock)) {
			$websock = WebSocket::class;
		}
		new $websock($this->host, $this->port);
		if ($this->serverHost && $this->serverPort) {
			$this->request = new Request($this->serverHost, $this->serverPort);
		}

		if ($config = Config::get('udp')) {
			new Udp($config['host'], $config['port']);
		}
		if (isset($this->config['task_worker_num'])) {
			new Task();
		}
		if (Config::get('usePipeMessage')) {
			new PipeMessage();
		}

		$pro = new Process('Yoc\\server\\Process::listen');
		$this->server->addProcess($pro);

		//进程执行
		$this->server->on('start', [$this, 'onStart']);

		echo '启动中. 请稍后....' . PHP_EOL;

		$server = $this->server;
		swoole_timer_after(1500, function () use ($server) {
			$server->start();
		});
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
	}
}
