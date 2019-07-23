<?php


namespace Beauty\db\pool;

/**
 * Class MasterConnect
 * @package Beauty\pool
 *
 * \Db::table()
 *
 *
 *
 */
class Master extends DbConnection
{

	public $host = '';
	public $username = '';
	public $password = '';


	public $masterConfig = [];

	private static $master = null;

	/**
	 * @return static
	 */
	public static function getInstance()
	{
		if (static::$master == null) {
			static::$master = new Master();
		}

		return static::$master;
	}

	/**
	 * @return mixed|void
	 */
	public function getConnectConfig()
	{
		$config = $this->masterConfig;
		$config[] = [
			'cds' => $this->host,
			'username' => $this->username,
			'password' => $this->password
		];
		return $config[array_rand($config)];
	}

}
