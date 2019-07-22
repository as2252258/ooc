<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/3/30 0030
 * Time: 14:56
 */

namespace Yoc\db\traits;

use Yoc\db\ActiveQuery;
use Yoc\db\ActiveRecord;

trait QueryTrait
{
	public $where = [];
	public $select = [];
	public $join = [];
	public $order = [];
	public $offset = NULL;
	public $limit = NULL;
	public $group = '';
	public $from = '';
	public $alias = 't1';
	public $filter = [];

	/** @var ActiveRecord */
	public $modelClass;

	/**
	 * @return string
	 * @throws \Exception
	 */
	public function getTable()
	{
		return $this->modelClass::getTable();
	}

	/**
	 * @param $columns
	 * @return $this
	 * @throws \Exception
	 */
	public function filter($columns)
	{
		if (!$columns) {
			return $this;
		}
		if (is_callable($columns, TRUE)) {
			return call_user_func($columns, $this);
		}
		if (is_string($columns)) {
			$columns = explode(',', $columns);
		}
		if (!is_array($columns)) {
			throw new \Exception('Filter paramster must a array!');
		}
		$this->filter = $columns;
		return $this;
	}

	/**
	 * @param string $alias
	 *
	 * @return $this
	 *
	 * select * from tableName as t1
	 */
	public function alias($alias = 't1')
	{
		$this->alias = $alias;
		return $this;
	}

	/**
	 * @param $tableName
	 *
	 * @return $this
	 *
	 */
	public function from($tableName)
	{
		$this->from = $tableName;
		return $this;
	}

	/**
	 * @param string $tableName
	 * @param array|string $on
	 * @param array $param
	 * @return $this
	 * $query->join([$tableName, ['userId'=>'uuvOd']], $param)
	 * $query->join([$tableName, ['userId'=>'uuvOd'], $param])
	 * $query->join($tableName, ['userId'=>'uuvOd',$param])
	 * @throws
	 */
	private function join(string $tableName, $on = NULL, array $param = NULL)
	{
		if (empty($on)) {
			return $this;
		}
		$join[] = $tableName;
		$join[] = 'ON ' . $this->toString($on);

		if (empty($join)) {
			throw new \Exception("If join table and you want, please write table.");
		}

		$this->join[] = implode(' ', $join);

		if (!empty($param)) {
			$this->addParams($param);
		}

		return $this;
	}

	/**
	 * @param $tableName
	 * @param $onCondition
	 * @param null $param
	 * @return $this
	 * @throws \Exception
	 */
	public function leftJoin($tableName, $onCondition, $param = NULL)
	{
		return $this->join(...["LEFT JOIN " . $tableName, $onCondition, $param]);
	}

	/**
	 * @param $tableName
	 * @param $onCondition
	 * @param null $param
	 * @return $this
	 * @throws \Exception
	 */
	public function rightJoin($tableName, $onCondition, $param = NULL)
	{
		return $this->join(...["RIGHT JOIN " . $tableName, $onCondition, $param]);
	}

	/**
	 * @param $tableName
	 * @param $onCondition
	 * @param null $param
	 * @return $this
	 * @throws \Exception
	 */
	public function innerJoin($tableName, $onCondition, $param = NULL)
	{
		return $this->join(...["INNER JOIN " . $tableName, $onCondition, $param]);
	}

	/**
	 * @param $array
	 *
	 * @return string
	 */
	private function toString($array)
	{
		$tmp = [];
		if (!is_array($array)) {
			return $array;
		}
		foreach ($array as $key => $val) {
			if (is_array($val)) {
				$tmp[] = $this->toString($array);
			} else {
				$tmp[] = $this->resolve($key, $val, '=');
			}
		}
		return implode(' AND ', $tmp);
	}

	/**
	 * @param $field
	 *
	 * @return $this
	 */
	public function sum($field)
	{
		$this->select[] = 'SUM(' . $field . ') AS ' . $field;
		return $this;
	}

	/**
	 * @param $field
	 * @return $this
	 */
	public function max($field)
	{
		$this->select[] = 'MAX(' . $field . ') AS ' . $field;
		return $this;
	}

