<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2019-03-22
 * Time: 14:36
 */

namespace Beauty\error;


class Logger
{

	private static $logs = [];

	/**
	 * @param $message
	 * @param string $category
	 * @throws \Exception
	 */
	public static function debug($message, $category = 'app')
	{
		static::writer($message, $category);
	}


	/**
	 * @param $message
	 * @param string $category
	 * @throws \Exception
	 */
	public static function trance($message, $category = 'app')
	{
		static::writer($message, $category);
	}


	/**
	 * @param $message
	 * @param string $category
	 * @throws \Exception
	 */
	public static function error($message, $category = 'app')
	{
		static::writer($message, $category);
	}

	/**
	 * @param $message
	 * @param string $category
	 * @throws \Exception
	 */
	public static function success($message, $category = 'app')
	{
		static::writer($message, $category);
	}

	/**
	 * @param $message
	 * @param $category
	 *
	 * @throws \Exception
	 */
	private static function writer($message, $category = 'app')
	{
		if ($message instanceof \Exception) {
			$message = $message->getMessage();
		} else {
			if (is_array($message) || is_object($message)) {
				$message = self::arrayformat($message);
			}
		}
		if (is_array($message)) {
			$message = self::arrayformat($message);
		}
		if (!empty($message)) {
			if (!is_array(static::$logs)) {
				static::$logs = [];
			}
			array_push(static::$logs, [$category, $message]);
		}
	}

	/**
	 * @param string $application
	 * @return mixed
	 */
	public static function getLastError($application = 'app')
	{
		$_tmp = [];
		foreach (static::$logs as $key => $val) {
			if ($val[0] != $application) {
				continue;
			}
			$_tmp[] = $val[1];
		}
		if (empty($_tmp)) {
			return 'Unknown error.';
		}
		return end($_tmp);
	}


	/**
	 * @return array
	 * 写入日志
	 */
	public static function insert()
	{
		if (empty(static::$logs)) {
			return static::$logs = [];
		}

		(new LogTask(static::$logs))->handler();
		return static::$logs = [];
	}

	/**
	 * @param $data
	 * @return string
	 */
	private static function arrayformat($data)
	{
		if (is_string($data)) {
			return $data;
		}
		if ($data instanceof \Exception) {
			$data = self::getException($data);
		} else if (is_object($data)) {
			$data = get_object_vars($data);
		}

		$_tmp = [];
		foreach ($data as $key => $val) {
			if (is_array($val)) {
				$_tmp[] = self::arrayformat($val);
			} else {
				$_tmp[] = (is_string($key) ? $key . ' : ' : '') . $val;
			}
		}
		return implode(PHP_EOL, $_tmp);
	}

	/**
	 * @param \Exception $exception
	 * @return array
	 */
	private static function getException($exception)
	{
		$_tmp = [$exception->getMessage()];
		$_tmp[] = $exception->getFile() . ' on line ' . $exception->getLine();
		$_tmp[] = $exception->getTrace();
		return $_tmp;
	}

}
