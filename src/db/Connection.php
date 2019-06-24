<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/3/30 0030
 * Time: 14:09
 */

namespace Yoc\db;


use Yoc\base\Component;
use Yoc\db\mysql\Schema;
use Yoc\db\pool\Master;
use Yoc\db\pool\Slave;
use Yoc\error\Logger;
use Yoc\event\Event;

/**
 * Class Connection
 * @package Yoc\db
 *
 * 连接管理器
 *   创建连接
 *   回收连接
 */
class Connection extends Component
{
	public $id = 'db';
	public $cds = '';
	public $password = '';
	public $username = '';
	public $charset = 'utf-8';
	public $tablePrefix = '';
	public $masterConfig = [];

	public $slaveConfig = [];

	/** @var Schema $_schema */
	private $_schema = null;

	/** @var Master */
	private $masterInstance = NULL;

	/** @var Slave */
	private $slaveInstance = NULL;

	/**
	 * register a release event.
	 */
	public function init()
	{
		Event::on('AFTER_REQUEST', [$this, 'releaseAll']);
		Event::on('AFTER_TASK', [$this, 'disconnect']);
	}

	/**
	 * @param null $sql
	 * @return \PDO
	 * @throws \Exception
	 */
	public function getConnect($sql = NULL)
	{
		return $this->getPdo($sql);
	}

	/**
	 * @param $sql
	 * @return \PDO
	 * @throws \Exception
	 */
	private function getPdo($sql)
	{
		if ($this->isWrite($sql)) {
			$connect = $this->getMasterConnect();
		} else {
			$connect = $this->getSlaveConnect();
		}
		return $connect->getConnect();
	}

	/**
	 * @return mixed|object|Schema
	 * @throws \Exception
	 */
	public function getSchema()
	{
		if ($this->_schema === null) {
			$this->_schema = \Yoc::createObject([
				'class' => Schema::class,
				'db' => $this
			]);
		}
		return $this->_schema;
	}

	/**
	 * @param $sql
	 * @return bool
	 */
	private function isWrite($sql)
	{
		if (empty($sql)) return false;

		$prefix = strtolower(mb_substr($sql, 0, 6));

		return in_array($prefix, ['insert', 'update', 'delete']);
	}

	/**
	 * @return bool
	 */
	public function isTran()
	{
		return $this->masterInstance->inTransaction();
	}

	/**
	 * @return Master
	 * @throws \Exception
	 */
	public function getMasterConnect()
	{
		if ($this->masterInstance instanceof Master) {
			return $this->masterInstance;
		}

		/** @var Master $class */
		$this->masterInstance = \Yoc::createObject([
			'class' => Master::class,
			'host' => $this->cds,
			'username' => $this->username,
			'password' => $this->password,
			'masterConfig' => $this->masterConfig
		]);

		return $this->masterInstance;
	}

	/**
	 * @return Slave|Master
	 * @throws \Exception
	 */
	public function getSlaveConnect()
	{
		if ($this->slaveInstance instanceof Slave) return $this->slaveInstance;

		if (empty($this->slaveConfig)) {
			return $this->masterInstance;
		}

		$slaveConfig = array_shift($this->slaveConfig);

		/** @var Master $class */
		$this->slaveInstance = \Yoc::createObject([
			'class' => Slave::class,
			'host' => $slaveConfig['cds'],
			'username' => $slaveConfig['username'],
			'password' => $slaveConfig['password'],
			'slaveConfig' => $this->slaveConfig
		]);

		return $this->slaveInstance;
	}

	/**
	 * @return $this
	 * @throws \Exception
	 */
	public function beginTransaction()
	{
		$this->masterInstance->beginTransaction();
		return $this;
	}

	/**
	 * @throws \Exception
	 * 事务回滚
	 */
	public function rollback()
	{
		$this->masterInstance->rollBack();
	}

	/**
	 * @throws \Exception
	 * 事务提交
	 */
	public function commit()
	{
		$this->masterInstance->commit();
	}

	/**
	 * @param $sql
	 * @param array $attributes
	 * @return Command
	 * @throws
	 */
	public function createCommand($sql = null, $attributes = [])
	{
		$command = new Command([
			'sql' => $sql,
			'db' => $this
		]);
		return $command->bindValues($attributes);
	}

	/**
	 * @return QueryBuilder
	 * @throws \Exception
	 */
	public function getBuild()
	{
		return $this->getSchema()->getQueryBuilder();
	}

	/**
	 *
	 * 回收链接
	 * @throws
	 */
	public function release()
	{
		if ($this->slaveInstance) {
			$this->slaveInstance->release();
		}
		if ($this->masterInstance) {
			$this->masterInstance->release();
		}
	}

	/**
	 * 回收所有连接
	 */
	public function releaseAll()
	{
		if ($this->slaveInstance) {
			$this->slaveInstance->releaseAll();
		}
		if ($this->masterInstance) {
			$this->masterInstance->releaseAll();
		}
	}

	/**
	 * 回收所有连接
	 */
	public function disconnect()
	{
		if ($this->slaveInstance) {
			$this->slaveInstance->disconnect();
		}
		if ($this->masterInstance) {
			$this->masterInstance->disconnect();
		}
	}

}
