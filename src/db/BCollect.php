<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/4/9 0009
 * Time: 9:44
 */

namespace Yoc\db;


abstract class BCollect implements \IteratorAggregate, \ArrayAccess
{

	/**
	 * @var ActiveRecord[]
	 */
	protected $_item = [];

	protected $_filter = [];

	/** @var ActiveRecord */
	protected $model;

	/**
	 * Collection constructor.
	 *
	 * @param array $array
	 */
	public function __construct(array $array = [])
	{
		$this->_item = $array;
	}


	/**
	 * @return int
	 */
	public function getLength()
	{
		return count($this->_item);
	}


	/**
	 * @param $item
	 */
	public function setItems($item)
	{
		$this->_item = $item;
	}


	/**
	 * @param $model
	 */
	public function setModel($model)
	{
		$this->model = $model;
	}

	/**
	 * @param $item
	 */
	public function addItem($item)
	{
		array_push($this->_item, $item);
	}

	/**
	 * @return \ArrayIterator|\Traversable
	 */
	public function getIterator()
	{
		return new \ArrayIterator($this->_item);
	}

	/**
	 * @param mixed $offset
	 * @return bool
	 */
	public function offsetExists($offset)
	{
		return !empty($this->_item) && isset($this->_item[$offset]);
	}

	/**
	 * @param mixed $offset
	 * @return mixed|null|ActiveRecord
	 */
	public function offsetGet($offset)
	{
		if (!$this->offsetExists($offset)) {
			return NULL;
		}
		/** @var ActiveRecord $model */
		return $this->_item[$offset];
	}

	/**
	 * @param mixed $offset
	 * @param mixed $value
	 */
	public function offsetSet($offset, $value)
	{
		$this->_item[$offset] = $value;
	}


	/**
	 * @param mixed $offset
	 */
	public function offsetUnset($offset)
	{
		if ($this->offsetExists($offset)) {
			unset($this->_item[$offset]);
		}
	}
}
