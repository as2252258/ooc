<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/4/4 0004
 * Time: 18:44
 */

namespace Yoc\validator;


class TypesOfValidator extends BaseValidator
{


	const JSON = 'json';
	const FLOAT = 'float';
	const ARRAY = 'array';
	const STRING = 'string';
	const INTEGER = 'integer';
	const SERIALIZE = 'serialize';

	/** @var array */
	public $types = [
		self::JSON => 'json',
		self::FLOAT => 'float',
		self::ARRAY => 'array',
		self::STRING => 'string',
		self::INTEGER => 'integer',
		self::SERIALIZE => 'serialize',
	];

	/** @var string */
	public $method;


	/**
	 * @return bool
	 */
	public function trigger()
	{
		if (!in_array($this->method, $this->types)) {
			return true;
		}

		$param = $this->getParams();
		if (empty($param) || !isset($param[$this->field])) {
			return true;
		}

		$value = $param[$this->field];

		$method = $this->method . 'Format';

		if ($value === null) {
			return $this->addError('This ' . $this->field . ' is not an empty data.');
		}

		return $this->{$method}($value);
	}

	/**
	 * @param $value
	 * @return bool
	 */
	public function jsonFormat($value)
	{
		if (!is_string($value) || is_numeric($value)) {
			return $this->addError('The ' . $this->field . ' not is JSON data.');
		}
		if (is_null(json_decode($value))) {
			return $this->addError('The ' . $this->field . ' not is JSON data.');
		}
		return true;
	}

	/**
	 * @param $value
	 * @return bool
	 */
	public function serializeFormat($value)
	{
		if (!is_string($value) || is_numeric($value)) {
			return $this->addError('The ' . $this->field . ' not is serialize data.');
		}
		if (false === unserialize($value)) {
			return $this->addError('The ' . $this->field . ' not is serialize data.');
		}
		return true;
	}

	/**
	 * @param $value
	 * @return bool
	 */
	public function arrayFormat($value)
	{
		if (!is_array($value)) {
			return $this->addError('The ' . $this->field . ' not is array data.');
		}
		return true;
	}

	/**
	 * @param $value
	 * @return bool
	 */
	public function stringFormat($value)
	{
		if (!is_string($value)) {
			return $this->addError('The ' . $this->field . ' not is string data.');
		}
		return true;
	}

	/**
	 * @param $value
	 * @return bool
	 */
	public function integerFormat($value)
	{
		$trim = trim($value);
		if (!is_numeric($trim)) {
			return $this->addError('The ' . $this->field . ' not is number data.');
		}
		if ($trim != $value || intval($trim) != $value) {
			return $this->addError('The ' . $this->field . ' not is number data.');
		}
		return true;
	}

	/**
	 * @param $value
	 * @return bool
	 */
	public function floatFormat($value)
	{
		$trim = trim($value);
		if (floatval($trim) != $value || !is_float($trim)) {
			return $this->addError('The ' . $this->field . ' not is float data.');
		}
		return true;
	}

}
