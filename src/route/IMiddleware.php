<?php


namespace Beauty\route;


use Beauty\http\Request;

interface IMiddleware
{

	public function handler(Request $params,\Closure $next);

}
