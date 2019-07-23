<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/4/25 0025
 * Time: 18:38
 */

namespace Beauty\web;


use Beauty\base\BApp;
use Beauty\base\Config;
use Beauty\cache\Redis;
use Beauty\core\Qn;
use Beauty\db\Connection;
use Beauty\db\DbPool;
use Beauty\error\RestfulHandler;
use Beauty\event\Event;
use Beauty\http\Request;
use Beauty\http\Response;
use Beauty\http\UrlManager;
use Beauty\permission\Permis;
use Beauty\server\Socket;

/**
 * Class Init
 *
 * @package Beauty\web
 *
 * @property-read Redis $redis
 * @property-read Socket $socket
 * @property-read Request $request
 * @property-read Response $response
 * @property-read RestfulHandler $error
 * @property-read UrlManager $urlManager
 * @property-read Connection $db
 * @property-read Qn $qn
 * @property-read DbPool $dbPool
 * @property-read Permis $permis
 * @property-read Config $config
 * @property-read Event $event
 */
class Application extends BApp
{

	/**
	 * @var string
	 */
	public $id = 'uniqueId';

	/** @var DbPool */
	public $dbPool;

	/**
	 * @throws
	 */
	public function initial()
	{
		global $argv;
		$socket = $this->get('socket');
		if (isset($argv[2])) {
			$this->modify($argv, $socket);
		}
		$this->shutdown();
		if (!isset($argv[1])) {
			$socket->run();
		} else if ($argv[1] == 'stop') {
			return;
		} else if ($argv[1] == 'restart') {
			$socket->run();
		} else {
			$socket->run();
		}
	}

	/**
	 * @param $argv
	 * @param Socket $socket
	 */
	private function modify($argv, $socket)
	{
		if ($argv[2] == 'back') {
			$socket->setConfig('daemonize', 1);
		} else if ($argv[2] == 'Front') {
			$socket->setConfig('daemonize', 0);
		} else {
			$socket->setConfig('daemonize', 0);
		}
	}

	private function shutdown()
	{
		$socket = $this->runtimePath . '/socket.sock';

		if (!file_exists($socket)) {
			return $this->checkProcessIsRuning();
		}

		$pathId = file_get_contents($socket);
		if (empty($pathId) || !is_numeric($pathId)) {
			return $this->checkProcessIsRuning();
		}

		@unlink($socket);
		$shell = shell_exec("ps aux | awk '{print $2}'");
		if (!in_array($pathId, explode(PHP_EOL, $shell))) {
			return $this->checkProcessIsRuning();
		}

		shell_exec("kill -TERM $pathId");

		return $this->checkProcessIsRuning();
	}

	private function checkProcessIsRuning()
	{
		echo 'please wait.';
		while (true) {
			if (!process_exists('PHP_' . $this->id)) {
				break;
			};
			sleep(1);
			echo '.';

			$this->closeChildProcess();
		}
		echo PHP_EOL . 'Stop Ok...' . PHP_EOL;
	}

	/**
	 * @return bool
	 */
	private function closeChildProcess()
	{
		$ids = $this->getPathIdsByProcess('PHP_' . $this->id);
		echo implode(',', $ids) . PHP_EOL;
		foreach ($ids as $pathId) {
			shell_exec('kill -9 ' . $pathId);
		}
		return true;
	}

	/**
	 * @return array|null
	 */
	private function getPathIdsByProcess($id)
	{
		$resul = shell_exec("ps x | grep '{$id}' | awk '{print $1}'");
		$explode = explode(PHP_EOL, $resul);

		return array_filter($explode);
	}
}
