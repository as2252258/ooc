<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/11/8 0008
 * Time: 18:24
 */

namespace Beauty\server;


interface IServer
{
	public function onHandler(...$value);
}
