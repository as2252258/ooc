<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/4/3 0003
 * Time: 17:04
 */

namespace Beauty\validator;


class LengthValidator extends BaseValidator
{

	const MAX_LENGTH = 'max';
	const MIN_LENGTH = 'min';

	public $method;

	public $value;

	/**
	 * @return bool
	 */
	public function trigger()
	{
		$param = $this->getParams();
		if (empty($param) || !isset($param[$this->field])) {
			if ($this->method != self::MAX_LENGTH) {
				return $this->addError('The param :attribute not exists');
			} else {
				return TRUE;
			}
		}
		$value = $param[$this->field];
		if (is_null($value)) {
			return $this->addError('The param :attribute is null');
		}
		switch (strtolower($this->method)) {
			case self::MAX_LENGTH:
				return $this->maxLength($value);
				break;
			case self::MIN_LENGTH:
				return $this->minLength($value);
				break;
			default:
				return $this->defaultLength($value);
		}
	}

	/**
	 * @param $value
	 * @return bool
	 *
	 * 效验长度是否大于最大长度
	 */
	private function maxLength($value)
	{
		if (is_array($value)) {
			if (count($value) > $value) {
				return $this->addError('The param :attribute length overflow');
			}
		} else {
			if (is_numeric($value) && strlen(floatval($value)) > $this->value) {
				return $this->addError('The param :attribute length overflow');
			}
			if (strlen($value) > $this->value) {
				return $this->addError('The param :attribute length overflow');
			}
		}
		return TRUE;
	}

	/**
	 * @param $value
	 * @return bool
	 *
	 * 效验长度是否小于最小长度
	 */
	private function minLength($value)
	{
		if (is_array($value)) {
			if (count($value) < $value) {
				return $this->addError('The param :attribute length error');
			}
		} else {
			if (is_numeric($value) && strlen(floatval($value)) < $this->value) {
				return $this->addError('The param :attribute length overflow');
			}
			if (strlen($value) < $this->value) {
				return $this->addError('The param :attribute length error');
			}
		}
		return TRUE;
	}

	/**
	 * @param $value
	 * @return bool
	 *
	 * 效验长度是否小于最小长度
	 */
	private function defaultLength($value)
	{
		if (is_array($value)) {
			if (count($value) !== $value) {
				return $this->addError('The param :attribute length error');
			}
		} else {
			if (is_numeric($value) && mb_strlen(floatval($value)) !== $this->value) {
				return $this->addError('The param :attribute length overflow');
			}
			if (mb_strlen($value) !== $this->value) {
				return $this->addError('The param :attribute length error; ' . mb_strlen($value) . ':' . $this->value);
			}
		}
		return TRUE;
	}
}
