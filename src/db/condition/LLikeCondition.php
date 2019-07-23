<?php


namespace Beauty\db\condition;


class LLikeCondition extends Condition
{

	public $pos = '';

	public function builder()
	{
		return $this->column . ' LIKE \'%' . $this->value . '\'';
	}

}
