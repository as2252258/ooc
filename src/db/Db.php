<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/4/4 0004
 * Time: 15:40
 */

use \Yoc\db\traits\QueryTrait;
use \Yoc\db\Connection;

class Db
{

	use QueryTrait;

	private static $db;

	/**
	 * @param $table
	 *
	 * @return static
	 */
	public static function table($table)
	{
		$db = new Db();
		$db->from($table);
		return $db;
	}

	/**
	 * @param Connection $db
	 * @return mixed
	 * @throws \Exception
	 */
	public function get(Connection $db = NULL)
	{
		$db = $this->createCommand($db);
		$query = $db->getBuild();
		return $db->createCommand($query->builder($this))
			->all();
	}

	/**
	 * @param Connection $db
	 * @return array|mixed
	 * @throws \Exception
	 */
	public function find(Connection $db = NULL)
	{
		$db = $this->createCommand($db);
		$query = $db->getBuild();
		return $db->createCommand($query->builder($this))
			->one();
	}

	/**
	 * @param Connection $db
	 * @return bool|int
	 * @throws Exception
	 */
	public function count(Connection $db = NULL)
	{
		$db = $this->createCommand($db);
		$query = $db->getBuild();
		return $db->createCommand($query->builder($this))
			->rowCount();
	}

	/**
	 * @param Connection $db
	 * @return array|mixed|null
	 * @throws Exception
	 */
	public function exists(Connection $db = NULL)
	{
		$db = $this->createCommand($db);
		$query = $db->getBuild();
		return $db->createCommand($query->builder($this))
			->fetchColumn();
	}

	/**
	 * @param $sql
	 * @param array $attributes
	 * @param Connection $db
	 * @return bool|int|array
	 * @throws Exception
	 */
	public static function findAllBySql(string $sql,array $attributes = [], Connection $db = NULL)
	{
		$db = $db ?? Yoc::$app->db;
		return $db->createCommand($sql, $attributes)->all();
	}

	/**
	 * @param $sql
	 * @param array $attributes
	 * @param Connection|NULL $db
	 * @return array|mixed
	 * @throws Exception
	 */
	public static function findBySql(string $sql,array $attributes = [], Connection $db = NULL)
	{
		/** @var Connection $db */
		list(, $db) = $db ?? Yoc::$app->db;

		return $db->createCommand($sql, $attributes)->one();
	}

	/**
	 * @param string $field
	 * @return array|null
	 * @throws \Exception
	 */
	public function values(string $field)
	{
		$data = $this->get();
		if (empty($data) || empty($field)) {
			return NULL;
		}
		$first = current($data);
		if (!isset($first[$field])) {
			return NULL;
		}
		return array_column($data, $field);
	}

	/**
	 * @param $field
	 * @return array|mixed|null
	 * @throws \Exception
	 */
	public function value($field)
	{
		$data = $this->find();
		if (!empty($field) && isset($data[$field])) {
			return $data[$field];
		}
		return $data;
	}

	/**
	 * @param string $dbName
	 * @return Connection|null
	 * @throws Exception
	 */
	private function createCommand($dbName = 'db')
	{
		return Yoc::$app->$dbName;
	}

	/**
	 * @param null $db
	 * @return bool|int
	 * @throws Exception
	 */
	public function delete($db = null)
	{
		/** @var Connection $db */
		list(, $db) = $db ?? Yoc::$app->dbPool->getDbConnect('server');

		$query = $db->getBuild()->builder($this);

		return $db->createCommand($query)->delete();
	}

	/**
	 * @param $table
	 * @param null $db
	 * @return bool|int
	 * @throws Exception
	 */
	public static function drop($table, $db = null)
	{
		/** @var Connection $db */
		list(, $db) = $db ?? Yoc::$app->dbPool->getDbConnect('server');

		return $db->createCommand('DROP TABLE ' . $table)->delete();
	}

	/**
	 * @param $table
	 * @param null $db
	 * @return bool|int
	 * @throws Exception
	 */
	public static function truncate($table, $db = null)
	{
		/** @var Connection $db */
		list(, $db) = $db ?? Yoc::$app->dbPool->getDbConnect('server');

		return $db->createCommand('TRUNCATE ' . $table)->exec();
	}

	/**
	 * @param $table
	 * @param Connection|NULL $db
	 * @return array|mixed|null
	 * @throws Exception
	 */
	public static function showCreateSql($table, Connection $db = NULL)
	{
		if (!$db) {
			/** @var Connection $db */
			list(, $db) = $db ?? Yoc::$app->dbPool->getDbConnect('server');
		}

		if (empty($table)) {
			return null;
		}

		return $db->createCommand('SHOW CREATE TABLE ' . $table)->one();
	}

	/**
	 * @param $table
	 * @param Connection $db
	 * @return array|mixed|null
	 * @throws Exception
	 */
	public static function desc($table, Connection $db = NULL)
	{
		if (!$db) {
			/** @var Connection $db */
			list(, $db) = $db ?? Yoc::$app->dbPool->getDbConnect('server');
		}

		if (empty($table)) {
			return null;
		}

		return $db->createCommand('SHOW FULL FIELDS FROM ' . $table)->all();
	}
}
