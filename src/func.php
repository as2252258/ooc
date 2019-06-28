<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/6/13 0013
 * Time: 14:17
 */

if (!function_exists('calc_hash_tbl')) {

	/**
	 * @param $u
	 * @param int $n | 表数量
	 * @param int $m
	 * @return int
	 */
	function calc_hash_tbl($u, $n = 20, $m = 10)
	{

		$h = sprintf("%u", crc32($u));

		$h1 = intval($h / $n);

		$h2 = $h1 % $n;

		$h3 = base_convert($h2, 10, $m);

		$h4 = sprintf("%02s", $h3);

		return $h4 == 0 ? '' : (int)$h4;

	}
}

if (!function_exists('hash_datebase')) {

	/**
	 * @param $u
	 * @param int $s | 数据库数量
	 * @return int
	 */
	function hash_datebase($u, $s = 5)
	{
		$h = sprintf("%u", crc32($u));
		$h1 = intval(fmod($h, $s));
		return $h1 == 0 ? '' : $h1;
	}
}

if (!function_exists('get_unique_id')) {

	/**
	 * @param null $prefix
	 * @return int
	 */
	function get_unique_id($prefix = NULL)
	{
		$redis = Yoc::$app->redis;
		$id = Yoc::$app->id . ':' . ($prefix ?? 'uniqueID');
		if (!$redis->exists($id)) {
			$redis->set($id, 1);

			return 1;
		}
		$incr = $redis->incr($id);
		return $incr < 1 ? 1 : $incr;
	}
}

if (!function_exists('md5_uniqid')) {

	function md5_uniqid(string $prefix = null)
	{
		if (!$prefix) {
			return md5(uniqid(md5(microtime(true)), true));
		} else {
			return md5(uniqid($prefix . md5(microtime(true)), true));
		}
	}
}


if (!function_exists('request')) {

	/**
	 * @return \Yoc\http\Request
	 * @throws
	 */
	function request()
	{
		return Yoc::$app->get('request');
	}

}


if (!function_exists('Input')) {

	/**
	 * @return \Yoc\http\HttpParams
	 */
	function Input()
	{
		return request()->params;
	}

}


if (!function_exists('response')) {

	/**
	 * @return \Yoc\http\Response
	 * @throws
	 */
	function response()
	{
		return Yoc::$app->get('response');
	}

}

if (!function_exists('app')) {

	/**
	 * @return \Yoc\web\Application
	 */
	function app()
	{
		return Yoc::$app;
	}

}


if (!function_exists('redirect')) {

	/**
	 * @param $url
	 */
	function redirect($url)
	{
		return response()->redirect($url);
	}

}

if (!function_exists('redis')) {

	/**
	 * @return \Redis
	 * @throws
	 */
	function redis()
	{
		return Yoc::$app->getRedis();
	}
}

if (!function_exists('router')) {

	/**
	 * @return \Yoc\route\Router
	 * @throws
	 */
	function router()
	{
		return Yoc::$app->router;
	}
}


if (!function_exists('isInCircle')) {

	/**
	 * @param int $centerX1
	 * @param int $centerY1
	 * @param int $x2
	 * @param int $y2
	 * @param int $r
	 * @return bool
	 * 判断是否在中心点
	 */
	function isInCircle(int $centerX1, int $centerY1, int $x2, int $y2, $r = 100)
	{
		$distance = sqrt(($y2 - $centerY1) * ($y2 - $centerY1) + ($x2 - $centerX1) * ($x2 - $centerX1));
		if ($distance > $r) {
			return false;
		} else {
			return true;
		}
	}
}


if (!function_exists('env')) {

	function env($key, $default = null)
	{
		return getenv($key) ?? $default;
	}

}
