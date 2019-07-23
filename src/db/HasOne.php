<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/4/4 0004
 * Time: 13:47
 */

namespace Beauty\db;

/**
 * Class HasOne
 * @package Beauty\db
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
