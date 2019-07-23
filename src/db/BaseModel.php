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

abstract class BaseModel extends Component
{

	use QueryTrait;

	/** @var ActiveRecord */
	public $modelClass;

	/** @var array */
	public $with = [];

	/** @var bool */
	public $asArray = FALSE;

	/** @var bool */
	public $useCache = FALSE;

	/** @var Connection $db */
	public $db = NULL;

	/**
	 * model constructor.
	 * @param $model
	 * @param array $config
	 */
	public function __construct($model, $config = [])
	{
		$this->modelClass = $model;
		parent::__construct($config);
	}

	/**
	 * @param ActiveRecord $model
	 * @throws
	 */
	protected function relate($model)
	{
		if (empty($this->with) || !is_array($this->with)) {
			return;
		}
		foreach ($this->with as $key => $val) {
			$method = 'get' . ucfirst($val);
			if (!method_exists($model, $method)) {
				continue;
			}
			$model->setRelate($val, $method);
		}
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

	/**
	 * @param Connection $db
	 * @return $this
	 */
	public function useDb($db)
	{
		if ($db == NULL) {
			return $this;
		}
		$this->db = $db;
		return $this;
	}


	/**
	 * @return Connection
	 * @throws \Exception
	 */
	protected function getDb()
	{
		if ($this->db instanceof Connection) {
			return $this->db;
		} else {
			return $this->getModel()::getDb();
		}
	}


	/**
	 * @return ActiveRecord
	 * @throws \Exception
	 */
	public function getModel()
	{
		if ($this->modelClass instanceof ActiveRecord) {
			return $this->modelClass;
		} else {
			$this->modelClass = \Beauty::createObject($this->modelClass);
			return clone $this->modelClass;
		}
	}
}
