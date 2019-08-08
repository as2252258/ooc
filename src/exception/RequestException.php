<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/4/24 0024
 * Time: 19:26
 */

namespace Beauty\exception;


use Throwable;

class RequestException extends Exception
{
	public function __construct(string $message = "", int $code = 0, Throwable $previous = null)
	{
		parent::__construct($message, 4004, $previous);
	}
}
