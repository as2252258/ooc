<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/4/3 0003
 * Time: 15:42
 */

namespace Beauty\db\validator;


class DateTimeValidator extends BaseValidator
{
	
	const DATE = 'date';
	const DATE_TIME = 'datetime';
	const TIME = 'time';
	const STR_TO_TIME = 'timestamp';
	
	public $method;
	
	/**
	 * @return bool
	 */
	public function trigger()
	{
		$param = $this->getParams();
		if (empty($param) || !is_array($param)) {
			return true;
		}
		if (!isset($param[$this->field]) || empty($param[$this->field])) {
			return true;
		}
		$value = $param[$this->field];
		switch (strtolower($this->method)) {
			case self::DATE:
				return $this->validatorDate($value);
				break;
			case self::DATE_TIME:
				return $this->validateDatetime($value);
				break;
			case self::TIME:
				return $this->validatorTime($value);
				break;
			case self::STR_TO_TIME:
				return $this->validatorTimestamp($value);
				break;
			default:
				return true;
		}
	}
	
	/**
	 * @param $value
	 * @return bool
	 *
	 * 效验分秒 格式如  01:02 or 01-02
	 */
	public function validatorTime($value)
	{
		if (empty($value) || !is_string($value)) {
			return $this->addError('The param :attribute not is a date value');
		}
		$match = preg_match('/^[0-5]?\d{1}.{1}[0-5]?\d{1}$/', $value, $result);
		if ($match && $result[0] == $value) {
			return true;
		} else {
			return $this->addError('The param :attribute format error');
		}
	}
	
	
	/**
	 * @param $value
	 * @return bool
	 *
	 * 效验分秒 格式如 2017-12-22 01:02
	 */
	public function validateDatetime($value)
	{
		if (empty($value) || !is_string($value)) {
			return $this->addError('The param :attribute not is a date value');
		}
		$match = '/^\d{4}\-\d{2}\-\d{2}\s+\d{2}:\d{2}:\d{2}$/';
		$match = preg_match($match, $value, $result);
		if ($match && $result[0] == $value) {
			return true;
		} else {
			return $this->addError('The param :attribute format error');
		}
	}
	
	/**
	 * @param $value
	 * @return bool
	 *
	 * 效验分秒 格式如  2017-12-22
	 */
	public function validatorDate($value)
	{
		if (empty($value) || !is_string($value)) {
			return $this->addError('The param :attribute not is a date value');
		}
		$match = preg_match('/^(\d{4}).*([0-12]).*([0-31]).*$/', $value, $result);
		if ($match && $result[0] == $value) {
			return true;
		} else {
			return $this->addError('The param :attribute format error');
		}
	}
	
	/**
	 * @param $value
	 * @return bool
	 *
	 * 效验时间戳 格式如  1521452254
	 */
	public function validatorTimestamp($value)
	{
		if (empty($value) || !is_numeric($value)) {
			return $this->addError('The param :attribute not is a timestamp value');
		}
		if (strlen((string)$value) != 10) {
			return $this->addError('The param :attribute not is a timestamp value');
		}
		if (!date('YmdHis', $value)) {
			return $this->addError('The param :attribute format error');
		}
		return true;
	}
}
