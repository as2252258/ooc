<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/11/8 0008
 * Time: 16:44
 */

namespace Yoc\event;


interface IEvent
{
	
	public function trigger($name, $class = NULL);
	
	
	public function on($name, $class);
	
}