	/**
	 * @param string $lngField
	 * @param string $latField
	 * @param int $lng1
	 * @param int $lat1
	 *
	 * @return $this
	 */
	public function distance(string $lngField, string $latField, int $lng1, int $lat1)
	{
		$sql = "ROUND(6378.138 * 2 * ASIN(SQRT(POW(SIN(($lat1 * PI() / 180 - $lat1 * PI() / 180) / 2),2) + COS($lat1 * PI() / 180) * COS($latField * PI() / 180) * POW(SIN(($lng1 * PI() / 180 - $lngField * PI() / 180) / 2),2))) * 1000) AS distance";
		$this->select[] = $sql;
		return $this;
	}

	/**
	 * @param        $column
	 * @param string $sort
	 *
	 * @return $this
	 *
	 * [
	 *     'addTime',
	 *     'descTime desc'
	 * ]
	 */
	public function orderBy($column, $sort = 'DESC')
	{
		if (empty($column)) {
			return $this;
		}
		if (is_string($column)) {
			return $this->addOrder(...func_get_args());
		}

		foreach ($column as $key => $val) {
			$this->addOrder($val);
		}

		return $this;
	}

	/**
	 * @param        $column
	 * @param string $sort
	 *
	 * @return $this
	 *
	 */
	private function addOrder($column, $sort = 'DESC')
	{
		$column = trim($column);

		if (func_num_args() == 1 || strpos($column, ' ') !== FALSE) {
			$this->order[] = $column;
		} else {
			$this->order[] = "$column $sort";
		}
		return $this;
	}

	/**
	 * @param array|string $column
	 *
	 * @return $this
	 */
	public function select($column = '*')
	{
		if (!is_array($column)) {
			$column = explode(',', $column);
		}
		foreach ($column as $key => $val) {
			array_push($this->select, $val);
		}
		return $this;
	}

	/**
	 * @param        $columns
	 * @param string $oprea
	 * @param null $value
	 *
	 * @return $this|array|ActiveQuery
	 * @throws \Exception
	 */
	public function or($columns, $oprea = '=', $value = NULL)
	{
		$this->where[] = ['or', func_get_args()];
		return $this;
	}

	/**
	 * @param        $columns
	 * @param string $oprea
	 * @param null $value
	 *
	 * @return array|ActiveQuery|mixed
	 * @throws \Exception
	 */
	public function and($columns, $oprea = '=', $value = NULL)
	{
		$this->where[] = func_get_args();
		return $this;
	}

	/**
	 * @param $limit
	 * @return $this
	 */
	public function plunk($limit)
	{
		$this->offset = 0;
		$this->limit = $limit;
		return $this;
	}

	/**
	 * @param array|string $columns
	 * @param string $value
	 *
	 * @return $this
	 */
	public function like($columns, string $value)
	{
		if (empty($columns) || empty($value)) {
			return $this;
		}

		if (is_array($columns)) {
			$columns = 'CONCAT(' . implode(',^,', $columns) . ')';
		}

		$this->where[] = ['like', $columns, trim('%', $value)];

		return $this;
	}

	/**
	 * @param array|string $columns
	 * @param string $value
	 *
	 * @return $this
	 */
	public function lLike($columns, string $value)
	{
		if (empty($columns) || empty($value)) {
			return $this;
		}

		if (is_array($columns)) {
			$columns = 'CONCAT(' . implode(',^,', $columns) . ')';
		}

		$this->where[] = ['llike', $columns, trim('%', $value)];

		return $this;
	}

	/**
	 * @param array|string $columns
	 * @param string $value
	 *
	 * @return $this
	 */
	public function rLike($columns, string $value)
	{
		if (empty($columns) || empty($value)) {
			return $this;
		}

		if (is_array($columns)) {
			$columns = 'CONCAT(' . implode(',^,', $columns) . ')';
		}

		$this->where[] = ['rlike', $columns, trim('%', $value)];

		return $this;
	}


	/**
	 * @param array|string $columns
	 * @param string $value
	 *
	 * @return $this
	 */
	public function notLike($columns, string $value)
	{
		if (empty($columns) || empty($value)) {
			return $this;
		}

		if (is_array($columns)) {
			$columns = 'CONCAT(' . implode(',^,', $columns) . ')';
		}

		$this->where[] = ['not like', $columns, trim('%', $value)];

		return $this;
	}

	/**
	 * @param string $column
	 * @param int $value
	 * @return $this
	 */
	public function eq(string $column, int $value)
	{
		$this->where[] = ['eq', $column, $value];
		return $this;
	}


