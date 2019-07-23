<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/5/24 0024
 * Time: 11:50
 */

namespace Beauty\base;

use Beauty\exception\ConfigException;


/**
 * Class Config
 * @package Beauty\base
 */
class Config extends Component
{

	const ERROR_MESSAGE = 'The not find :key in app configs.';

	public $data;

	/**
	 * @param $key
	 * @param bool $try
	 * @param mixed $default
	 * @return null
	 * @throws ConfigException
	 */
	public static function get($key, $try = FALSE, $default = null)
	{
		$config = \Beauty::$app->config;
		if (isset($config->data[$key])) {
			return $config->data[$key];
		} else if ($default !== null) {
			return $default;
		} else if ($try) {
			throw new ConfigException("The not find $key in app configs.");
		}
		return NULL;
	}

	/**
	 * @param $key
	 * @param $value
	 * @return mixed
	 */
	public static function set($key, $value)
	{
		$config = \Beauty::$app->config;
		return $config->data[$key] = $value;
	}

	/**
	 * @param $key
	 * @return bool
	 */
	public static function has($key)
	{
		$config = \Beauty::$app->config;
		return isset($config->data[$key]);
	}

	/**
	 * @param $name
	 * @param $value
	 */
	public function __set($name, $value)
	{
		$this->data[$name] = $value;
	}
}
