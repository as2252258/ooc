<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/4/18 0018
 * Time: 10:40
 */

namespace Beauty\db;


use function Amp\first;
use Beauty\base\BObject;
use Beauty\db\condition\BetweenCondition;
use Beauty\db\condition\ChildCondition;
use Beauty\db\condition\Condition;
use Beauty\db\condition\DefaultCondition;
use Beauty\db\condition\InCondition;
use Beauty\db\condition\LikeCondition;
use Beauty\db\condition\LLikeCondition;
use Beauty\db\condition\MathematicsCondition;
use Beauty\db\condition\NotBetweenCondition;
use Beauty\db\condition\NotInCondition;
use Beauty\db\condition\NotLikeCondition;
use Beauty\db\condition\RLikeCondition;
use Beauty\db\traits\QueryTrait;

class QueryBuilder extends BObject
{

	const UPDATE = 'update';
	const INSERT = 'insert';
	const COUNT = 'count';
	const EXISTS = 'exists';
	const INT_TYPE = ['bit', 'bool', 'tinyint', 'smallint', 'mediumint', 'int', 'bigint', 'float', 'double', 'decimal', 'timestamp'];

	/**
	 * @var array
	 * EQ    等于（=）
	 * NEQ    不等于（<>）
	 * GT    大于（>）
	 * EGT    大于等于（>=）
	 * LT    小于（<）
	 * ELT    小于等于（<=）
	 * LIKE    模糊查询
	 * [NOT] BETWEEN    （不在）区间查询
	 * [NOT] IN    （不在）IN 查询
	 */
	private $conditionMap = [
		'IN' => InCondition::class,
		'NOT IN' => NotInCondition::class,
		'LIKE' => LikeCondition::class,
		'NOT LIKE' => NotLikeCondition::class,
		'LLike' => LLikeCondition::class,
		'RLike' => RLikeCondition::class,
		'EQ' => [
			'class' => MathematicsCondition::class,
			'type' => 'EQ'
		],
		'NEQ' => [
			'class' => MathematicsCondition::class,
			'type' => 'NEQ'
		],
		'GT' => [
			'class' => MathematicsCondition::class,
			'type' => 'GT'
		],
		'EGT' => [
			'class' => MathematicsCondition::class,
			'type' => 'EGT'
		],
		'LT' => [
			'class' => MathematicsCondition::class,
			'type' => 'LT'
		],
		'ELT' => [
			'class' => MathematicsCondition::class,
			'type' => 'ELT'
		],
		'BETWEEN' => BetweenCondition::class,
		'NOT BETWEEN' => NotBetweenCondition::class,
	];

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
		$builder .= $this->builderFrom($query->from);
		$builder .= $this->builderAlias($query->alias);
		$builder .= $this->builderJoin($query->join);
		$builder .= $this->builderWhere($query->where);
		$builder .= $this->builderGroup($query->group);
		$builder .= $this->builderOrder($query->order);
		$builder .= $this->builderLimit($query);

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

		$where = $this->builderWhere($condition);
		if (!empty($_tmp)) {
			$sql .= implode(',', $_tmp) . $where;
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
			$_tmp = $this->gz($params['incr'], ' + ', $_tmp);
		}

		if (isset($params['decr']) && is_array($params['decr'])) {
			$_tmp = $this->gz($params['decr'], ' - ', $_tmp);
		}

		if (empty($_tmp)) {
			throw new \Exception("Not has IncrBy or DecrBy values.");
		}

		$params = [];

