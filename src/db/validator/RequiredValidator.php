<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/4/3 0003
 * Time: 15:47
 */

namespace Beauty\db\validator;


class RequiredValidator extends BaseValidator
{
	
	/**
	 * @return bool
	 * 检查是否存在
	 */
	public function trigger()
	{
		$param = $this->getParams();
		if (empty($param) || !isset($param[$this->field])) {
			return $this->addError('The param :attribute not exists');
		} else {
			return true;
		}
	}
	
}
