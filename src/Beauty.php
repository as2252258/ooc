<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/4/24 0024
 * Time: 17:15
 */

use Beauty\db\ActiveRecord;
use Beauty\db\Collection;
use Beauty\task\Task;
use Beauty\web\Application;
use Beauty\route\Router;
use Beauty\di\Container;

defined('APP_PATH') or define('APP_PATH', realpath(__DIR__ . '/../../../..'));
defined('DISPLAY_ERRORS') or define('DISPLAY_ERRORS', true);
defined('IMG_TYPES') or define('IMG_TYPES', [
	IMAGETYPE_GIF => '.gif',
	IMAGETYPE_JPEG => '.jpeg',
	IMAGETYPE_PNG => '.png',
	IMAGETYPE_SWF => '.swf',
	IMAGETYPE_PSD => '.psd',
	IMAGETYPE_BMP => '.bmp',
	IMAGETYPE_TIFF_II => '.tif_ii',
	IMAGETYPE_TIFF_MM => '.tif_mm',
	IMAGETYPE_JPC => '.jpc',
	IMAGETYPE_JP2 => '.jp2',
	IMAGETYPE_JPX => '.jpx',
	IMAGETYPE_JB2 => '.jb2',
	IMAGETYPE_SWC => '.swc',
	IMAGETYPE_IFF => '.iff',
	IMAGETYPE_WBMP => '.wbmp',
	IMAGETYPE_XBM => '.xbm',
	IMAGETYPE_ICO => '.ico',
	IMAGETYPE_WEBP => '.webp',
]);

class Beauty
{

	/** @var Application */
	public static $app;

	/** @var Router */
	public static $router;

	/** @var Container */
	public static $container;

	/**
	 * @param $name
	 * @return mixed
	 * @throws
	 */
	public static function getApp($name)
	{
		return static::$app->get($name);
	}

	/**
	 * @param $tmp
	 * @return string
	 */
	public static function rename($tmp)
	{
		$hash = md5_file($tmp['tmp_name']);

		$later = exif_imagetype($tmp['tmp_name']);
		if (!isset(IMG_TYPES[$later])) {
			$later = '.jpg';
		}

		$match = '/(\w{12})(\w{5})(\w{9})(\w{6})/';
		$tmp = preg_replace($match, '$1-$2-$3-$4', $hash . $later);

		return strtoupper($tmp);
	}

	/**
	 * @param $object
	 * @param $config
	 *
	 * @return mixed
	 */
	public static function configure($object, $config)
	{
		foreach ($config as $key => $val) {
			$object->$key = $val;
		}
		return $object;
	}

	/**
	 * @param       $className
	 * @param array $construct
	 *
	 * @return mixed|object
	 * @throws
	 */
	public static function createObject($className, $construct = [])
	{
		if (is_string($className)) {
			return static::$container->get($className, $construct);
		} else if (is_array($className)) {
			if (!isset($className['class']) || empty($className['class'])) {
				throw new Exception('Object configuration must be an array containing a "class" element.');
			}
			$class = $className['class'];
			unset($className['class']);
			return static::$container->get($class, $construct, $className);
		} else if (is_callable($className, TRUE)) {
			return call_user_func($className, $construct);
		} else {
			throw new Exception('Unsupported configuration type: ' . gettype($className));
		}
	}

	/**
	 * @param string $event
	 * @param array $fds
	 * @param null $data
	 * @throws Exception
	 */
	public static function push(string $event, array $fds, $data = NULL)
	{
		$message = self::param($event, $data);

		/** @var swoole_websocket_server $service */
		$service = Beauty::getApp('socket')->getSocket();
		foreach ($fds as $fd) {
			if (!$service->exist($fd)) {
				continue;
			}
			$service->push($fd, $message);
		}
	}

	/**
	 * @param string $event
	 * @param null $data
	 * @return string
	 * 构造推送内容
	 * @throws Exception
	 */
	public static function param(string $event, $data = NULL)
	{
		if (is_object($data)) {
			if ($data instanceof ActiveRecord || $data instanceof Collection) {
				$data = $data->toArray();
			} else {
				$data = get_object_vars($data);
			}
		}
		if (!is_array($data)) $data = ['data' => $data];
		return json_encode(array_merge(['callback' => $event], $data));
	}

	/**
	 * @param $command
	 * @param array $param
	 * @param null $ms
	 * @return mixed
	 */
	public static function command($command, array $param = [])
	{
		$_TMP = [APP_PATH . '/artisan', $command];
		foreach ($param as $key => $val) {
			if (is_array($val) || is_object($val)) {
				continue;
			}
			$_TMP[] = $key . '=' . $val;
		}
		$data = shell_exec(PHP_BINDIR . '/php ' . implode(' ', $_TMP));
		return trim($data);
	}

	/**
	 * @return \Beauty\error\RestfulHandler
	 */
	public static function getLogger()
	{
		return Beauty::getApp('error');
	}

	/**
	 * @param Task $task
	 * @param int|null $work_id
	 * @return mixed
	 */
	public static function async(Task $task, int $work_id = null)
	{
		$server = static::getApp('socket');

		if (empty($work_id)) {
			$work_id = $server->getRandWorker();
		}

		$format = serialize($task);

		return $server->getSocket()->task($format, $work_id);
	}


	/**
	 * @param $name
	 * @param $callback
	 * @param $param
	 * @throws
	 */
	public static function on($name, $callback, $param = null)
	{
		$event = static::getApp('event');
		$event->on($name, $callback, $param);
	}


	/**
	 * @param $name
	 * @param $callback
	 * @param bool $isRemove
	 */
	public static function trigger($name, $callback, $isRemove = false)
	{
		$event = static::getApp('event');
		$event->try($name, $callback, $isRemove);
	}


	/**
	 * @return string
	 */
	public static function getRuntimePath()
	{
		return \Beauty::$app->runtimePath;
	}


	/**
	 * @param $callback
	 * @param bool $throw
	 * @return bool
	 * @throws
	 */
	public static function checkFunction($callback, $throw = false)
	{
		if (is_callable($callback, true)) {
			return true;
		}

		if ($throw) {
			throw new Exception('The $callback not is function.');
		}

		return false;
	}

}


/**
 * @param string $server_name
 *
 * @return bool
 */
function process_exists($server_name = 'im server')
{
	$cmd = 'ps axu|grep "' . $server_name . '"|grep -v "grep"|wc -l';
	$ret = shell_exec("$cmd");
	return (bool)trim(rtrim($ret, "\r\n"));
}

Beauty::$container = new Container();
//Beauty::$router = new Router();
