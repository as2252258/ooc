<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/4/4 0004
 * Time: 13:58
 */

namespace Yoc\db;

/**
 * Class HasMany
 * @package Yoc\db
 *
 * @method with($name)
 */
class HasMany extends HasBase
{

	/**
	 * @param $name
	 * @param $arguments
	 * @throws \Exception
	 * @return mixed
	 */
	public function __call($name, $arguments)
	{
		$this->model = $this->model->$name(...$arguments);
		return $this;
	}

	/**
	 * @param $name
	 * @return array|null|ActiveRecord
	 * @throws \Exception
	 */
	public function get()
	{
		if(!($this->model instanceof ActiveQuery)){
			return $this->model;
		}
		return $this->model->all();
	}
}
