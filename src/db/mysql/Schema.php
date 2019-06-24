<?php

namespace Yoc\db\mysql;


use Yoc\base\Component;
use Yoc\db\Connection;
use Yoc\db\QueryBuilder;

class Schema extends Component
{

	/** @var Connection */
	public $db;

	/** @var QueryBuilder */
	private $_builder = null;

	/** @var Columns */
	private $_column = null;

	/**
	 * @return QueryBuilder
	 */
	public function getQueryBuilder()
	{
		if ($this->_builder === null) {
			$this->_builder = new QueryBuilder();
		}
		return $this->_builder;
	}


	/**
	 * @return Columns
	 */
	public function getColumns()
	{
		if ($this->_column === null) {
			$this->_column = new Columns(['db' => $this->db]);
		}

		return $this->_column;
	}
}
