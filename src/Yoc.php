<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/4/24 0024
 * Time: 17:15
 */

class Yoc
{

	/** @var \Yoc\web\Application */
	public static $app;

	/** @var \Yoc\route\Router */
	public static $router;

	/** @var \Yoc\di\Container */
	public static $container;

	/** @var array */
	public static $classMap = [
		'Db' => APP_PATH . '/vendor/yoc/db/Db.php',
	];

	/**
	 * @param $className
	 *
	 * @throws Exception
	 *
	 * 类的自动加载
	 */
	public static function autoload($className)
	{
		$classFile = NULL;
		if (isset(self::$classMap[$className])) {
			$classFile = self::$classMap[$className];
		} else {
			$classFile = str_replace('\\', '/', $className) . '.php';
			$className = lcfirst($classFile);
			if (strpos($className, 'yoc') === 0) {
				$classFile = __DIR__ . str_replace('yoc', '', $className);
			} else {
				$classFile = APP_PATH . '/' . $className;
			}
		}
		if (!file_exists($classFile)) {
			//echo 'File Not Exists: ' . $classFile . PHP_EOL;
			return;
		}
		include $classFile;
	}

	/**
	 * @param $tmp_file
	 * @param $file_type
	 * @return string
	 * 生成图片名称
	 */
	public static function rename($tmp)
	{
		$tmp_file = $tmp['tmp_name'];
		$file_type = $tmp['name'];

		$tmp = md5_file($tmp_file);
		$tmp .= strchr($file_type, '.');
		$tmp = preg_replace('/(\w{12})(\w{5})(\w{9})(\w{6})/', '$1-$2-$3-$4', $tmp);
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
	 * @param        $userId
	 * @param        $data
	 * @throws
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
			if ($data instanceof \Yoc\db\ActiveRecord || $data instanceof \Yoc\db\Collection) {
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
	 * @return \Yoc\db\DbPool
	 * @throws Exception
	 */
	public static function getDbPool()
	{
		return Yoc::$app->get('dbPool');
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

	public static function listenCommand(swoole_process $process)
	{
		while (1 == 1) {
			$data = json_decode($process->read(), TRUE);
			if (!is_array($data)) {
				continue;
			}
			try {
				$result = $process->exec(PHP_BINDIR . '/php', json_decode($process->read(), TRUE));
				$process->write(json_encode(['cmd' => 'success', 'result' => json_decode($result, TRUE)]));
			} catch (Exception $exception) {
				$process->write(json_encode(['cmd' => 'fail', 'result' => $exception->getMessage()]));
			}
		}
	}

	/**
	 * @param \Yoc\task\Task $task
	 * @return false|int
	 */
	public static function Delivery(\Yoc\task\Task $task)
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
	return (bool)rtrim($ret, "\r\n");
}

spl_autoload_register(['Yoc', 'autoload'], TRUE, TRUE);
Yoc::$container = new \Yoc\di\Container();
Yoc::$router = new Yoc\route\Router();