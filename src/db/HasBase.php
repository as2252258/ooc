<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/4/4 0004
 * Time: 15:47
 */

namespace Yoc\db;

/**
 * Class HasBase
 * @package Yoc\db
 *
 * @include Query
 */
abstract class HasBase
{

	/** @var ActiveRecord|Collection */
	protected $data;

	/** @var ActiveQuery */
	protected $model;

	/** @var array */
	protected $collect;

	/** @var ActiveRecord */
	protected $oldModel;

	protected $primaryId;

	protected $forgetKey;

	protected $values = [];

	/**
	 * HasBase constructor.
	 * @param ActiveRecord $model
	 * @param $primaryId
	 * @param string $forgetKey
	 * @param ActiveRecord $oldModel
	 * @throws \Exception
	 */
	public function __construct($model, $primaryId, $value)
	{
		if (is_array($value)) {
			if (empty($value)) $value = [-100];
			$this->model = $model::find()->in($primaryId, $value);
		} else {
			$this->model = $model::find()->where([$primaryId => $value]);
		}
	}

	/**
	 * @return mixed|ActiveQuery
	 */
	public function getModel()
	{
		return $this->model;
	}

	/**
	 * @return mixed|ActiveQuery
	 */
	public function getPrimaryKey()
	{
		return $this->primaryId;
	}

	/**
	 * @return mixed|ActiveQuery
	 */
	public function getForgetKey()
	{
		return $this->forgetKey;
	}

	/**
	 * @return mixed
	 */
	public function getValue()
	{
		return $this->value;
	}

	abstract public function get();

	/**
	 * @param $name
	 * @return mixed
	 */
	public function __get($name)
	{
		return $this->get();
	}
}
