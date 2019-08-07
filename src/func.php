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
		$redis = Beauty::$app->redis;
		$id = Beauty::$app->id . ':' . ($prefix ?? 'uniqueID');
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
	 * @return \Beauty\http\Request
	 * @throws
	 */
	function request()
	{
		return Beauty::$app->get('request');
	}

}


if (!function_exists('Input')) {

	/**
	 * @return \Beauty\http\HttpParams
	 */
	function Input()
	{
		return request()->params;
	}

}


if (!function_exists('response')) {

	/**
	 * @return \Beauty\http\Response
	 * @throws
	 */
	function response()
	{
		return Beauty::$app->get('response');
	}

}

if (!function_exists('app')) {

	/**
	 * @return \Beauty\web\Application
	 */
	function app()
	{
		return Beauty::$app;
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
		return Beauty::$app->getRedis();
	}
}

if (!function_exists('router')) {

	/**
	 * @return \Beauty\route\Router
	 * @throws
	 */
	function router()
	{
		return Beauty::$app->router;
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


if (!function_exists('setCommand')) {

	/**
	 * @param bool $isCommand
	 * @return mixed
	 */
	function setCommand($isCommand = true)
	{
		return app()->isCommand = $isCommand;
	}

}


if (!function_exists('getIsCommand')) {

	/**
	 * @return mixed
	 */
	function getIsCommand()
	{
		return app()->isCommand;
	}

}


if (!function_exists('getIsCli')) {

	/**
	 * @return mixed
	 */
	function getIsCli()
	{
		return app()->isCli;
	}

}


if (!function_exists('events')) {

	/**
	 * @return \Beauty\event\Event
	 * @throws \Beauty\exception\ComponentException
	 */
	function events()
	{
		return Beauty::$app->get('event');
	}

}


if (!function_exists('exif_imagetype')) {

	/**
	 * @param $name
	 * @return string
	 */
	function exif_imagetype($name)
	{
		return getType($name);
	}

	/**
	 * @param $file
	 * @return string
	 */
	function getFileType($file)
	{
		$fp = fopen($file, "rb");
		$bin = fread($fp, 2); //只读2字节
		fclose($fp);
		// unpack() 函数从二进制字符串对数据进行解包
		$str_info = @unpack("C2chars", $bin);
		//  intval() 函数用于获取变量的整数值
		$type_code = intval($str_info['chars1'] . $str_info['chars2']);
		$file_type = '';
		// 下面将解析后获取的状态值进行判断
		switch ($type_code) {
			case 7790:
				$file_type = 'exe';
				break;
			case 7784:
				$file_type = 'midi';
				break;
			case 8075:
				$file_type = 'zip';
				break;
			case 8297:
				$file_type = 'rar';
				break;
			case 255216:
				$file_type = 'jpg';
				break;
			case 7173:
				$file_type = 'gif';
				break;
			case 6677:
				$file_type = 'bmp';
				break;
			case 13780:
				$file_type = 'png';
				break;
			default:
				$file_type = 'unknown';
				break;
		}
		return $file_type;
	}
}
