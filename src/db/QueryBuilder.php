<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/4/18 0018
 * Time: 10:40
 */

namespace Yoc\db;


use function Amp\first;
use Yoc\base\BObject;

class QueryBuilder extends BObject
{

	const UPDATE = 'update';
	const INSERT = 'insert';
	const COUNT = 'count';
	const EXISTS = 'exists';
	const INT_TYPE = ['bit', 'bool', 'tinyint', 'smallint', 'mediumint', 'int', 'bigint', 'float', 'double', 'decimal', 'timestamp'];

	/** @var */
	public $column;

	/**
	 * @param mixed|\Db|Sql $query
	 * @return string
	 * @throws \Exception
	 */
	public function builder($query)
	{
		if (empty($query->from)) {
			$query->from = $query->getTable();
		}

		$builder = $this->builderSelect($query->select);
		if (!empty($query->from)) {
			$builder .= $this->builderFrom($query->from);
		}
		if (!empty($query->alias)) {
			$builder .= $this->builderAlias($query->alias);
		}
		if (!empty($query->join)) {
			$builder .= $this->builderJoin($query->join);
		}
		if (!empty($query->where)) {
			$builder .= $this->builderWhere($query->where);
		}
		if (!empty($query->group)) {
			$builder .= $this->builderGroup($query->group);
		}
		if (!empty($query->order)) {
			$builder .= $this->builderOrder($query->order);
		}

		$builder .= $this->builderLimit($query->offset, $query->limit);

		return $builder;
	}

	/**
	 * @param mixed|\Db $query
	 * @return string
	 * @throws \Exception
	 */
	public function count($query)
	{
		if (empty($query->from)) {
			$query->from = $query->getTable();
		}
		$builder = $this->builderSelect('COUNT(*)');
		$builder .= $this->builderFrom($query->from);
		$builder .= $this->builderAlias($query->alias);
		$builder .= $this->builderJoin($query->join);
		$builder .= $this->builderWhere($query->where);
		$builder .= $this->builderGroup($query->group);

		echo $builder . PHP_EOL;

		return $builder;
	}

	/**
	 * @param mixed $query
	 * @return string
	 * @throws \Exception
	 */
	public function delete($query)
	{
		return 'DELETE FROM ' . $query->getTable() . $this->builderWhere($query->where);
	}

	public function getWhere($query)
	{
		return $this->builderWhere($query->where);
	}

	/**
	 * @param ActiveRecord $table
	 * @param $attributes
	 * @param $condition
	 * @param $params
	 * @param $columns
	 * @return string
	 * @throws \Exception
	 */
	public function update($table, $attributes, $condition, &$params, $columns)
	{
		$this->column = $columns;
		$sql = "UPDATE {$table::getTable()} SET ";
		if (empty($params)) {
			throw new \Exception("Not has update values.");
		}

		$_tmp = [];
		foreach ($attributes as $val) {
			if (!isset($params[$val])) {
				continue;
			}
			$_tmp[] = $val . '=:' . $val;
		}
		if (!empty($_tmp)) {
			$sql .= implode(',', $_tmp) . $this->builderWhere($condition);
		}

		return $sql;
	}

	/**
	 * @param $table
	 * @param $params
	 * @param $condition
	 * @return string
	 * @throws \Exception
	 */
	public function incrOrDecr($table, &$params, $condition)
	{
		$_tmp = $newParam = [];
		$sql = "UPDATE {$table::getTable()} SET ";
		if (isset($params['incr']) && is_array($params['incr'])) {
			foreach ($params['incr'] as $key => $val) {
				$_tmp[] = $key . '=' . $key . ' + ' . $val;
				if (!is_numeric($val)) {
					throw new \Exception('Incr And Decr action. The value must a numeric.');
				}
			}
		}

		if (isset($params['decr']) && is_array($params['decr'])) {
			foreach ($params['decr'] as $key => $val) {
				$_tmp[] = $key . '=' . $key . ' - ' . $val;
				if (!is_numeric($val)) {
					throw new \Exception('Incr And Decr action. The value must a numeric.');
				}
			}
		}

		if (empty($_tmp)) {
			throw new \Exception("Not has IncrBy or DecrBy values.");
		}

		$params = [];

		return $sql . implode(',', $_tmp) . $this->builderWhere($condition);
	}

	/**
	 * @param $table
	 * @param array $params
	 * @return string
	 */
	public function insertOrUpdateByDUPLICATE($table, array $params)
	{
		$keys = implode(',', array_keys($params));

		$onValues = [];
		$values = array_values($params);
		foreach ($values as $key => $val) {
			$onValues[] = is_numeric($val) ? $val : '\'' . $val . '\'';
		}

		$onUpdates = [];
		foreach ($params as $key => $val) {
			$onUpdates[] = $key . '=' . (is_numeric($val) ? $val : '\'' . $val . '\'');
		}

		return 'INSERT INTO ' . $table . '(' . $keys . ') VALUES (' . implode(',', $onValues) . ') ON DUPLICATE KEY UPDATE ' . implode(',', $onUpdates);
	}

	/**
	 * @param $table
	 * @param $attributes
	 * @param array|null $params
	 * @return string
	 * @throws \Exception
	 */
	public function insert($table, $attributes, array $params = NULL)
	{
		$sql = "INSERT INTO {$table}(" . implode(',', $attributes) . ') VALUES(:' . implode(',:', $attributes) . ')';
		if (empty($params)) {
			throw new \Exception("");
		}
		foreach ($params as $key => $val) {
			if (strpos($sql, ':' . $key) === FALSE) {
				throw new \Exception("save data param not find.");
			}
		}
		return $sql;
	}


