<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/4/24 0024
 * Time: 17:15
 */

use Yoc\db\ActiveRecord;
use Yoc\db\Collection;
use Yoc\task\Task;
use Yoc\web\Application;
use Yoc\route\Router;
use Yoc\di\Container;

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

class Yoc
{

	/** @var Application */
	public static $app;

	/** @var Router */
	public static $router;

	/** @var Container */
	public static $container;

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
	 * @param $user_id
	 * @param null $data
	 * @throws Exception
	 */
	public static function push(string $event, $user_id, $data = NULL)
	{
		$message = self::param($event, $data);

		/** @var swoole_websocket_server $service */
		$service = Yoc::$app->socket->getSocket();

		if (is_array($user_id)) {
			$fd = $user_id;
		} else {
			$fd = static::getFds($user_id);
		}
		if (!is_array($fd) || empty($fd)) {
			return;
		}
		foreach ($fd as $_key => $_val) {
			if (!$service->exist(intval($_val))) {
				continue;
			}
			$service->push($_val, $message);
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
	 * @param $userId
	 * @return array|null
	 */
	public static function getFds($userId)
	{
		$redis = \Yoc::$app->redis;
		$fds = $redis->hGet('user_fds', $userId);
		if (empty($fds)) {
			return NULL;
		}
		$all = [];
		$server = \Yoc::$app->socket->getSocket();
		foreach (explode(',', $fds) as $key => $val) {
			if (!is_numeric($val)) {
				continue;
			}
			if ($server->exist((int)$val)) {
				$all[] = $val;
			}
		}
		if (empty($all)) {
			$redis->hDel('user_fds', $userId);
		} else {
			$all = array_unique($all);
			$redis->hMset('user_fds', [$userId => implode(',', $all)]);
		}
		return $all;
	}

	/**
	 * @param $message
	 * @throws
	 */
	public static function trance(...$message)
	{
		$redis = Yoc::$app->redis;
		/** @var swoole_websocket_server $socket */
		$socket = Yoc::$app->get('socket');
		if (empty($socket) || !($socket = $socket->getSocket())) {
			return;
		}
		$remove = [];
		$members = $redis->sMembers('debug_list');
		foreach ($members as $val) {
			if (!$socket->exist($val)) {
				$remove[] = $val;
			} else {
				if (count($message) == 1) {
					$message = current($message);
				}
				$socket->push($val, print_r($message, TRUE));
			}
		}
		if (!empty($remove)) {
			$redis->sRem('debug_list', ...$remove);
		}
	}

	/**
	 * @param $command
	 * @param array $param
	 * @param null $ms
	 * @return mixed
	 */
	public static function command($command, $param = [], $ms = NULL)
	{
		$_TMP = [APP_PATH . '/artisan', $command];
		if (!empty($param) && is_array($param)) {
			foreach ($param as $key => $val) {
				if (is_array($val) || is_object($val)) {
					continue;
				}
				$_TMP[] = $key . '=' . $val;
			}
		}
		$data = shell_exec(PHP_BINDIR . '/php ' . implode(' ', $_TMP));
		return trim($data);
	}

	/**
	 * @param \Yoc\task\Task $task
	 * @return false|int
	 */
	public static function putin(Task $task)
	{
		$server = \Yoc::$app->socket;

		$worker = $server->getRandWorker();

		$format = serialize($task);

		return $server->getSocket()->task($format, $worker);
	}

	/**
	 * @return \Yoc\error\RestfulHandler
	 */
	public static function getLogger()
	{
		return Yoc::$app->error;
	}

	/**
	 * @param $name
	 * @param $default
	 * @return mixed
	 * @throws Exception
	 */
	public static function getOrCreate($name, $default)
	{
		if (!Yoc::$app->has($name)) {
			return $default;
		} else {
			return Yoc::$app->get($name);
		}
	}

	/**
	 * @param $name
	 * @return mixed
	 * @throws
	 */
	public static function get($name)
	{
		return Yoc::$app->get($name);
	}

	/**
	 * @param $name
	 * @param $options
	 * @throws Exception
	 */
	public static function set($name, $options)
	{
		Yoc::$app->set($name, $options);
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

Yoc::$container = new Container();
//Yoc::$router = new Router();