	/**
	 * @param string $column
	 * @param int $value
	 * @return $this
	 */
	public function neq(string $column, int $value)
	{
		$this->where[] = ['neq', $column, $value];
		return $this;
	}


	/**
	 * @param string $column
	 * @param int $value
	 * @return $this
	 */
	public function gt(string $column, int $value)
	{
		$this->where[] = ['gt', $column, $value];
		return $this;
	}


	/**
	 * @param string $column
	 * @param int $value
	 * @return $this
	 */
	public function ngt(string $column, int $value)
	{
		$this->where[] = ['ngt', $column, $value];
		return $this;
	}


	/**
	 * @param string $column
	 * @param int $value
	 * @return $this
	 */
	public function lt(string $column, int $value)
	{
		$this->where[] = ['lt', $column, $value];
		return $this;
	}

	/**
	 * @param string $column
	 * @param int $value
	 * @return $this
	 */
	public function elt(string $column, int $value)
	{
		$this->where[] = ['elt', $column, $value];
		return $this;
	}

	/**
	 * @param $columns
	 * @param $value
	 *
	 * @return $this
	 */
	public function in($columns, $value)
	{
		$this->where[] = ['in', $columns, $value];
		return $this;
	}

	/**
	 * @param $columns
	 * @param $value
	 *
	 * @return $this
	 */
	public function notIn($columns, $value)
	{
		$this->where[] = ['not in', $columns, $value];
		return $this;
	}

	/**
	 * @param string $column
	 * @param string $start
	 * @param string $end
	 *
	 * @return $this
	 */
	public function between(string $column, string $start, string $end)
	{
		if (empty($column) || empty($start) || empty($end)) {
			return $this;
		}

		$this->where[] = ['between', $column, [$start, $end]];

		return $this;
	}

	/**
	 * @param string $column
	 * @param string $start
	 * @param string $end
	 *
	 * @return $this
	 */
	public function notBetween(string $column, string $start, string $end)
	{
		if (empty($column) || empty($start) || empty($end)) {
			return $this;
		}

		$this->where[] = ['not between', $column, [$start, $end]];

		return $this;
	}

	/**
	 * @param array $params
	 *
	 * @return $this
	 */
	public function bindParams(array $params = [])
	{
		if (empty($params)) {
			return $this;
		}
		$this->attributes = $params;
		return $this;
	}

	/**
	 * @param        $columns
	 * @param string $oprea
	 * @param null $value
	 * @param string $or
	 *                  [field, opreat, value]
	 *
	 * @return $this|array|ActiveQuery|string
	 * @throws
	 */
	public function where($columns, $oprea = '=', $value = NULL, $or = 'AND')
	{
		$args = current(func_get_args());
		if (empty($args)) {
			return $this;
		}
		$math = ['in', 'like', '>', '<', '>=', '<=', '<>', 'between'];
		if (count($args) == 3 && in_array($args[1], $math)) {
			$this->where[] = [$args[1], $args[0], $args[2]];
		} else {
			$this->where[] = func_get_args();
		}
		return $this;
	}

	/**
	 * @param string $name
	 *
	 * @return $this
	 */
	public function groupBy(string $name, $having = NULL)
	{
		$this->group = $name;
		if (!empty($having)) {
			return $this;
		}
		if (is_array($having)) {
			$query = [];
			foreach ($having as $key => $val) {
				$query[] = $this->resolve($key, $val, '=');
			}
			$this->group .= ' HAVING ' . implode(' AND ', $query);
		} else {
			$this->group .= ' HAVING ' . $having;
		}
		return $this;
	}

	/**
	 * @param int $offset
	 * @param int $limit
	 *
	 * @return $this
	 */
	public function limit(int $offset, int $limit = 20)
	{
		$this->offset = $offset;
		$this->limit = $limit;
		return $this;
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
	public function resolve($column, $value = null, $oprea = '=')
	{
		if ($value === NULL || $value === '') {
			return '';
		}
		if (trim($oprea) == 'like') {
			return $column . $oprea . '\'%' . $value . '%\'';
		}
		if (is_numeric($value)) {
			$str = $column . $oprea . $value;
		} else {
			$str = $column . $oprea . '\'' . $value . '\'';
		}
		return $str;
	}
}
