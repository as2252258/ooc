<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2019-03-22
 * Time: 16:09
 */

namespace Yoc\error;


use Swoole\Coroutine\Client;
use Yoc\core\JSON;
use Yoc\task\Task;

/**
 * Class LogTask
 * @package Yoc\error
 * 日志记录
 */
class LogTask extends Task
{

	/**
	 * 触发事件
	 * @param $param
	 * @throws
	 */
	public function handler()
	{
		$path = \Yoc::$app->runtimePath . '/log';
		if (!is_dir($path)) mkdir($path, 777);

		$_tmp = [];

		foreach ($this->param as $val) {
			list($category, $message) = $val;

			$local = $path . '/' . (empty($category) ? 'app' : $category);
			if (!is_dir($local)) mkdir($local);

			$local = realpath($local);

			if (!isset($_tmp[$category])) {
				$_tmp[$category] = [];
			}
			if (empty($_tmp[$category]['local'])) {
				$_tmp[$category]['local'] = $local;
			}
			$_tmp[$category]['data'][] = $message;
		}

		$text = '[debug ' . date('Y-m-d H:i:s') . ']:';
		foreach ($_tmp as $key => $val) {
			$data = implode(PHP_EOL, $val['data']);

			$logFile = $val['local'] . '/server.log';

			if (empty($data)) {
				continue;
			}
			$this->write($text . $data, $logFile);
		}
	}

	/**
	 * @param $context
	 * @param $path
	 */
	private function write($context, $path)
	{
		if (!file_exists($path)) {
			touch($path);
		}
		if (!file_exists($path) || !is_writeable($path)) {
			return;
		}

		$logFile = realpath($path);
		if (filesize($logFile) >= 4 * 1024000) {
			$logCount = count(glob($path . '/*'));
			if (file_exists($logFile)) {
				rename($logFile, $logFile . '.' . $logCount);
				shell_exec('echo 3 > /proc/sys/vm/drop_caches');
			}
			touch($logFile);
		}
		file_put_contents($logFile, $context . PHP_EOL, FILE_APPEND);
	}
}
