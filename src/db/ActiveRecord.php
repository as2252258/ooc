<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/3/30 0030
 * Time: 14:39
 */

namespace Yoc\db;


use Exception;
use Yoc\core\ArrayAccess;
use Yoc\error\Logger;
use Yoc\exception\DbException;

defined('SAVE_FAIL') or define('SAVE_FAIL', 3227);

/**
 * Class Orm
 * @package Yoc\db
 *
 * @property $attributes
 * @property-read $oldAttributes
 * @method beforeSearch($model)
 */
class ActiveRecord extends BaseActiveRecord
{

	/**
	 * @return array
	 */
	public function rules()
	{
		return [];
	}

	/**
	 * @param string $column
	 * @param int $value
	 * @return static|bool
	 * @throws Exception
	 */
	public function incrBy(string $column, int $value)
	{
		$primary = static::getPrimary();
		if (empty($primary) || !is_string($primary)) {
			throw new Exception('Only has primary data can use.');
		}

		$field = $this->{static::getPrimary()};
		if (empty($field)) {
			return false;
		}

		if (!isset($this->actions['incr'])) {
			$this->actions['incr'] = [];
		}
		$this->actions['incr'][$column] = $value;

		$command = static::getDb()->createCommand()->incr($this, $this->actions, [$primary => $field]);
		if (!$command->incrOrDecr()) {
			return false;
		}

		$this->$column += $value;
		$this->refresh();

		return $value;
	}

	/**
	 * @param $column
	 * @param $value
	 * @return bool|static
	 * @throws Exception
	 */
	public function decrBy(string $column, int $value)
	{
		$primary = static::getPrimary();
		if (empty($primary) || !is_string($primary)) {
			throw new Exception('Only has primary data can use.');
		}

		$field = $this->{static::getPrimary()};
		if (empty($field)) {
			return false;
		}

		if (!isset($this->actions['decr'])) {
			$this->actions['decr'] = [];
		}
		$this->actions['decr'][$column] = $value;

		$command = static::getDb()->createCommand()->incr($this, $this->actions, [$primary => $field]);
		if (!$command->incrOrDecr()) {
			return false;
		}

		$this->$column -= $value;
		$this->refresh();

		return $value;
	}

	/**
	 * @param array $params
	 * @return bool|static
	 * @throws Exception
	 */
	public static function InsertOrUpdate(array $params)
	{
		$table = static::getTable();

		$builder = static::getDb()->getSchema()->getQueryBuilder();

		$mysqlLanguage = $builder->insertOrUpdateByDUPLICATE($table, $params);

		Logger::debug($mysqlLanguage,'mysql');

		$command = static::getDb()->createCommand($mysqlLanguage);
		if (false === ($id = $command->exec())) {
			throw new Exception($command->getError());
		}

		if (static::hasPrimary() && is_numeric($id)) {
			$params[static::getPrimary()] = $id;

			return static::propute($params);
		}

		return static::find()->where($params)->first();
	}

	/**
	 * @param array $data
	 * @return bool
	 * @throws DbException
	 * @throws \Exception
	 */
	public static function addAll(array $data): bool
	{
		$class = new static();
		if (empty($data)) {
			return $class->addError('Insert data empty.', 'mysql');
		}
		$first = current($data);
		$last = $data[count($data) - 1];
		if (!is_array($first) || !is_array($last)) {
			return $class->addError('Insert data format error.', 'mysql');
		}
		$db = static::getDb();

		$attributes = array_keys(current($data));

		$data = $db->getBuild()->all($class::getTable(), $attributes, $data);

		$command = $db->createCommand(...$data)->save(FALSE);
		if (!$command) {
			return FALSE;
		} else {
			return TRUE;
		}
	}

	/**
	 * @return bool
	 * @throws Exception
	 */
	public function delete()
	{
		$conditions = $this->_oldAttributes;
		if (empty($conditions)) {
			return $this->addError("Delete condition do not empty.", 'mysql');
		}
		$primary = static::getPrimary();

		if (!empty($primary)) {
			$sul = static::deleteAll([$primary => $this->getAttribute($primary)]);
		} else {
			$sul = static::deleteAll($conditions);
		}
		if (!$sul) {
			return false;
		}
		if (method_exists($this, 'afterDelete')) {
			$this->afterDelete();
		}
		return true;
	}


