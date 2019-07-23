<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/4/26 0026
 * Time: 11:58
 */

namespace Beauty\exception;


use Throwable;

class InitException extends Exception
{
	public function __construct(string $message = "", int $code = 0, Throwable $previous = NULL)
	{
		parent::__construct($message, 1000, $previous);
	}
}
