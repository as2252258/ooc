<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/4/24 0024
 * Time: 17:32
 */

namespace Yoc\exception;


use Throwable;

class NotFindClassException extends Exception
{
	
	public function __construct(string $message = "", int $code = 0, Throwable $previous = null)
	{
		$message = "No class named $message was found, please check if the class name is correct";
		parent::__construct($message, 404, $previous);
	}
	
}