		return $sql . implode(',', $_tmp) . $this->builderWhere($condition);
	}

	/**
	 * @param $params
	 * @param $op
	 * @param array $_tmp
	 * @return array
	 * @throws \Exception
	 */
	private function gz($params, $op, $_tmp)
	{
		$message = 'Incr And Decr action. The value must a numeric.';
		foreach ($params as $key => $val) {
			$_tmp[] = $key . '=' . $key . $op . $val;
			if (!is_numeric($val)) {
				throw new \Exception($message);
			}
		}

		return $_tmp;
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
	 * @param $attributes
	 * @param $condition
	 * @return bool|string
	 */
	public function updateAll($table, $attributes, $condition)
	{
		$param = [];
		foreach ($attributes as $key => $val) {
			if ($val === null || $val === '') {
				continue;
			}
			$param[] = $this->resolve($key, $val);
		}

		if (empty($param)) return true;
		$condition = $this->builderWhere($condition);

		return 'UPDATE ' . $table . ' SET ' . implode(',', $param) . $condition;
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
	 * @param null $select
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
	 * @param $alias
	 * @return string
	 */
	private function builderAlias($alias)
	{
		return " AS " . $alias;
	}

	/**
	 * @param $table
	 * @return string
	 */
	private function builderFrom($table)
	{
		if (preg_match('/^\{\{.*\}\}$/', $table)) {
			$prefix = \Beauty::$app->db->tablePrefix;

			return " FROM " . $prefix . trim($table, '{{%}}');
		} else {
			return " FROM " . $table;
		}
	}

	/**
	 * @param $join
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
		if (!empty($_tmp)) {
			return ' WHERE (' . implode(') AND (', $_tmp) . ')';
		} else {
			return '';
		}
	}

	/**
	 * @param $group
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
	 * @param $order
	 * @return string
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
	 * @param QueryTrait $query
	 * @return string
	 */
	private function builderLimit($query)
	{
		$limit = $query->limit;
		if (!is_numeric($limit) || $limit < 1) {
			return "";
		}
		$offset = $query->offset;

		return ' LIMIT ' . $offset . ',' . $limit;
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
		$condition = ['like', 'in', 'or', '>', '<', '<=', '>=', '<>', 'eq', 'neq', 'gt', 'ngt', 'lt', 'nlt'];
		foreach ($array as $key => $value) {
			if (is_array($value) && isset($value[0])) {
				if (in_array($value[0], $condition)) {
					$tmp = $this->builderLike($value);
				} else {
					$tmp = $this->addCondition($value);
				}
			} else if (is_string($key)) {
				$tmp = $this->resolve($key, $value);
			} else {
				$tmp = $this->addCondition($value);
			}
			if (empty($tmp)) {
				continue;
			}
			$_tmp[] = $tmp;
		}
		return $_tmp;
	}


	/**
	 * @param $condition
	 * @return string
	 */
	private function addCondition($condition)
	{
		if (!is_array($condition)) {
			return $condition;
		}

		echo PHP_EOL;
		echo PHP_EOL;
		echo PHP_EOL;
		echo __FILE__ . __LINE__ . PHP_EOL;
		var_dump($condition);

		$_tmp = [];
		foreach ($condition as $key => $value) {
			if ($value === null || $value === '') {
				continue;
			}
			if (is_numeric($key)) {
				$_tmp[] = $value;
			} else {
				$_tmp[] = $this->resolve($key, $value);
			}
		}
		if (empty($_tmp)) {
			return '';
		}
		return '(' . implode(') AND (', $_tmp) . ')';
	}

	/**
	 * @param $array
	 * @return mixed
	 * @throws \Exception
	 */
	private function builderLike($array)
	{
		list($opera, $column, $value) = $array;

		$option['value'] = $value;
		$option['opera'] = $opera;
		$option['column'] = $column;

		$strPer = strtoupper($opera);
		if (isset($this->conditionMap[$strPer])) {
			$class = $this->conditionMap[$strPer];
			if (is_array($class)) {
				$option = array_merge($option, $class);
			} else {
				$option['class'] = $class;
			}
		} else if ($value instanceof ActiveQuery) {
			$option['value'] = $value->adaptation();
			$option['class'] = ChildCondition::class;
		} else {
			$option['class'] = DefaultCondition::class;
		}

		/** @var Condition $class */
		$class = \Beauty::createObject($option);
		return $class->builder();
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
