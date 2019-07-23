<?php


namespace Beauty\db\condition;


class InCondition extends Condition
{


	/**
	 * @return string
	 */
	public function builder()
	{

		$format = array_filter($this->format($this->value));
		if (empty($format)) {
			return '';
		}

		return '`' . $this->column . '` in(' . implode(',', $format) . ')';
	}


	/**
	 * @param array $param
	 * @return array
	 */
	private function format($param)
	{
		$_tmp = [];
		if (!is_array($param)) {
			return null;
		}
		foreach ($param as $value) {
			if ($value === null) {
				continue;
			}
			if (is_numeric($value)) {
				$_tmp[] = $value;
			} else {
				$_tmp[] = '\'' . $value . '\'';
			}
		}
		return $_tmp;
	}

}
