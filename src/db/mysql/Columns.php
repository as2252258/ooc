<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2019-03-18
 * Time: 17:22
 */

namespace Beauty\db\mysql;


use Beauty\base\Component;
use Beauty\db\ActiveRecord;
use Beauty\db\Connection;
use Beauty\exception\DbException;

class Columns extends Component
{

	private $columns = [];

	/** @var Connection $db */
	public $db;
	public $table = '';

	/**
	 * @param string $table
	 * @return $this
	 */
	public function table($table)
	{
		$this->table = $table;
		return $this;
	}

	/**
	 * @return $this|array
	 * @throws
	 */
	public function getColumns()
	{
		if (!empty($this->columns[$this->table])) {
			return $this->columns[$this->table];
		}
		$sql = $this->db->getBuild()->getColumn($this->table);

		$column = $this->db->createCommand($sql)->all();
		if (empty($column)) {
			throw new DbException("The table " . $this->table . " not exists.");
		}

		$this->columns[$this->table] = $column;
		return $this;
	}


	/**
	 * @param $key
	 * @param $val
	 * @param $column
	 * @param $object
	 * @throws \Exception
	 */
	public function fieldFormat($key, $val, &$object)
	{
		$format = $this->getFields()[$key];
		if (strpos($format, '(') !== FALSE) {
			$format = current(explode('(', $format));
		}
		if (strpos(' ', $format) !== FALSE) {
			$format = explode(' ', $format)[1];
		}
		switch (strtolower(trim($format))) {
			case 'int':
			case 'tinyint':
				$object[$key] = (int)$val;
				break;
			case 'float':
				$object[$key] = floatval($val);
				break;
			case 'varchar':
			case 'char':
			case 'text':
			case 'longtext':
				$object[$key] = htmlspecialchars($val);
				break;
			case 'json':
				if (is_array($val)) {
					$object[$key] = json_encode($val, JSON_UNESCAPED_UNICODE);
				} else if (is_null($json = json_decode($val, TRUE))) {
					throw new \Exception('Field ' . $key . ' has data format error.');
				} else {
					$object[$key] = $val;
				}
				break;
			default:
				$object[$key] = $val;
		}
	}

	/**
	 * @param $data
	 * @return array
	 * @throws
	 */
	public function populate($data)
	{
		$column = $this->getFields();
		foreach ($data as $key => $val) {
			$format = $column[$key] ?? null;
			if (empty($format)) continue;

			$shift = current(explode(' ', $format));
			$format = current(explode('(', $shift));

			$data[$key] = $this->onlyField($key, $val, $format);
		}
		return $data;
	}

	/**
	 * @param $field
	 * @param $val
	 * @param $format
	 * @return float|int|mixed|string
	 * @throws DbException
	 */
	public function onlyField($field, $val, $format = null)
	{
		if ($format === null) {
			$format = $this->getFields()[$field];
		}
		switch (strtolower($format)) {
			case 'int':
			case 'bigint':
			case 'tinyint':
				$val = (int)$val;
				break;
			case 'float':
				$val = floatval($val);
				break;
			case 'varchar':
			case 'char':
			case 'text':
			case 'longtext':
				$val = htmlspecialchars_decode($val);
				break;
			case 'json':
				if (is_string($val)) {
					$val = json_decode($val, TRUE);
				}
				break;
		}
		return $val;
	}

	/**
	 * @return array
	 * @throws
	 */
	public function format()
	{
		$this->getColumns();
		return array_column($this->columns[$this->table], 'Default', 'Field');
	}


	public function getPrimaryKeys()
	{
		$_tmp = [];
		foreach ($this->columns as $val) {
			$_tmp[] = $val[''];
		}
	}


	/**
	 * @return array
	 * @throws DbException
	 */
	public function getFields()
	{
		$this->getColumns();
		return array_column($this->columns[$this->table], 'Type', 'Field');
	}

}
