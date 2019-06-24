<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/4/4 0004
 * Time: 13:47
 */

namespace Yoc\db;

/**
 * Class HasOne
 * @package Yoc\db
 * @internal Query
 */
class HasOne extends HasBase
{

	/**
	 * @param $name
	 * @param $arguments
	 * @return $this
	 * @throws \Exception
	 */
	public function __call($name, $arguments)
	{
		$this->model = $this->model->$name(...$arguments);
		return $this;
	}

	/**
	 * @return array|null|ActiveRecord
	 * @throws \Exception
	 */
	public function get()
	{
		if (!($this->model instanceof ActiveQuery)) {
			return $this->model;
		}
		return $this->model->first();
	}
}
