<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/4/25 0025
 * Time: 18:34
 */

namespace Yoc\exception;


use Throwable;

class ComponentException extends Exception
{
	
	public function __construct(string $message = "", int $code = 0, Throwable $previous = NULL)
	{
		parent::__construct($message, 5000, $previous);
	}
	
}
