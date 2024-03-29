<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2019-03-22
 * Time: 19:09
 */

namespace Beauty\server;


use Swoole\Event;
use Beauty\core\JSON;

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
		static::$inotify = inotify_init();
		$process->name('event: file change.');

		static::watch(APP_PATH);
		Event::add(static::$inotify, [Process::class, 'check']);
		Event::wait();
	}

	/**
	 * 开始监听
	 */
	public static function check()
	{
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
				continue;
			} else if (in_array($ev['mask'], $eventList)) {
				$fileType = strstr($ev['name'], '.');
				//非重启类型
				if ($fileType !== '.php') {
					continue;
				}
			} else {
				continue;
			}
			try {
				if (static::$int !== -1) {
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
		$val = \Beauty::$app->runtimePath . '/socket.sock';
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