	/**
	 * @param $table
	 * @param $attributes
	 * @param array|NULL $params
	 * @return array
	 * @throws \Exception
	 */
	public function all($table, $attributes, array $params = NULL)
	{
		$sql = "INSERT INTO {$table}(" . implode(',', $attributes) . ') VALUES';
		if (empty($params)) {
			throw new \Exception("save data param not find.");
		}
		$insert = [];
		$insertData = [];

		foreach ($params as $key => $val) {
			if (!is_array($val)) {
				continue;
			}
			array_push($insert, '(:' . implode($key . ',:', $attributes) . $key . ')');

			foreach ($attributes as $myVal) {
				$insertData[':' . $myVal . $key] = $val[$myVal];
			}
		}
		if (empty($insertData) || empty($insert)) {
			throw new \Exception("save data is empty.");
		}
		$sql .= implode(',', $insert);

		return [$sql, $insertData];
	}

	/**
	 * @param $table
	 * @return string
	 */
	public function getColumn($table)
	{
		return 'SHOW FULL FIELDS FROM ' . $table;
	}

	/**
	 * @return string
	 */
	private function builderSelect($select = NULL)
	{
		if (empty($select)) {
			return "SELECT *";
		}
		if (is_array($select)) {
			return "SELECT " . implode(',', $select);
		} else {
			return "SELECT " . $select;
		}
	}

	/**
	 * @return string
	 */
	private function builderAlias($alias)
	{
		return " AS " . $alias;
	}

	/**
	 * @return string
	 * @throws \Exception
	 */
	private function builderFrom($table)
	{
		if (preg_match('/^\{\{.*\}\}$/', $table)) {
			$prefix = \Yoc::$app->db->tablePrefix;

			return " FROM " . $prefix . trim($table, '{{%}}');
		} else {
			return " FROM " . $table;
		}
	}

	/**
	 * @return string
	 */
	private function builderJoin($join)
	{
		return (empty($join) ? '' : ' ' . implode(' ', $join));
	}

	/**
	 * @param $where
	 * @return string
	 */
	private function builderWhere($where)
	{
		if (is_string($where)) {
			return $where;
		}

		$_tmp = $this->addArrayCondition($where);
		$array = [];
		foreach ($_tmp as $key => $val) {
			if (is_array($val)) {
				$array[] = $val[1];
			} else {
				$array[] = $val;
			}
		}

		if (!empty($array)) {
			return ' WHERE (' . implode(') AND (', $array) . ')';
		} else {
			return '';
		}
	}

	/**
	 * @return string
	 */
	private function builderGroup($group)
	{
		if (!empty($group)) {
			return " GROUP BY $group";
		} else {
			return '';
		}
	}

	/**
	 * @return string
	 * @throws \Exception
	 */
	private function builderOrder($order)
	{

		if (!empty($order)) {
			return ' ORDER BY ' . implode(',', $order);
		} else {
			return '';
		}
	}

	/**
	 * @return string
	 */
	private function builderLimit($offset, $limit)
	{
		if (!empty($limit)) {
			return ' LIMIT ' . $offset . ',' . $limit;
		} else {
			return '';
		}
	}


	/**
	 * @param array $array
	 *
	 * @return array
	 * @throws
	 */
	private function addArrayCondition(array $array)
	{
		if (empty($array)) {
			return [];
		}
		$_tmp = [];
		$condition = ['like', 'in', 'or', '>', '<', '<=', '>=', '<>'];
		if (in_array(($array[0] ?? ''), $condition)) {
			$_tmp[] = $this->builderLike($array);
		} else {
			$_tmp = $this->eachCondition($array, $_tmp);
		}

		$_tmp = array_filter($_tmp);

		return $_tmp;
	}


	/**
	 * @param $array
	 * @param $_tmp
	 * @return array
	 * @throws \Exception
	 */
	private function eachCondition($array, $_tmp)
	{
		foreach ($array as $key => $val) {
			if ($val === NULL) continue;

			if (is_array($val)) {
				$_o = $this->addArrayCondition($val);
			} else if (is_string($key)) {
				$_o = $this->resolve($key, $val);
			} else {
				$_o = $val;
			}
			if (!is_array($_o)) {
				$_o = [$_o];
			}
			$_tmp = array_merge($_tmp, $_o);
		}
		return $_tmp;
	}

	/**
	 * @param $array
	 * @return mixed
	 * @throws \Exception
	 */
	private function builderLike($array)
	{
		$_tmp = [];
		if (is_array($array[1])) {
			list($columns, $valus) = $array[1];
			$array[1] = $columns;
			$array[2] = $valus;
		}
		if ($array[0] == 'in') {
			if (!is_array($array[2])) {
				return null;
			}
			$_tmp[] = $array[1] . ' in (' . implode(',', $array[2]) . ')';
		} else if ($array[0] == 'like') {
			$_tmp[] = $array[1] . ' like \'%' . $array[2] . '%\'';
		} else if ($array[0] == 'or') {
			$_tmp[] = $this->resolve($array[1], $array[2]);
		} else if (isset($array[2]) && $array[2] instanceof ActiveQuery) {
			$values = $array[2]->adaptation();

			$_tmp[] = $array[1] . ' ' . $array[0] . ' (' . $values . ')';
		} else {
			$_tmp[] = $this->resolve($array[1], $array[2], $array[0]);
		}
		return [$array[0], array_shift($_tmp)];
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
