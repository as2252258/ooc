<?php


namespace Yoc\db\condition;


class NotLikeCondition extends Condition
{

	public $pos = '';

	public function builder()
	{
		return $this->column . ' NOT LIKE \'%' . $this->value . '%\'';
	}

}
