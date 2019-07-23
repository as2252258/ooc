<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/6/27 0027
 * Time: 17:49
 */

namespace Beauty\db;


use Beauty\db\traits\QueryTrait;

class Sql
{
	
	use QueryTrait;
	
	/**
	 * @return string
	 * @throws \Exception
	 */
	public function getSql()
	{
		return (new QueryBuilder())->builder($this);
	}
}
