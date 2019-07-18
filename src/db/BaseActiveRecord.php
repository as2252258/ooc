<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/3/30 0030
 * Time: 14:39
 */

namespace Yoc\db;


use Yoc\base\Component;
use Yoc\validator\Validator;
use Yoc\error\Logger;
use Exception;

/**
 * Class BOrm
 *
 * @package Yoc\base
 *
 * @property bool $isCreate
 * @method rules()
 * @method static tableName()
 */
abstract class BaseActiveRecord extends Component implements IOrm, \ArrayAccess
{

	/** @var array */
	protected $_attributes = [];

	/** @var array */
	protected $_oldAttributes = [];

	/** @var array */
	protected $_relate = [];

	/** @var null|string */
	protected static $primary = NULL;

	/**
	 * @var bool
	 */
	protected $isNewExample = TRUE;

	protected $actions = [];

	/**
	 * @throws Exception
	 */
	public function init()
	{
		$column = $this->getColumns()->format();

		$this->_attributes = $column;
		$this->_oldAttributes = $column;
	}

	/**
	 * @param $column
	 * @param $value
	 * @return $this
	 * @throws
	 */
	public function incrBy(string $column, int $value)
	{
		throw new Exception('Undefined function incrBy in ' . get_called_class());
	}

	/**
	 * @param $column
	 * @param $value
	 * @return $this
	 * @throws
	 */
	public function decrBy(string $column, int $value)
	{
		throw new Exception('Undefined function decrBy in ' . get_called_class());
	}

	/**
	 * @return array
	 */
	public function getActions()
	{
		return $this->actions;
	}

	/**
	 * @return bool
	 */
	public function getIsCreate()
	{
		return $this->isNewExample === TRUE;
	}

	/**
	 * @param bool $bool
	 * @return $this
	 */
	public function setIsCreate($bool = FALSE)
	{
		$this->isNewExample = $bool;
		return $this;
	}

	/**
	 * @return mixed
	 *
	 * get last exception or other error
	 */
	public function getLastError()
	{
		return Logger::getLastError('mysql');
	}

	/**
	 * @return bool
	 */
	public static function hasPrimary()
	{
		return static::$primary !== NULL;
	}

	/**
	 * @return null|string
	 */
	public static function getPrimary()
	{
		return self::hasPrimary() ? static::$primary : NULL;
	}

	/**
	 * @param $condition
	 *
	 * @return $this
	 * @throws
	 */
	public static function findOne($condition, $db = NULL)
	{
		if (empty($condition) || !is_numeric($condition)) {
			return NULL;
		}
		return static::find()->where([static::getPrimary() => $condition])->first();
	}

	/**
	 * @return mixed|ActiveQuery
	 * @throws
	 */
	public static function find()
	{
		return \Yoc::createObject(ActiveQuery::class, [get_called_class()]);//$model->_query;
	}

	/**
	 * @param       $condition
	 * @param array $attributes
	 *
	 * @return bool
	 * @throws \Exception
	 */
	public static function deleteAll($condition = NULL, $attributes = [])
	{
		if (empty($condition)) {
			return static::find()->deleteAll();
		}
		$model = static::find()->where($condition);
		if (!empty($attributes)) {
			$model->bindParams($attributes);
		}
		return $model->deleteAll();
	}


	/**
	 * @return array
	 */
	public function getAttributes()
	{
		return $this->_attributes;
	}

	/**
	 * @return array
	 */
	public function getOldAttributes()
	{
		return $this->_oldAttributes;
	}

	/**
	 * @param $name
	 * @param $value
	 * @return mixed
	 */
	public function setAttribute($name, $value)
	{
		return $this->_attributes[$name] = $value;
	}

	/**
	 * @param $name
	 * @param $value
	 * @return mixed
	 */
	public function setOldAttribute($name, $value)
	{
		return $this->_oldAttributes[$name] = $value;
	}

	/**
	 * @param array $param
	 * @return $this
	 * @throws
	 */
	public function setAttributes(array $param)
	{
		if (empty($param)) {
			return $this;
		}
		foreach ($param as $key => $val) {
			if (!$this->has($key)) {
				continue;
			}
			$this->$key = $val;
		}
		return $this;
	}

	/**
	 * @return bool
	 * @throws \Exception
	 */
	public function beforeSave()
	{
		$event = new BeforeSaveEvent($this);
		$this->trigger('BEFORE_SAVE', $event);
		return $event->isVild;
	}

