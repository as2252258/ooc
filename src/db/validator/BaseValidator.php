<?php

namespace Yoc\db\validator;


use Yoc\db\ActiveRecord;

abstract class BaseValidator
{

	public $field;

	public $rules;

	public $method;

	protected $isFail = TRUE;

	protected $message = '';

	protected $params = [];

	/** @var ActiveRecord */
	protected $model;

	public function setModel($model)
	{
		$this->model = $model;
	}

	public function __construct($config = [])
	{
		$this->regConfig($config);
	}

	private function regConfig($config)
	{
		if (empty($config) || !is_array($config)) {
			return;
		}
		foreach ($config as $key => $val) {
			$this->$key = $val;
		}
	}

	/**
	 * @throws \Exception
	 * @return bool
	 */
	public function trigger()
	{
    	throw new \Exception('Child Class must define method of trigger');
	}

	/**
	 * @return mixed
	 */
	protected function getParams()
	{
		return $this->params;
	}

	/**
	 * @param $data
	 * @return $this
	 */
	public function setParams($data)
	{
		$this->params = $data;
		return $this;
	}

	/**
	 * @param $message
	 * @return bool
	 */
	protected function addError($message)
	{
		$this->isFail = FALSE;

		$message = str_replace(':attribute', $this->field, $message);

		$this->message = $message;

		return $this->isFail;
	}

	/**
	 * @return string
	 */
	public function getError()
	{
		return $this->message;
	}

	/**
	 * @param $name
	 * @param $value
	 * @throws \Exception
	 */
	public function __set($name, $value)
	{
		$method = 'set' . ucfirst($name);
		if (method_exists($this, $method)) {
			$this->$method($value);
		} else if (property_exists($this, $name)) {
			$this->$name = $value;
		} else {
			throw new \Exception('unknown property ' . $name . ' in class ' . get_called_class());
		}
	}
}
