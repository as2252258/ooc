<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/5/25 0025
 * Time: 10:14
 */

namespace Yoc\exception;


use Throwable;

class AuthException extends Exception
{
	
	public function __construct($message = "", $code = 0, Throwable $previous = NULL)
	{
		parent::__construct($message, 2000, $previous);
	}
	
}
