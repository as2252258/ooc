<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/4/27 0027
 * Time: 11:00
 */

namespace Yoc\cache;


use Yoc\base\Component;
use Yoc\event\Event;

/**
 * Class Redis
 * @package Yoc\cache
 * @see \Redis
 */
class Redis extends Component implements ICache
{
	public $host;
	public $auth;
	public $port;
	public $databases = 0;
	public $timeout = -1;
	public $prefix = 'idd';

	/** @var \Redis */
	private $redis = NULL;


	public function init()
	{
		Event::on('AFTER_REQUEST', [$this, 'close']);
	}

	/**
	 * @throws \Exception
	 */
	private function connect()
	{
		$redis = new \Redis();
		if (!$redis->connect($this->host, $this->port)) {
			throw new \Exception('The Redis Connect Fail.');
		}
		if (!$redis->auth($this->auth)) {
			throw new \Exception('The Redis Password error.');
		}
		$redis->select($this->databases);
		$redis->setOption(\Redis::OPT_READ_TIMEOUT, $this->timeout);
		$redis->setOption(\Redis::OPT_PREFIX, \Yoc::$app->id . ':');
		return $redis;
	}

	/**
	 * @return \Redis
	 * @throws \Exception
	 */
	private function getRedis()
	{
		try {
			if (!$this->redis instanceof \Redis) {
				$connect = $this->connect();
			} else if (!$this->redis->ping()) {
				$connect = $this->connect();
			}
		} catch (\Exception $redisException) {
			$this->addError($redisException, 'redis');
			$connect = $this->connect();
		}
		if (isset($connect)) {
			$this->redis = $connect;
		}
		if (!$this->redis) {
			throw new \Exception('The Redis Connect Fail.');
		}
		return $this->redis;
	}

	/**
	 * @param $name
	 * @param $arguments
	 * @return mixed
	 * @throws \Exception
	 */
	public function __call($name, $arguments)
	{
		if (is_array($name)) {
			$name = array_shift($name);
		}
		if (!method_exists($this, $name)) {
			return $this->getRedis()->$name(...$arguments);
		} else {
			return $this->$name(...$arguments);
		}
	}

	/**
	 * @throws \Exception
	 */
	public function close()
	{
		$this->redis->close();
		$this->redis = NULL;
	}
}