	/**
	 * @param       $condition
	 * @param array $attributes
	 *
	 * @return bool
	 * @throws \Exception
	 */
	public static function updateAll($condition, $attributes = [])
	{
		$command = static::getDb();
		$condition = static::find()->where($condition);

		$param = [];
		foreach ($attributes as $key => $val) {
			$_n = $condition->resolve($key, $val);
			if (empty($_n)) {
				continue;
			}
			$param[] = $_n;
		}

		if (empty($param)) return true;
		$sql = 'UPDATE ' . static::getTable() . ' SET ' . implode(',', $param) . $condition->getCondition();

		return $command->createCommand($sql)->exec();
	}

	/**
	 * @param       $condition
	 * @param array $attributes
	 *
	 * @return array|mixed|null|\Yoc\db\Collection
	 * @throws \Exception
	 */
	public static function findAll($condition, $attributes = [])
	{
		if (!empty($attributes)) {
			return static::find()->where($condition, $attributes)->all();
		} else {
			return static::find()->where($condition)->all();
		}
	}

	/**
	 * @param $data
	 * @return array|mixed
	 * @throws \Exception
	 */
	private function resolveObject($data)
	{
		if (is_object($data) || is_array($data)) {
			return ArrayAccess::toArray($data);
		}
		if (is_numeric($data) || !method_exists($this, $data)) {
			return $data;
		}
		$relate = $this->{$data}();
		if (($relate instanceof HasBase)) {
			$relate = $relate->get();
		}
		if (is_array($relate) || is_object($relate)) {
			$return = ArrayAccess::toArray($relate);
		} else {
			$return = $relate;
		}
		return $return;
	}


	/**
	 * @return array|mixed
	 * @throws \Exception
	 */
	public function toArray()
	{
		$data = [];
		foreach ($this->_attributes as $key => $val) {
			$data[$key] = $this->$key;
		}
		return array_merge($data, $this->runRelate());
	}

	/**
	 * @return array
	 * @throws \Exception
	 */
	private function runRelate()
	{
		$relates = [];
		if (empty($this->_relate)) {
			return $relates;
		}
		foreach ($this->_relate as $key => $val) {
			$relates[$key] = $this->resolveObject($val);
		}
		return $relates;
	}


	/**
	 * @param $modelName
	 * @param $foreignKey
	 * @param $localKey
	 * @return mixed|ActiveQuery
	 * @throws \Exception
	 */
	public function hasOne($modelName, $foreignKey, $localKey)
	{
		if (!$this->has($localKey)) {
			throw new \Exception("Need join table primary key.");
		}
		return new HasOne($modelName, $foreignKey, $this->$localKey);
	}


	/**
	 * @param $modelName
	 * @param $foreignKey
	 * @param $localKey
	 * @return mixed|ActiveQuery
	 * @throws \Exception
	 */
	public function hasMany($modelName, $foreignKey, $localKey)
	{
		if (!$this->has($localKey)) {
			throw new \Exception("Need join table primary key.");
		}
		return new HasMany($modelName, $foreignKey, $this->$localKey);
	}

	/**
	 * @param $modelName
	 * @param $foreignKey
	 * @param $localKey
	 * @return mixed|ActiveQuery
	 * @throws \Exception
	 */
	public function hasIn($modelName, $foreignKey, $localKey)
	{
		if (!$this->has($localKey)) {
			throw new \Exception("Need join table primary key.");
		}

		return new HasMany($modelName, $foreignKey, $this->$localKey);
	}

	/**
	 * @return bool
	 * @throws \Exception
	 */
	public function afterDelete()
	{
		if (!self::hasPrimary()) {
			return TRUE;
		}
		$value = $this->{self::getPrimary()};
		if (empty($value)) {
			return TRUE;
		}
		return TRUE;
	}

	/**
	 * @return bool
	 * @throws \Exception
	 */
	public function beforeDelete()
	{
		if (!self::hasPrimary()) {
			return TRUE;
		}
		$value = $this->{self::getPrimary()};
		if (empty($value)) {
			return TRUE;
		}
		return TRUE;
	}

	/**
	 * @param array $data
	 * @return ActiveRecord
	 * @throws
	 */
	public static function propute(array $data)
	{
		$model = \Yoc::createObject(get_called_class());
		$model->_attributes = $data;
		$model->_oldAttributes = $data;
		$model->setIsCreate(false);
		return $model;
	}
}
