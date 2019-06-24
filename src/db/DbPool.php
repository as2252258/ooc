<?php
/**
 * Created by PhpStorm.
 * User: qv
 * Date: 2018/7/16 0016
 * Time: 11:41
 */

namespace Yoc\db;


use Yoc\base\BObject;
use Yoc\error\Logger;

class DbPool extends BObject
{

	const MASTER = 'DB_MASTER';
	const SLAVE = 'DB_SLAVE';

	/** @var \SplQueue */
	private $connections = [];

	private $useConnect = [];

	public $dbs = [];

	private $sort = 0;

	private $hasTrancation = false;


	public function beginTransaction()
	{
		$this->hasTrancation = true;
		foreach ($this->useConnect as $key => $val) {
			foreach ($val as $connects) {
				/** @var Connection $connect */
				list($id, $connect) = $connects;
				if ($connect->isTran()) {
					continue;
				}
				$connect->beginTransaction();
			}
		}
	}

	/**
	 * @param string $dbName
	 * @param bool $isSlave
	 * @return array
	 * @throws \Exception
	 */
	public function getDbConnect(string $dbName)
	{
		if (isset($this->useConnect[$dbName])) {
			return current($this->useConnect[$dbName])[1];
		}

		if (isset($this->connections[$dbName])) {
			if (isset($this->connections[$dbName])) {
				if ($this->connections[$dbName]->count() > 0) {
					$object = $this->connections[$dbName]->pop();
				}
			}
			if (!isset($object) || !($object instanceof Connection)) {
				$object = \Yoc::createObject($this->dbs[$dbName]);

				Logger::debug('创建数据库链接[' . $dbName . ']', 'mysql');
			}

			$object->setSerial($this->sort++);

			if ($this->hasTrancation) {
				$object->beginTransaction();
			}

			$connect = [$dbName, $object];
			$this->useConnect[$dbName][(string)$object->getSerial()] = $connect;


			/** @var Connection $create */
			return $connect;
		}

		if (!isset($this->dbs[$dbName])) {
			throw new \Exception('Unknown database[' . $dbName . '] configuration.');
		}

		$object = \Yoc::createObject($this->dbs[$dbName]);
		$object->setSerial($this->sort++);

		$connect = [$dbName, $object];
		$this->useConnect[$dbName][(string)$object->getSerial()] = $connect;

		/** @var Connection $create */
		return $connect;
	}


	/**
	 * @param Connection $connect
	 * @param string $dbName
	 * 回收数据库链接
	 */
	public function release(string $dbName, Connection $connect)
	{
		if ($this->hasTrancation) {
			return;
		}
		/** @var \SplQueue $queue */
		$queue = $this->connections[$dbName] ?? null;
		if (empty($queue)) {
			$queue = new \SplQueue();
			$this->connections[$dbName] = $queue;
		}
		$queue->push($connect);

		unset($this->useConnect[$dbName][$connect->getSerial()]);
		if (count($this->useConnect[$dbName] ?? []) < 1) {
			unset($this->useConnect[$dbName]);
		}
	}

	/**
	 * 提交事务
	 */
	public function commit()
	{
		foreach ($this->useConnect as $key => $val) {
			foreach ($val as $connects) {
				/** @var Connection $connect */
				list($id, $connect) = $connects;
				if (!$connect->isTran()) {
					continue;
				}
				$connect->commit();
			}
		}
		$this->hasTrancation = false;
	}

	/**
	 * 事务回滚
	 */
	public function rollback()
	{
		foreach ($this->useConnect as $key => $val) {
			foreach ($val as $connects) {
				/** @var Connection $connect */
				list($id, $connect) = $connects;
				if (!$connect->isTran()) {
					continue;
				}
				$connect->rollback();
			}
		}
		$this->hasTrancation = false;
	}

	/**
	 * @throws \Exception
	 */
	public function runInit()
	{
		$this->sort = 0;

		$this->connections = [];
		$this->useConnect = [];

		foreach ($this->dbs as $key => $connectConfig) {
			/** @var Connection $object */
			$object = \Yoc::createObject($connectConfig);
			$object->getSlaveConnect();
			$object->getMasterConnect();

			if (!isset($this->connections[$key])) {
				$this->connections[$key] = new \SplQueue();
			}

			$this->connections[$key]->push($object);
		}
	}

	public function releaseAll()
	{
		foreach ($this->useConnect as $key => $val) {
			foreach ($val as $connect) {
				/** @var Connection $connect */
				list($id, $connect) = $connect;
				if ($connect->isTran()) {
					$connect->rollback();
				}
				$connect->release();
			}
		}
		echo '释放链接' . PHP_EOL;
		$this->useConnect = [];
	}
}
