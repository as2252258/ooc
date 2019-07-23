<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/4/9 0009
 * Time: 10:54
 */

namespace Beauty\core;


use Beauty\db\ActiveRecord;

class ArrayIterator extends \ArrayIterator
{
	
	/** @var ActiveRecord */
	public $model;
	
	/**
	 * @param string $index
	 * @return mixed
	 */
	public function offsetGet($index)
	{
		/** @var ActiveRecord $model */
		$model = new $this->model;
		return $model->setAttributes(parent::offsetGet($index));
	}
	
	/**
	 * @param mixed $model
	 */
	public function setModel($model): void
	{
		$this->model = $model;
	}
}
