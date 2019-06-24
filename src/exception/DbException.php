<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/4/27 0027
 * Time: 13:23
 */

namespace Yoc\exception;


use Throwable;

class DbException extends Exception
{
	public function __construct(string $message = "", int $code = 0, Throwable $previous = NULL)
	{
		parent::__construct($message, 3522, $previous);
	}
}
