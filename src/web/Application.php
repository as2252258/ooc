<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/4/25 0025
 * Time: 18:38
 */

namespace Yoc\web;


use components\Authorize;
use Yoc\base\BApp;
use Yoc\base\Config;
use Yoc\cache\Redis;
use Yoc\core\Qn;
use Yoc\db\Connection;
use Yoc\db\DbPool;
use Yoc\error\RestfulHandler;
use Yoc\http\Request;
use Yoc\http\Response;
use Yoc\http\UrlManager;
use Yoc\permission\Permis;
use Yoc\server\Socket;

/**
 * Class Init
 *
 * @package Yoc\web
 *
 * @property-read \Redis|Redis $redis
 * @property-read Socket $socket
 * @property-read Request $request
 * @property-read Response $response
 * @property-read RestfulHandler $error
 * @property-read UrlManager $urlManager
 * @property-read Connection $db
 * @property-read Qn $qn
 * @property-read DbPool $dbPool
 * @property-read Permis $permis
 * @property-read Authorize $auth
 * @property-read Config $config
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

		$pathIds = $this->getPathIdsByProcess($this->id);

		if (!file_exists($socket)) {
			$pathId = current($pathIds);
		} else {
			$pathId = file_get_contents($socket);
			if (empty($pathId) || !is_numeric($pathId)) {
				$pathId = current($pathIds);
			}
			@unlink($socket);
		}
		if (empty($pathId)) {
			return $this->closeChildProcess();
		}

		$shell = shell_exec("ps aux | awk '{print $2}'");
		if (!in_array($pathId, explode(PHP_EOL, $shell))) {
			return $this->closeChildProcess();
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
		}
		echo PHP_EOL . 'Stop Ok...' . PHP_EOL;
	}

	/**
	 * @return bool
	 */
	private function closeChildProcess()
	{
		$ids = $this->getPathIdsByProcess('PHP_' . $this->id);
		foreach ($ids as $pathId) {
			shell_exec('kill ' . $pathId);
		}
		return $this->checkProcessIsRuning();
	}

	/**
	 * @return array|null
	 */
	private function getPathIdsByProcess($id)
	{
		$resul = shell_exec("ps x | grep '{$id}' | awk '{print $1}'");
		$explode = explode(PHP_EOL, $resul);
		if (count($explode) == 4) {
			return explode(PHP_EOL, $resul);
		}
		return [];
	}
}
