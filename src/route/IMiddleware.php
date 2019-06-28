<?php


namespace Yoc\route;


use Yoc\http\Request;

interface IMiddleware
{

	public function handler(Request $params,\Closure $next);

}
