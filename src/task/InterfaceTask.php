<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2019-03-22
 * Time: 16:01
 */

namespace Beauty\task;


interface InterfaceTask
{

	public function __construct($data);

	public function handler();


}
