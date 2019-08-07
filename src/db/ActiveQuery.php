<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/4/4 0004
 * Time: 14:42
 */

namespace Beauty\db;

use Beauty\base\Component;
use Beauty\db\traits\QueryTrait;

class ActiveQuery extends Component
{

	use QueryTrait;

	/** @var array */
	public $with = [];

	/** @var bool */
	public $asArray = FALSE;

	/** @var bool */
	public $useCache = FALSE;

	/** @var Connection $db */
	public $db = NULL;

	/**
	 * @var array
	 * 参数绑定
	 */
	public $attributes = [];


	public function clear()
	{
		$this->db = null;
		$this->useCache = false;
		$this->with = [];
	}

	/**
	 * @param $key
	 * @param $value
	 * @return $this
	 */
	public function addParam($key, $value)
	{
		$this->attributes[$key] = $value;
		return $this;
	}

	/**
	 * @param array $values
	 * @return $this
	 */
	public function addParams(array $values)
	{
		foreach ($values as $key => $val) {
			$this->addParam($key, $val);
		}
		return $this;
	}

	/**
	 * @param $name
	 * @return $this
	 */
	public function with($name)
	{
		if (empty($name)) {
			return $this;
		}
		if (is_string($name)) {
			$name = explode(',', $name);
		}
		foreach ($name as $key => $val) {
			array_push($this->with, $val);
		}
		return $this;
	}

	/**
	 * @param bool $isArray
	 * @return $this
	 */
	public function asArray($isArray = TRUE)
	{
		$this->asArray = $isArray;
		return $this;
	}


	/** @var ActiveRecord */
	public $modelClass;

	/**
	 * Comply constructor.
	 * @param $model
	 * @param array $config
	 */
	public function __construct($model, $config = [])
	{
		$this->modelClass = $model;
		parent::__construct($config);
	}

	/**
	 * @return ActiveRecord
	 * @throws
	 */
	public function first()
	{
		$data = $this->command($this->adaptation())->one();
		if (empty($data)) {
			return NULL;
		}
		if ($this->asArray) {
			return $data;
		}
		return $this->populate($data);
	}

	/**
	 * @return array|Collection
	 */
	public function get()
	{
		return $this->all();
	}


	/**
	 * @param int $size
	 * @param callable $callback
	 * @param mixed $param
	 * @throws \Exception
	 */
	public function plunk(int $size, callable $callback, $param = null)
	{
		$offset = 0;
		\Beauty::checkFunction($callback, true);
		do {
			//get batch data by condition
			$data = $this->limit($size, $offset)->get();
			if ($data->isEmpty()) {
				break;
			}

			//run callback
			if ($param !== null) {
				call_user_func($callback, $data, $param);
			} else {
				call_user_func($callback, $data);
			}

			//check data if is over
			if ($data->getLength() < $size) {
				break;
			}
			$offset += $size;
		} while ($data->getLength() == $size);
	}

	/**
	 * @return array|Collection
	 * @throws
	 */
	public function all()
	{
		$data = $this->command($this->adaptation())->all();
		$collect = new Collection();
		if (empty($data) || !is_array($data)) {
			return $this->asArray ? [] : new Collection();
		}
		$_tmp = [];
		foreach ($data as $key => $val) {
			$_tmp[] = $this->populate($val);
		}
		$collect->setItems($_tmp);
		if ($this->asArray) {
			return $collect->toArray();
		}
		return $collect;
	}

	/**
	 * @return array|mixed|null|ActiveRecord
	 * @throws \Exception
	 */
	public function queryRand()
	{
		$this->orderBy('RAND()')->limit(1);
		return $this->first();
	}

	/**
	 * @param string $field
	 * @param string $setKey
	 *
	 * @return array|null
	 * @throws \Exception
	 */
	public function column(string $field, $setKey = '')
	{
		return $this->all()->column($field, $setKey);
	}

	/**
	 * @param $data
	 *
	 * @return ActiveRecord
	 * @throws \ReflectionException
	 * @throws \Exception
	 */
	private function populate($data)
	{
		/** @var ActiveRecord $model */
		$model = $this->modelClass::propute($data);

		if (!empty($this->with) && is_array($this->with)) {
			$this->findWith($model);
		}

		return $model;
	}


	/**
	 * @param ActiveRecord $model
	 * @throws
	 */
	protected function findWith($model)
	{
		if (empty($this->with) || !is_array($this->with)) {
			return;
		}
		foreach ($this->with as $val) {
			$method = 'get' . ucfirst($val);
			if (!method_exists($model, $method)) {
				continue;
			}
			$model->setRelate($val, $method);
		}
	}

	/**
	 * @return int
	 * @throws \Exception
	 */
	public function count()
	{
		$primary = $this->getPrimary();
		if (!empty($primary)) $this->select($primary);
		$data = $this->command($this->getBuild()->count($this))->one();
		$this->select = [];
		if ($data && is_array($data)) {
			return (int)array_shift($data);
		} else {
			return 0;
		}
	}

	/**
	 * @param $filed
	 *
	 * @return null
	 * @throws \Exception
	 */
	public function value($filed)
	{
		$first = $this->first()->toArray();
		return $first[$filed] ?? null;
	}

	/**
	 * @return bool
	 * @throws \Exception
	 */
	public function exists()
	{
		$sql = $this->adaptation();
		return (bool)$this->command($sql)->fetchColumn();
	}

	/**
	 * @return bool
	 * @throws \Exception
	 */
	public function deleteAll()
	{
		$sql = $this->getBuild()->delete($this);
		return $this->command($sql)->delete();
	}

	/**
	 * @return string
	 * @throws \Exception
	 */
	public function getCondition()
	{
		return $this->getBuild()->getWhere($this);
	}

	/**
	 * @return string
	 * @throws \Exception
	 */
	public function adaptation()
	{
		return $this->getBuild()->builder($this);
	}

	/**
	 * @param $limit
	 * @return Query|Plunk
	 */
//	public function plunk($limit)
//	{
//		return new Plunk($this);
//	}

	/**
	 * @param $sql
	 * @param array $attr
	 * @return Command
	 * @throws \Exception
	 */
	private function command($sql, $attr = [])
	{
		if (!empty($attr) && is_array($attr)) {
			$attr = array_merge($this->attributes, $attr);
		} else {
			$attr = $this->attributes;
		}
		return $this->getDb()->createCommand($sql, $attr);
	}

	/**
	 * @return QueryBuilder
	 * @throws \Exception
	 */
	public function getBuild()
	{
		return $this->getDb()->getSchema()->getQueryBuilder();
	}

	/**
	 * @return Connection
	 * @throws \Exception
	 */
	private function getDb()
	{
		return $this->modelClass::getDb();
	}

	/**
	 * @return mixed
	 */
	public function getPrimary()
	{
		return $this->modelClass::getPrimary();
	}
}
