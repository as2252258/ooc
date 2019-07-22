<?php


namespace Yoc\db;


use Validate\Validate;
use Yoc\error\Logger;
use Yoc\event\Event;

class BeforeSaveEvent extends Event
{


	public $isVild = true;


	/** @var ActiveRecord */
	public $model;

	/**
	 * BeforeSaveEvent constructor.
	 * @param object $model
	 * @param array $config
	 */
	public function __construct($model, array $config = [])
	{
		$this->model = $model;
		parent::__construct($config);
	}


	/**
	 * @return ActiveRecord
	 * @throws \Exception
	 */
	public function handler()
	{
		return $this->dataAssembly();
	}

	/**
	 * @return object|ActiveRecord
	 *
	 * 修改器触发
	 */
	public function dataAssembly()
	{
		$data = $this->model->attributes;
		foreach ($data as $key => $val) {
			$method = 'set' . ucfirst($key) . 'Attribute';
			if (!method_exists($this->model, $method)) {
				continue;
			}
			$this->model->setAttribute($key, $this->model->$method($val));
		}
		return $this->model;
	}

}
