<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/3/30 0030
 * Time: 14:39
 */

namespace Beauty\db;


interface IOrm
{

	/**
	 * @param int $param
	 * @return static
	 */
	public static function findOne($param, $db = NULL);

	/**
	 * @param $dbName
	 * @return Connection
	 */
	public static function setDatabaseConnect($dbName);

}
