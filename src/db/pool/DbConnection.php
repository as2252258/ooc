<?php


namespace Beauty\db\pool;


class DbConnection extends ConnectPool
{
	public $index = 0;

	public $host = '';

	public $user = '';

	public $pass = '';


	private $inTransaction = 0;

	/**
	 * @return bool
	 */
	public function inTransaction()
	{
		return $this->inTransaction == 1;
	}

	/**
	 * @throws \Exception
	 */
	public function beginTransaction()
	{
		$pdo = $this->getConnect();

		$this->inTransaction++;

		if ($this->inTransaction()) {
			$pdo->beginTransaction();
		}
	}

	/**
	 * @throws \Exception
	 */
	public function commit()
	{
		$this->inTransaction--;
		if ($this->inTransaction == 0) {
			$this->getConnect()->commit();
		}
	}

	/**
	 * @throws \Exception
	 */
	public function rollback()
	{
		$this->inTransaction--;
		if ($this->inTransaction == 0) {
			$this->getConnect()->rollBack();
		}
	}

	/**
	 * @return bool
	 * @throws \Exception
	 */
	public function isConnect()
	{
		$connect = $this->useConnect[$this->index];
		try {
			if (empty($connect) || !($connect instanceof \PDO)) {
				throw new \Exception('Unbale mysql client.');
			}
			if (!$connect->getAttribute(\PDO::ATTR_SERVER_INFO)) {
				throw new \Exception('Mysql cient timeout.');
			}
			return true;
		} catch (\Error | \Exception $exception) {
			unset($this->useConnect[$this->index]);
			$this->addError($exception->getMessage());
			return false;
		}
	}

	/**
	 * 如果存在事务，则不释放
	 */
	public function release()
	{
		if ($this->inTransaction()) {
			return;
		}
		parent::release(); // TODO: Change the autogenerated stub
	}

	/**
	 * @param array $config
	 * @return \PDO
	 * @throws
	 */
	public function createConnect(array $config = [])
	{
		$link = new \PDO($config['cds'], $config['username'], $config['password'], [
			\PDO::ATTR_PERSISTENT => true,
			\PDO::ATTR_TIMEOUT => 2 * 3600,
			\PDO::ATTR_EMULATE_PREPARES => false,
			\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => TRUE,
			\PDO::ATTR_CASE => \PDO::CASE_NATURAL,
		]);
		$link->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
		$link->setAttribute(\PDO::ATTR_STRINGIFY_FETCHES, false);
		$link->setAttribute(\PDO::ATTR_ORACLE_NULLS, \PDO::NULL_EMPTY_STRING);
		return $link;
	}

	/**
	 * @throws \Exception
	 */
	public function getConnectConfig()
	{
		throw new \Exception('You need add client configure on return.');
	}

}
