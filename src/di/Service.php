<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/4/25 0025
 * Time: 18:29
 */

namespace Beauty\di;


use Beauty\base\Component;
use Beauty\exception\ComponentException;
use Beauty\exception\NotFindClassException;

class Service extends Component
{

	private $_components = [];


	private $_definition = [];

	/**
	 * @param $id
	 *
	 * @return mixed
	 * @throws
	 */
	public function get($id)
	{
		if (isset($this->_components[$id])) {
			return $this->_components[$id];
		}
		if (isset($this->_definition[$id])) {
			$config = $this->_definition[$id];
			$object = \Beauty::createObject($config);
			return $this->_components[$id] = $object;
		} else {
			throw new ComponentException("Unknown component ID: $id");
		}
	}


	/**
	 * @param $id
	 * @param $definition
	 *
	 * @throws \Exception
	 */
	public function set($id, $definition)
	{
		if ($definition === NULL) {
			unset($this->_components[$id], $this->_definition[$id]);
			return;
		}

		unset($this->_components[$id]);

		if (is_object($definition) || is_callable($definition, TRUE)) {
			$this->_definition[$id] = $definition;
		} else if (is_array($definition)) {
			if (isset($definition['class'])) {
				$this->_definition[$id] = $definition;
			} else {
				throw new ComponentException("The configuration for the \"$id\" component must contain a \"class\" element.");
			}
		} else {
			throw new ComponentException("Unexpected configuration type for the \"$id\" component: " . gettype($definition));
		}
	}

	/**
	 * @param $id
	 * @return bool
	 */
	public function has($id)
	{
		return isset($this->_definition[$id]) || isset($this->_components[$id]);
	}

	/**
	 * @param array $data
	 * @throws \Exception
	 */
	public function setComponents(array $data)
	{
		foreach ($data as $key => $val) {
			$this->set($key, $val);
		}
	}


	/**
	 * @param $name
	 * @return mixed
	 * @throws \Exception
	 */
	public function __get($name)
	{
		$method = 'get' . ucfirst($name);
		if (method_exists($this, $method)) {
			return $this->$method();
		} else if ($this->has($name)) {
			return $this->get($name);
		} else {
			return parent::__get($name);
		}
	}

	/**
	 * @param $id
	 */
	public function remove($id)
	{
		unset($this->_components[$id]);
		unset($this->_definition[$id]);
	}
}
