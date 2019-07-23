<?php


namespace Beauty\validator;

use Beauty\db\ActiveRecord;

class Validator extends BaseValidator
{

	/** @var BaseValidator[] */
	private $validators = [];

	/** @var Validator */
	private static $instance = null;

	protected $classMap = [
		'not empty' => [
			'class' => 'Beauty\validator\EmptyValidator',
			'method' => EmptyValidator::CAN_NOT_EMPTY,
		],
		'not null' => [
			'class' => 'Beauty\validator\EmptyValidator',
			'method' => EmptyValidator::CAN_NOT_NULL,
		],
		'required' => [
			'class' => 'Beauty\validator\RequiredValidator',
		],
		'unique' => [
			'class' => 'Beauty\validator\UniqueValidator',
		],
		'datetime' => [
			'class' => 'Beauty\validator\DatetimeValidator',
			'method' => DateTimeValidator::DATE_TIME,
		],
		'date' => [
			'class' => 'Beauty\validator\DatetimeValidator',
			'method' => DateTimeValidator::DATE,
		],
		'time' => [
			'class' => 'Beauty\validator\DatetimeValidator',
			'method' => DateTimeValidator::TIME,
		],
		'timestamp' => [
			'class' => 'Beauty\validator\DatetimeValidator',
			'method' => DateTimeValidator::STR_TO_TIME,
		],
		'string' => [
			'class' => 'Beauty\validator\TypesOfValidator',
			'method' => TypesOfValidator::STRING,
		],
		'int' => [
			'class' => 'Beauty\validator\TypesOfValidator',
			'method' => TypesOfValidator::INTEGER,
		],
		'json' => [
			'class' => 'Beauty\validator\TypesOfValidator',
			'method' => TypesOfValidator::JSON,
		],
		'float' => [
			'class' => 'Beauty\validator\TypesOfValidator',
			'method' => TypesOfValidator::FLOAT,
		],
		'array' => [
			'class' => 'Beauty\validator\TypesOfValidator',
			'method' => TypesOfValidator::ARRAY,
		],
		'serialize' => [
			'class' => 'Beauty\validator\TypesOfValidator',
			'method' => TypesOfValidator::SERIALIZE,
		],
		'maxLength' => [
			'class' => 'Beauty\validator\LengthValidator',
			'method' => 'max',
		],
		'minLength' => [
			'class' => 'Beauty\validator\LengthValidator',
			'method' => 'min',
		],
		'email' => [
			'class' => 'Beauty\validator\EmailValidator',
			'method' => 'email',
		],
		'length' => [
			'class' => 'Beauty\validator\LengthValidator',
			'method' => 'default',
		],
	];

	/**
	 * @return Validator
	 */
	public static function getInstance()
	{
		if (static::$instance == null) {
			static::$instance = new Validator();
		}
		return static::$instance;
	}

	/**
	 * @param $field
	 * @param $rules
	 * @param $model
	 * @return $this
	 * @throws \Exception
	 */
	public function make($field, $rules)
	{
		if (!is_array($field)) {
			$field = [$field];
		}

		foreach ($field as $val) {
			$this->createRule($val, $rules);
		}

		return $this;
	}

	/**
	 * @param $field
	 * @param $rule
	 * @param ActiveRecord $model
	 * @throws \Exception
	 */
	public function createRule($field, $rule)
	{
		$define = ['field' => $field];
		foreach ($rule as $key => $val) {
			$type = strtolower($val);

			if (!is_numeric($key)) {
				$type = strtolower($key);
				$define['value'] = $val;
			}

			if (!isset($this->classMap[$type])) {
				continue;
			}

			$constr = array_merge($this->classMap[$type], $define);
			$class = \Beauty::createObject($constr);
			$class->setParams($this->getParams());

			$this->validators[] = $class;
		}
	}

	/**
	 * @return bool
	 * @throws \Exception
	 */
	public function validation()
	{
		if (count($this->validators) < 1) {
			return true;
		}

		$isTrue = true;
		foreach ($this->validators as $val) {
			if ($val->trigger()) {
				continue;
			};
			$isTrue = $this->addError($val->getError());
			break;
		}

		$this->validators = [];
		return $isTrue;
	}

}
