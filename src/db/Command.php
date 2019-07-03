<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/3/30 0030
 * Time: 15:23
 */

namespace Yoc\db;


use Yoc\base\Component;
use Yoc\error\Logger;

/**
 * Class Command
 * @package Yoc\db
 */
class Command extends Component
{
	/** @var \PDO */
	public $pdo;

	/** @var Connection */
	public $db;

	/** @var string */
	public $sql = '';

	/** @var array */
	public $params = [];

	/** @var bool */
	private $isFail = TRUE;

	/** @var \PDOStatement */
	private $prepare;

	/**
	 * @return bool|\PDOStatement
	 * @throws
	 */
	private function query()
	{
		$this->getPdo();
		$this->prepare = $this->pdo->prepare($this->sql);
		Logger::debug($this->sql, 'mysql');
		if (!$this->prepare) {
			throw new \PDOException($this->getError());
		}
		return $this->bind();
	}

	/**
	 * @return bool|\PDOStatement
	 * @throws
	 */
	public function incrOrDecr()
	{
		Logger::debug($this->sql, 'mysql');
		return $this->getPdo()->exec($this->sql);
	}

	/**
	 * @return \PDOStatement
	 * @throws \Exception
	 */
	private function bind()
	{
		if (empty($this->params)) {
			return $this->prepare;
		}
		foreach ($this->params as $key => $val) {
			if (is_array($val)) {
				throw new \Exception("Save data cannot have array");
			}
			$this->bindParam(':' . ltrim($key, ':'), $val);
		}
		return $this->prepare;
	}

	/**
	 * @param bool $isInsert
	 * @return bool|string
	 * @throws
	 */
	public function save($isInsert = TRUE)
	{
		$exec = $this->query()->execute();
		echo $this->sql . PHP_EOL;
		if (!$exec) {
			$ok = $this->addErrorLog();
		} else if ($isInsert) {
			$ok = $this->lastId() ?? TRUE;
		} else {
			$ok = TRUE;
		}
		$this->close();
		return $ok;
	}

	/**
	 * @return string
	 */
	public function lastId()
	{
		return $this->pdo->lastInsertId();
	}

	/**
	 * @param $model
	 * @param $attributes
	 * @param $condition
	 * @param $param
	 * @param $columns
	 * @return Command
	 * @throws \Exception
	 */
	public function update($model, $attributes, $condition, $param, $columns)
	{
		$sql = $this->db->getBuild()->update($model, $attributes, $condition, $param, $columns);
		return $this->setSql($sql)->bindValues($param);
	}

	/**
	 * @param $tableName
	 * @param $attributes
	 * @param $param
	 * @return Command
	 * @throws \Exception
	 */
	public function insert($tableName, $attributes, $param)
	{
		$sql = $this->db->getBuild()->insert($tableName, $attributes, $param);
		return $this->setSql($sql)->bindValues($param);

	}

	/**
	 * @return bool|int
	 * @throws \Exception
	 */
	public function all()
	{
		return $this->executeRun('fetchAll');
	}

	/**
	 * @param $tableName
	 * @param $param
	 * @param $condition
	 * @return Command
	 * @throws \Exception
	 */
	public function incr($tableName, $param, $condition)
	{
		$sql = $this->db->getBuild()->incrOrDecr($tableName, $param, $condition);
		echo $sql . PHP_EOL;
		return $this->setSql($sql)->bindValues($param);

	}

	/**
	 * @return array|mixed
	 * @throws \Exception
	 */
	public function one()
	{
		return $this->executeRun('fetch');
	}

	/**
	 * @return bool|int
	 * @throws \Exception
	 */
	public function fetchColumn()
	{
		$result = $this->executeRun('fetchColumn');
		if (!is_array($result)) {
			return (bool)$result;
		}
		return array_shift($result) == NULL;
	}

	/**
	 * @return bool|int
	 * @throws \Exception
	 */
	public function rowCount()
	{
		return $this->executeRun('rowCount');
	}

	/**
	 * @param $type
	 * @return bool|int
	 * @throws \Exception
	 */
	private function executeRun($type)
	{
		$pdo = $this->query();
		if (!$pdo->execute()) {
			$result = $this->addErrorLog();
		} else if ($type == 'rowCount') {
			$result = $pdo->rowCount();
		} else {
			$result = $pdo->$type(\PDO::FETCH_ASSOC);
		}
		$this->close($pdo);
		return $result;
	}

	/**
	 * @param \PDOStatement $pdo
	 */
	private function close($pdo = null)
	{
		if ($pdo) {
			$pdo->closeCursor();
		}
		if ($this->prepare) {
			$this->prepare->closeCursor();
		}
	}

	/**
	 * @param $type
	 * @return bool|int
	 * @throws \Exception
	 */
	public function delete()
	{
		$sul = $this->query()->execute();
		$this->close();
		return $sul;
	}

	/**
	 * @param $type
	 * @return bool|int
	 * @throws \Exception
	 */
	public function exec()
	{
		if (!$this->query()->execute()) {
			$error = $this->prepare->errorInfo()[2] ?? 'db error';
			$this->close();
			return $this->addError($error, 'mysql');
		}
		$this->close();
		return $this->lastId();
	}

	/**
	 * @param $name
	 * @param $value
	 */
	public function bindParam($name, $value)
	{
		$this->prepare->bindParam($name, $value);
	}

	/**
	 * @return $this
	 */
	public function bindValues(array $data = NULL)
	{
		$this->isFail = TRUE;

		if (!is_array($this->params)) {
			$this->params = [];
		}
		if (!empty($data)) {
			$this->params = array_merge($this->params, $data);
		}
		return $this;
	}

	/**
	 * @param $sql
	 * @return $this
	 */
	public function setSql($sql)
	{
		$this->sql = $sql;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getError()
	{
		return $this->prepare->errorInfo()[2] ?? '系统繁忙. 请稍后再试.';
	}

	/**
	 * @return bool
	 * @throws \Exception
	 */
	public function addErrorLog()
	{
		return $this->addError($this->getError(), 'mysql');
	}

	/**
	 * @return \PDO
	 * @throws \Exception
	 */
	private function getPdo()
	{
		if ($this->pdo) {
			return $this->pdo;
		}
		$this->pdo = $this->db->getConnect($this->sql);
		return $this->pdo;
	}
}