	/**
	 * @return bool|BaseActiveRecord
	 * @throws \Exception
	 */
	protected function insertInternal()
	{
		list($attributes, $param) = $this->filterNullValue($this->_attributes);
		if (empty($attributes) || empty($param)) {
			return FALSE;
		}

		$command = static::getDb()->createCommand()->insert(static::getTable(), $attributes, $param);

		if (($lastId = $command->save(TRUE)) === FALSE) {
			return FALSE;
		}

		if (!empty(self::getPrimary()) && $lastId) {
			$param[self::getPrimary()] = $lastId;
		}

		if ($this->afterSave($attributes, $param)) {
			return $this->populate($param);
		}

		return false;
	}


	/**
	 * @param array $data
	 * @return bool|mixed|\Yoc\db\ActiveRecord
	 * @throws Exception
	 */
	public function save($data = NULL)
	{
		if (!empty($data)) {
			$this->setAttributes($data);
		}
		if (!$this->validator($this->rules())) {
			return FALSE;
		}

		$format = $this->getColumns()->format();

		$this->_attributes = array_merge($format, $this->_attributes);

		if ($this->getIsCreate()) {
			return $this->insertInternal();
		} else {
			return $this->updateInternal();
		}
	}


	/**
	 * @param array $rule
	 * @return bool
	 * @throws \Exception
	 */
	public function validator($rule)
	{
		if (empty($rule)) return true;
		$validate = $this->resolve($rule);
		if (!$validate->validation()) {
			return $this->addError($validate->getError(), 'mysql');
		} else {
			return TRUE;
		}
	}

	/**
	 * @param $rule
	 * @return Validator
	 * @throws Exception
	 */
	private function resolve($rule)
	{
		$validate = Validator::getInstance();
		$validate->setParams($this->_attributes);
		foreach ($rule as $Key => $val) {
			$field = array_shift($val);
			if (empty($val)) {
				continue;
			}
			$validate->make($field, $val);
		}
		return $validate;
	}

	/**
	 * @param string $name
	 * @return null
	 * @throws \Exception
	 */
	public function getAttribute(string $name)
	{
		return $this->_attributes[$name] ?? null;
	}


	/**
	 * @return bool|static
	 * @throws \Exception
	 */
	protected function updateInternal()
	{
		if (!($renew = $this->isRenew())) {
			return true;
		}

		list($attributes, $param) = $this->filterNullValue($renew);

		if (empty($attributes) || empty($param)) return false;

		$primary = static::getPrimary();
		if (empty($primary)) {
			$condition = array_diff_assoc($this->_attributes, $renew);
		} else {
			$condition = [$primary => $this->$primary];
		}

		$command = static::getDb()->createCommand();
		$command = $command->update($this, $attributes, $condition, $param, $this->getColumns()->getFields());
		if (!$command->save(false)) {
			return $this->addError($command->getError());
		}
		$model = $this->populate($this->_attributes);

		if (method_exists($this, 'afterSave')) {
			$this->afterSave($attributes, $param);
		}

		return $model;
	}

	/**
	 * @return array
	 */
	private function isRenew()
	{
		return array_diff_assoc($this->_oldAttributes, $this->_attributes);
	}

	/**
	 * @param $data
	 *
	 * @return array
	 */
	private function filterNullValue($data)
	{
		$attributes = $param = [];
		foreach ($data as $key => $val) {
			if ($val === NULL) continue;
			$attributes[] = $key;
			$param[$key] = $val;
		}
		return [$attributes, $param];
	}

	/**
	 * @param $name
	 * @param $value
	 */
	public function setRelate($name, $value)
	{
		$this->_relate[$name] = $value;
	}

	/**
	 * @param array $relates
	 */
	public function setRelates(array $relates)
	{
		if (empty($relates)) {
			return;
		}
		foreach ($relates as $key => $val) {
			$this->setRelate($key, $val);
		}
	}

	/**
	 * @return array
	 */
	public function getRelates()
	{
		return $this->_relate;
	}

	/**
	 * @param $name
	 * @return mixed|null
	 */
	public function getRelate($name)
	{
		if (!isset($this->_relate[$name])) {
			return NULL;
		}
		return $this->_relate[$name];
	}


	/**
	 * @param $attribute
	 * @return bool
	 * @throws Exception
	 */
	public function has($attribute)
	{
		$format = $this->getColumns()->format();

		return array_key_exists($attribute, $format);
	}

	/**ƒ
	 * @return string
	 * @throws \Exception
	 */
	public static function getTable()
	{
		$tablePrefix = static::getDb()->tablePrefix;

		$table = static::tableName();

		if (strpos($table, $tablePrefix) === 0) {
			return $table;
		}

		if (empty($table)) {
			$class = preg_replace('/model\\\/', '', get_called_class());
			$table = lcfirst($class);
		}

		$table = trim($table, '{{%}}');
		if ($tablePrefix) {
			$table = $tablePrefix . $table;
		}
		return $table;
	}

