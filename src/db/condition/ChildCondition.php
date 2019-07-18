<?php


namespace Yoc\db\condition;


class ChildCondition extends Condition
{

	public function builder()
	{
		return $this->column . ' ' . $this->opera . ' (' . $this->value . ')';
	}

}
