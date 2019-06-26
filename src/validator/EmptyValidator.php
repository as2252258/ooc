<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/4/3 0003
 * Time: 15:46
 */
namespace Yoc\validator;


class EmptyValidator extends BaseValidator
{
	
	/** @var string [不能为空] */
	const CAN_NOT_EMPTY = 'not empty';
	
	/** @var string [可为空, 不能为null] */
	const CAN_NOT_NULL = 'not null';
	
	public $method;
	
	/**
	 * @return bool
	 *
	 * 检查参数是否为NULL
	 */
	public function trigger()
	{
		$param = $this->getParams();
		if (empty($param) || !isset($param[$this->field])) {
			return $this->addError(':attribute not exists');
		}
		
		$value = $param[$this->field];
		
		switch (strtolower($this->method)) {
			case self::CAN_NOT_EMPTY:
				if (strlen($value) < 1) {
					return $this->addError('The :attribute can not empty.');
				}
				break;
			case self::CAN_NOT_NULL:
				if ($value === null) {
					return $this->addError('The :attribute can not is null.');
				}
				break;
		}
		
		return true;
	}
}
