<?php


namespace Yoc\db\pool;


class Slave extends DbConnection
{
	public $host = '';
	public $username = '';
	public $password = '';


	public $slaveConfig = [];

	private static $master = null;

	/**
	 * @return static
	 */
	public static function getInstance()
	{
		if (static::$master == null) {
			static::$master = new Slave();
		}

		return static::$master;
	}


	/**
	 * @return mixed|void
	 */
	public function getConnectConfig()
	{
		$config = $this->slaveConfig;
		$config[] = [
			'cds' => $this->host,
			'username' => $this->username,
			'password' => $this->password
		];
		return $config[array_rand($config)];
	}

}
