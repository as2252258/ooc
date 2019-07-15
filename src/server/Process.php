<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2019-03-22
 * Time: 19:09
 */

namespace Yoc\server;


use Swoole\Event;
use Yoc\core\JSON;

class Process
{
	private static $inotify;
	private static $isReloading = false;
	private static $watchFiles = [];
	private static $dirs = [];
	private static $events = IN_MODIFY | IN_DELETE | IN_CREATE | IN_MOVE;

	private static $int = -1;


	/**
	 * @param \swoole_process $process
	 * @throws \Exception
	 */
	public static function listen(\swoole_process $process)
	{
//		if (!extension_loaded('inotify')) {
//			swoole_timer_tick(3000, function () {
//				echo 'Waite check.' . PHP_EOL;
//			});
//		} else {
		static::$inotify = inotify_init();
		if (function_exists('swoole_set_process_name')) {
			swoole_set_process_name('Listen file modify event.');
		}
		static::watch(APP_PATH);
		Event::add(static::$inotify, [Process::class, 'check']);
	}

	/**
	 * 开始监听
	 */
	public static function check()
	{
		echo 'START LISTEN' . PHP_EOL;
		$events = inotify_read(static::$inotify);
		if (!$events) {
			echo("Events not found." . PHP_EOL);
			return;
		}
		if (static::$isReloading) {
			echo("at reloading." . PHP_EOL);
			return;
		}

		$eventList = [IN_CREATE, IN_DELETE, IN_MODIFY, IN_MOVED_TO, IN_MOVED_FROM];
		foreach ($events as $ev) {
			if (empty($ev['name'])) {
				continue;
			}
			if ($ev['mask'] == IN_IGNORED) {
				echo $ev['name'] . ' IS IN_IGNORED.' . PHP_EOL;
				continue;
			} else if (in_array($ev['mask'], $eventList)) {
				$fileType = strstr($ev['name'], '.');
				//非重启类型
				if ($fileType !== '.php') {
					echo $ev['name'] . ' IS FILE TYPE ERROR.' . PHP_EOL;
					continue;
				}
			} else {
				echo $ev['name'] . ' UNKNOWN ERROR.' . PHP_EOL;
				continue;
			}
			try {
				if (static::$int !== -1) {
					echo $ev['name'] . ' AT LOADING.' . PHP_EOL;
					return;
				}
				static::$int = @swoole_timer_after(2000, [static::class, 'reload']);
			} catch (\Exception $exception) {
				echo $exception->getMessage() . PHP_EOL;
			}

			static::$isReloading = true;
		}
	}

	/**
	 * @throws \Exception
	 */
	public static function reload()
	{
		echo 'reloading ....' . PHP_EOL;

		$val = \Yoc::$app->runtimePath . '/socket.sock';
		posix_kill(file_get_contents($val), SIGUSR1);

		//清理所有监听
		static::clearWatch();

		//重新监听
		foreach (static::$dirs as $root) {
			static::watch($root);
		}

		static::$int = -1;
		static::$isReloading = FALSE;
	}

	/**
	 * 清理所有inotify监听
	 */
	public static function clearWatch()
	{
		foreach (static::$watchFiles as $wd) {
			@inotify_rm_watch(static::$inotify, $wd);
		}
		static::$watchFiles = [];
	}


	/**
	 * @param $dir
	 * @param bool $root
	 * @return bool
	 * @throws \Exception
	 */
	public static function watch($dir, $root = TRUE)
	{
		//目录不存在
		if (!is_dir($dir)) {
			throw new \Exception("[$dir] is not a directory.");
		}
		//避免重复监听
		if (isset(static::$watchFiles[$dir])) {
			return FALSE;
		}
		//根目录
		if ($root) {
			static::$dirs[] = $dir;
		}

		echo '监听目录: ' . APP_PATH . PHP_EOL;
		if (in_array($dir, [APP_PATH . '/commands', APP_PATH . '/.git', APP_PATH . '/.gitee'])) {
			return FALSE;
		}

		$wd = @inotify_add_watch(static::$inotify, $dir, static::$events);
		static::$watchFiles[$dir] = $wd;

		$files = scandir($dir);
		foreach ($files as $f) {
			if ($f == '.' or $f == '..' or $f == 'runtime' or preg_match('/\.txt/', $f) or preg_match('/\.sql/', $f) or preg_match('/\.log/', $f)) {
				continue;
			}
			$path = $dir . '/' . $f;
			//递归目录
			if (is_dir($path)) {
				static::watch($path, FALSE);
			}

			//检测文件类型
			$fileType = strstr($f, '.');
			if ($fileType == '.php') {
				try {

					echo 'LISTEN FILE ' . $path . PHP_EOL;

					$wd = @inotify_add_watch(static::$inotify, $path, static::$events);
					static::$watchFiles[$path] = $wd;
				} catch (\Exception $exception) {
					echo 'Reload: ' . $exception->getMessage();
				}
			}
		}
		return TRUE;
	}
}
