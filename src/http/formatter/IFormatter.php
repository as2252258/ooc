<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/4/8 0008
 * Time: 17:29
 */

namespace Yoc\http\formatter;


interface IFormatter
{

	/**
	 * @return static
	 */
	public function send($context);

	public function getData();

	public function clear();
}