	/**
	 * @param $data
	 * @return static
	 * @throws
	 */
	public function populate($data)
	{
		$this->_attributes = $data;
		$this->_oldAttributes = $this->_attributes;
		$this->setIsCreate(false);
		return $this;
	}


	/**
	 * @param $attributes
	 * @param $changeAttributes
	 * @return mixed
	 * @throws Exception
	 */
	public function afterSave($attributes, $changeAttributes)
	{
		$event = new AfterSaveEvent();
		$event->attributes = $attributes;
		$event->changeAttributes = $changeAttributes;
		$this->trigger('afterSave', $event);
		return $event->isVild;
	}

	/**
	 * @return Connection
	 * @throws \Exception
	 */
	public static function getDb()
	{
		return static::setDatabaseConnect('default');
	}

	/**
	 * @return mixed
	 * @throws \Exception
	 */
	public function getPrValue()
	{
		return $this->getAttribute(self::getPrimary());
	}

	/**
	 * @return static
	 */
	public function refresh()
	{
		$this->_oldAttributes = $this->_attributes;
		return $this;
	}

	/**
	 * @param $name
	 * @param $value
	 * @throws \Exception
	 */
	public function __set($name, $value)
	{
		if (!$this->has($name)) {
			parent::__set($name, $value);
			return;
		}

		$sets = 'set' . ucfirst($name) . 'Attribute';
		if (method_exists($this, $sets)) {
			$value = $this->$sets($value);
		}
		$this->_attributes[$name] = $value;
	}

	/**
	 * @param $name
	 * @return mixed|null
	 * @throws \Exception
	 */
	public function __get($name)
	{
		$method = 'get' . ucfirst($name) . 'Attribute';
		if (method_exists($this, $method)) {
			return $this->$method($this->_attributes[$name] ?? null);
		}

		if (isset($this->_attributes[$name]) || array_key_exists($name, $this->_attributes)) {
			return $this->_attributes[$name];
		}

		if (isset($this->_relate[$name])) {
			return $this->resolveClass($this->_relate[$name]);
		}

		$method = 'get' . ucfirst($name);
		if (method_exists($this, $method)) {
			return $this->resolveClass($this->$method());
		}

		return parent::__get($name);
	}

	/**
	 * @param $name
	 * @return |null
	 */
	public function __isset($name)
	{
		return $this->_attributes[$name] ?? null;
	}

	/**
	 * @param $name
	 * @return array|null|ActiveRecord
	 * @throws \Exception
	 */
	private function resolveClass($call)
	{
		if ($call instanceof HasOne) {
			return $call->get();
		} else if ($call instanceof HasMany) {
			return $call->get();
		} else {
			return $call;
		}
	}


	/**
	 * @param mixed $offset
	 * @return bool
	 * @throws \Exception
	 */
	public function offsetExists($offset)
	{
		return $this->has($offset);
	}

	/**
	 * @param mixed $offset
	 * @return mixed|null
	 * @throws \Exception
	 */
	public function offsetGet($offset)
	{
		return $this->__get($offset);
	}

	/**
	 * @param mixed $offset
	 * @param mixed $value
	 * @throws \Exception
	 */
	public function offsetSet($offset, $value)
	{
		return $this->__set($offset, $value);
	}

	/**
	 * @param mixed $offset
	 * @throws \Exception
	 */
	public function offsetUnset($offset)
	{
		if (!$this->has($offset)) {
			return;
		}
		unset($this->_attributes[$offset]);
		unset($this->_oldAttributes[$offset]);
		if (isset($this->_relate)) {
			unset($this->_relate[$offset]);
		}
	}

	/**
	 * @return array
	 */
	public function unset()
	{
		$fields = func_get_args();
		$fields = array_shift($fields);
		if (!is_array($fields)) {
			$fields = explode(',', $fields);
		}

		$array = array_combine($fields, $fields);

		return array_diff_assoc($array, $this->_attributes);
	}


	/**
	 * @param $bsName
	 * @return mixed
	 * @throws \Exception
	 */
	public static function setDatabaseConnect($bsName)
	{
		return \Yoc::$app->{$bsName};
	}

	/**
	 * @return \Yoc\db\mysql\Columns
	 * @throws Exception
	 */
	public function getColumns()
	{
		return static::getDb()->getSchema()
			->getColumns()
			->table(static::getTable());
	}
}
