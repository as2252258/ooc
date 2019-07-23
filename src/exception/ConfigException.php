<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/5/24 0024
 * Time: 12:06
 */

namespace Beauty\exception;


use Throwable;

class ConfigException extends Exception
{
	
	public function __construct(string $message = "", int $code = 0, Throwable $previous = NULL)
	{
		parent::__construct($message, 4522, $previous);
	}
	
}
