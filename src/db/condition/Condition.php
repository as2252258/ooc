<?php


namespace Beauty\db\condition;


use Beauty\base\BObject;

abstract class Condition extends BObject
{

	protected $column = '';
	protected $opera = '';
	protected $value = [];

	const INT_TYPE = ['bit', 'bool', 'tinyint', 'smallint', 'mediumint', 'int', 'bigint', 'float', 'double', 'decimal', 'timestamp'];

	abstract public function builder();

	/**
	 * @param string $column
	 */
	public function setColumn(string $column): void
	{
		$this->column = $column;
	}

	/**
	 * @param string $opera
	 */
	public function setOpera(string $opera): void
	{
		$this->opera = $opera;
	}

	/**
	 * @param $value
	 */
	public function setValue($value): void
	{
		$this->value = $value;
	}

	/**
	 * @param $column
	 * @param $value
	 * @param $oprea
	 *
	 * @return string
	 *
	 * $query = new Build();
	 * $query->where('id', '2');
	 * $query->where(['id' => 3]);
	 * $query->where('id', '<', 4);
	 * $query->orWhere('id', '=', 5);
	 * $query->orWhere('id', '=', 6);
	 * $query->ANDWhere('id', '=', 7);
	 * $sql = '(((id=2 AND id=3 AND id<4) OR id=5) OR id=6) AND i(d=7)';
	 */
	protected function resolve($column, $value = null, $oprea = '=')
	{
		if ($value === NULL || $value === '') {
			return '';
		}
		if (trim($oprea) == 'like') {
			return $column . ' ' . $oprea . ' \'%' . $value . '%\'';
		}

		$columns = $this->column[$column] ?? '';
		if (empty($columns)) {
			return $this->typeBuilder($column, $value, $oprea);
		}

		$explode = explode('(', $columns);
		$explode = array_shift($explode);
		if (strpos($explode, ' ') !== false) {
			$explode = explode(' ', $explode)[0];
		}

		if (!in_array(trim($explode), self::INT_TYPE)) {
			$str = $column . ' ' . $oprea . ' \'' . $value . '\'';
		} else {
			$str = $column . ' ' . $oprea . ' ' . $value;
		}
		return $str;
	}


	/**
	 * @param $column
	 * @param null $value
	 * @param string $oprea
	 * @return string
	 */
	private function typeBuilder($column, $value = null, $oprea = '=')
	{
		if (!is_numeric($value)) {
			return $column . ' ' . $oprea . ' \'' . $value . '\'';
		} else {
			return $column . ' ' . $oprea . ' ' . $value;
		}
	}

}
