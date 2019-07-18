<?php


namespace Yoc\db\condition;


class MathematicsCondition extends Condition
{

	public $type = '';


	/**
	 *     * EQ    等于（=）
	 * NEQ    不等于（<>）
	 * GT    大于（>）
	 * EGT    大于等于（>=）
	 * LT    小于（<）
	 * ELT    小于等于（<=）
	 */
	public function builder()
	{
		return $this->column . $this->opera . $this->value;
	}

}
