<?php


namespace Yoc\db\pool;


use Yoc\base\Component;

abstract class ConnectPool extends Component
{

	public $index = 0;

	/** @var \PDO[] $connects */
	protected $connects = [];

	/** @var \PDO[] $useConnect */
	protected $useConnect = [];

	abstract public function createConnect(array $config = []);


	abstract public function getConnectConfig();

	/**
	 * @return bool
	 * @throws \Exception
	 */
	public function isConnect()
	{
		throw new \Exception('You must define connect check callback.');
	}

	/**
	 * @return mixed
	 * @throws \Exception
	 * 获取连接，并且标记使用的序列
	 */
	public function getConnect(): \PDO
	{
		if (isset($this->useConnect[$this->index])) {
			return $this->useConnect[$this->index];
		}

		$this->debug(__LINE__);
		if (empty($this->connects)) {
			return $this->ifNull();
		}

		$this->index = array_rand($this->connects);

		$this->useConnect[$this->index] = $this->connects[$this->index] ?? null;

		if (isset($this->connects[$this->index])) {
			unset($this->connects[$this->index]);
		}

		if (!$this->isConnect()) {
			return $this->ifNull();
		}

		return $this->useConnect[$this->index];
	}

	/**
	 * @return \PDO
	 * @throws \Exception
	 */
	private function ifNull()
	{
		$config = $this->getConnectConfig();

		if (empty($config) || !is_array($config)) {
			throw new \Exception('Db Connect configure must not empty.');
		}

		$this->useConnect[$this->index] = $this->createConnect($config);

		return $this->useConnect[$this->index];
	}

	/**
	 * 释放链接
	 */
	public function release()
	{
		$connect = $this->useConnect[$this->index];

		unset($this->useConnect[$this->index]);

		$this->connects[$this->index] = $connect;

		$this->index = 0;

		$this->debug(__LINE__);
	}

	/**
	 * 释放链接
	 */
	public function disconnect()
	{
		$this->useConnect = [];
		$this->connects = [];
	}

	/**
	 * 回收所有正在使用的链接
	 */
	public function releaseAll()
	{
		$this->debug(__LINE__);
		if (count($this->useConnect) < 1) {
			return $this->index = 0;
		}
		foreach ($this->useConnect as $value) {
			if ($value->inTransaction()) {
				$value->rollBack();
			}
			array_push($this->connects, $value);
		}
		$this->useConnect = [];

		$this->debug(__FILE__);
		return $this->index = 0;
	}


	private function debug($line)
	{
//		$debug = [
//			'待回收数量:' . count($this->useConnect),
//			', 现有数量:' . count($this->connects),
//			'. at line ' . $line
//		];
//		echo implode('', $debug) . PHP_EOL;
	}
}
