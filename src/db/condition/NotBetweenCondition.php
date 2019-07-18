<?php


namespace Yoc\db\condition;


class NotBetweenCondition extends Condition
{


	/**
	 * @return string
	 */
	public function builder()
	{
		return $this->column . ' NOT BETWEEN ' . $this->value[0] . ' AND ' . $this->value[1];
	}

}
