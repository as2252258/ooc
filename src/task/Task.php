<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2019-03-22
 * Time: 16:00
 */

namespace Beauty\task;


use Beauty\base\Component;

abstract class Task extends Component implements InterfaceTask
{

	public $param;

	public function __construct($data)
	{
		$this->param = $data;
		parent::__construct([]);
	}
}
