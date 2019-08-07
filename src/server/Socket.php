<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/4/26 0026
 * Time: 14:53
 */

namespace Beauty\server;

use Beauty;
use Swoole\WebSocket\Server;
use Beauty\di\Service;

class Socket extends Service
{

	public $host;

	public $port;

	public $udp = [];

	public $http;

	public $config = [];

	/** @var Request $request */
	public $request;

	/** @var Server $server */
	private $server;
	public $isRun = false;

	public $usePipeMessage = false;

	/** @var array $callback */
	public $callback = [];

	/**
	 * @return array
	 * return server default config
	 */
	private function getDefaultConfig()
	{
		$default = [
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
		return array_merge($default, $this->config);
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

		$worker = Beauty::createObject(Worker::class);
		$this->server->on('workerStart', [$worker, 'onWorkerStart']);
		$this->server->on('workerError', [$worker, 'onWorkerError']);
		$this->server->on('workerStop', [$worker, 'onWorkerStop']);
		$this->server->on('workerExit', [$worker, 'onWorkerExit']);

		$this->registerWebSocketCallback();     //注册 handshake AND message AND close.
		$this->registerHttpServerCallback();    //register http server callback
		$this->registerTaskAndPipeCallback();   //register task and message queue

		$pro = new \Swoole\Process([Process::class, 'listen']);
		$this->server->addProcess($pro);

		//进程执行
		$this->server->on('start', [$this, 'onStart']);
		$this->server->start();
	}

	/**
	 * @throws \Exception
	 */
	private function registerWebSocketCallback()
	{
		$socket = Beauty::createObject(WebSocket::class);
		$onHandshake = [$socket, 'onHandshake'];
		if (Beauty::checkFunction($this->callback['handshake'] ?? null)) {
			$onHandshake = $this->callback['handshake'];
			if (is_array($onHandshake) && !is_object($onHandshake[0])) {
				$onHandshake[0] = Beauty::createObject($onHandshake[0]);
			}
		}
		$onMessage = [$socket, 'onMessage'];
		if (Beauty::checkFunction($this->callback['message'] ?? null)) {
			$onMessage = $this->callback['message'];
			if (is_array($onMessage) && !is_object($onMessage[0])) {
				$onMessage[0] = Beauty::createObject($onMessage[0]);
			}
		}
		$onClose = [$socket, 'onClose'];
		if (Beauty::checkFunction($this->callback['close'] ?? null)) {
			$onClose = $this->callback['close'];
			if (is_array($onClose) && !is_object($onClose[0])) {
				$onClose[0] = Beauty::createObject($onClose[0]);
			}
		}
		$this->server->on('handshake', $onHandshake);
		$this->server->on('message', $onMessage);
		$this->server->on('close', $onClose);
	}

	/**
	 * @throws \Exception
	 */
	private function registerHttpServerCallback()
	{
		if (empty($this->http)) {
			return;
		}
		if (!isset($this->http['host']) || !isset($this->http['port'])) {
			return;
		}
		$this->server->addlistener($this->http['host'], $this->http['port'], SWOOLE_TCP);

		$request = Beauty::createObject(Request::class);
		$this->server->on('request', [$request, 'onRequest']);
	}

	/**
	 * @throws \Exception
	 */
	private function registerTaskAndPipeCallback()
	{
		$taskNumber = $this->config['task_worker_num'] ?? 0;
		if ($taskNumber > 0) {
			$task = Beauty::createObject(Task::class);
			$this->server->on('task', [$task, 'onTask']);
			$this->server->on('finish', [$task, 'onFinish']);
		}

		if ($this->usePipeMessage === true) {
			$pipeMessage = Beauty::createObject(PipeMessage::class);
			$this->server->on('pipeMessage', [$pipeMessage, 'onPipeMessage']);
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
	 * @return int
	 */
	public function getRandWorker()
	{
		return rand(0, $this->config['task_worker_num'] - 1);
	}

	/**
	 * @return Server
	 */
	public function getSocket()
	{
		return $this->server;
	}

	/**
	 * 重启
	 */
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
		$time = \Beauty::$app->runtimePath . '/socket.sock';
		file_put_contents($time, $server->master_pid);

		if (function_exists('swoole_set_process_name')) {
			swoole_set_process_name(\Beauty::$app->id);
		}

		$this->isRun = true;

		$this->triDefer();
	}
}
