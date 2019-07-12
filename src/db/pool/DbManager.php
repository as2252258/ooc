<?php


namespace Yoc\db\pool;


use Yoc\db\Connection;

class DbManager
{

	/** @var Connection[] $dbs */
	private static $dbs = [];

	private static $inTransaction = false;

	/**
	 * @param string $name
	 * @param Connection $connect
	 * @throws \Exception
	 */
	public static function register(string $name, Connection $connect)
	{
		if (static::$inTransaction && !$connect->isTran()) {
			$connect->beginTransaction();
		}

		if (!isset(static::$dbs[$name])) {
			static::$dbs[$name] = [];
		}

		foreach (static::$dbs[$name] as $item) {
			if ($connect === $item) {
				return;
			}
		}

		static::$dbs[$name][] = $connect;
	}

	/**
	 * @throws \Exception
	 */
	public static function beginTransaction()
	{
		if (static::$inTransaction === true) {
			return;
		}

		if (count(static::$dbs) < 1) {
			return;
		}

		foreach (static::$dbs as $connection) {
			if ($connection->isTran()) {
				continue;
			}
			$connection->beginTransaction();
		}
	}

	/**
	 * @throws \Exception
	 */
	public static function rollback()
	{
		if (static::$inTransaction === false) {
			return;
		}

		if (count(static::$dbs) < 1) {
			return;
		}

		foreach (static::$dbs as $connection) {
			if (!$connection->isTran()) {
				continue;
			}
			$connection->rollback();
		}
	}

	/**
	 * @throws \Exception
	 */
	public static function commit()
	{
		if (static::$inTransaction === false) {
			return;
		}

		if (count(static::$dbs) < 1) {
			return;
		}

		foreach (static::$dbs as $connection) {
			if (!$connection->isTran()) {
				continue;
			}
			$connection->commit();
		}
	}

	/**
	 * 回收链接
	 */
	public static function release()
	{
		if (count(static::$dbs) < 1) {
			return;
		}

		foreach (static::$dbs as $connection) {
			$connection->release();
		}
		static::$dbs = [];
	}

	/**
	 * 断开连接
	 */
	public static function disconnect()
	{
		if (count(static::$dbs) < 1) {
			return;
		}

		foreach (static::$dbs as $connection) {
			$connection->disconnect();
		}
		static::$dbs = [];
	}

}
