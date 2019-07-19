<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/4/26 0026
 * Time: 14:53
 */

namespace Yoc\server;

use Swoole\Http\Response;
use Yoc;
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

	public $usePipeMessage = false;

	/**
	 * @return array
	 * return server default config
	 */
	private function getDefaultConfig()
	{
		return array_merge([
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
		], $this->config);
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
		sleep(1.5);
		$this->server = new Server($this->host, $this->port);
		$this->server->set($this->getDefaultConfig());

		$worker = Yoc::createObject(Worker::class);
		$this->server->on('workerStart', [$worker, 'onWorkerStart']);
		$this->server->on('workerError', [$worker, 'onWorkerError']);
		$this->server->on('workerStop', [$worker, 'onWorkerStop']);
		$this->server->on('workerExit', [$worker, 'onWorkerExit']);

		$socket = Yoc::createObject(WebSocket::class);
		$this->server->on('handshake', [$socket, 'onHandshake']);
		$this->server->on('message', [$socket, 'onMessage']);
		$this->server->on('close', [$socket, 'onClose']);

		if (!empty($this->http)) {
			$this->server->addlistener($this->http['host'], $this->http['port'], SWOOLE_TCP);

			$request = Yoc::createObject(Request::class);
			$this->server->on('request', [$request, 'onRequest']);
		}

		$taskNumber = $this->config['task_worker_num'] ?? 0;
		if ($taskNumber > 0) {
			$task = Yoc::createObject(Task::class);
			$this->server->on('task', [$task, 'onTask']);
		}

		if ($this->usePipeMessage === true) {
			$pipeMessage = Yoc::createObject(PipeMessage::class);
			$this->server->on('pipeMessage', [$pipeMessage, 'onPipeMessage']);
		}

		$pro = new \Swoole\Process([Process::class, 'listen']);
		$this->server->addProcess($pro);

		//进程执行
		$this->server->on('start', [$this, 'onStart']);
		$this->server->start();
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


	public function reload()
	{
		$this->server->reload();
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
