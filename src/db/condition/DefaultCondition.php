<?php


namespace Beauty\db\condition;


class DefaultCondition extends Condition
{

	public function builder()
	{
		return $this->resolve($this->column, $this->value, $this->opera);
	}

}
