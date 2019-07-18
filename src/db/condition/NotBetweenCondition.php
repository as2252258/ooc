<?php


namespace Yoc\db\condition;


class NotBetweenCondition extends Condition
{


	/**
	 * @return string
	 */
	public function builder()
	{
		return implode(' ', [
			$this->column, $this->value[0], 'AND', $this->value[1]
		]);
	}

}
