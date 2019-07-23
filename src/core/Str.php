<?php

namespace Beauty\core;


use Beauty\base\BObject;

class Str extends BObject
{

	const STRING = 'abcdefghijklmnopqrstuvwxyz';

	const NUMBER = '01234567890';

	/**
	 * @param int $length
	 *
	 * @return string
	 * 获取随机字符串
	 */
	public static function rand(int $length = 20)
	{
		$string = '';
		if ($length < 1) $length = 20;
		$default = self::STRING . strtoupper(self::STRING) . self::NUMBER;
		$default = str_split($default);
		for ($i = 0; $i < $length; $i++) {
			$string .= $default[array_rand($default)];
		}
		return (string)$string;
	}

	/**
	 * @param int $length
	 *
	 * @return int
	 * 获取随机数字
	 */
	public static function random(int $length = 20)
	{
		$number = '';
		$default = str_split(self::NUMBER);
		if ($length < 1) $length = 1;
		for ($i = 0; $i < $length; $i++) {
			$number .= $default[array_rand($default)];
		}
		return $number;
	}

	/**
	 * @param        $string
	 * @param        $sublen
	 * @param bool $strip_tags
	 * @param string $append
	 *
	 * @return string
	 */
	public static function cut_str_utf8($string, $sublen, $strip_tags = true, $append = '...')
	{
		if ($strip_tags) {
			$string = strip_tags($string);
		}//去掉签标
		$pa = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|\xe0[\xa0-\xbf][\x80-\xbf]|[\xe1-\xef][\x80-\xbf][\x80-\xbf]|\xf0[\x90-\xbf][\x80-\xbf][\x80-\xbf]|[\xf1-\xf7][\x80-\xbf][\x80-\xbf][\x80-\xbf]/";
		preg_match_all($pa, $string, $t_string);
		$str = "";
		for ($i = 0; $i < count($t_string[0]); $i++) {
			$str .= $t_string[0][$i];
			//转为gbk，一个汉字长度为2
			if (strlen(@iconv('utf-8', 'gbk', $str)) >= $sublen) {
				if ($i != count($t_string[0]) - 1) $str .= $append;
				break;
			}
		}
		return $str;
	}

	/**
	 * @param $data
	 *
	 * @return bool
	 * 判断是否为json字符串
	 */
	public static function isJson($data, $callback = null)
	{
		$json = !is_null(json_decode($data)) && !is_numeric($data);
		if ($json && is_callable($callback, true)) {
			return call_user_func($callback, $data);
		}
		return $json;
	}

	/**
	 * @param $data
	 *
	 * @return bool
	 * 判断是否序列化字符串
	 */
	public static function isSerialize($data, $callBack = null)
	{
		$false = !empty($data) && unserialize($data) !== false;
		if ($false && is_callable($callBack, true)) {
			return call_user_func($callBack, $data);
		}
		return $false;
	}

	/**
	 * @param     $string
	 * @param int $length
	 *
	 * @return string
	 */
	public static function cut($string, int $length = 20, $append = '...')
	{
		if (empty($string)) {
			return '';
		}
		if ($length < 1) {
			$length = 1;
		}
		$array = str_split($string);
		if (count($array) <= $length) {
			return implode('', $array);
		}
		$string = implode('', array_slice($array, 0, $length));
		if (!empty($append)) {
			$string .= $append;
		}
		return $string;
	}

	/**
	 * @param        $str
	 * @param int $number
	 * @param string $key
	 *
	 * @return string
	 */
	public static function encrypt($str, $number = 10, $key = 'xshucai.com')
	{
		$res = [];
		$add = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
		$len = strlen($key) < 0 ? 1 : strlen($key) + 5 > strlen($add) ? strlen($add) - 5 : strlen($key);
		if ($number < 1) $number = 10;
		$array = str_split($str);
		asort($array);
		$str = implode('', $array);
		for ($i = 0; $i < $number; $i++) {
			$_tmp = md5($key) . md5($str) . mb_substr($add, $len, $len + 5, 'utf-8');
			$res[] = md5($_tmp);
		}
		sort($res, SORT_STRING);
		return hash('sha384', implode('', $res));
	}

	/**
	 * @param $file
	 * @param $type
	 * @return string
	 */
	public static function filename($file, $type)
	{
		switch ($type) {
			case 'image/png':
				return md5_file($file) . '.png';
			case 'image/jpeg':
			case 'image/jpg':
				return md5_file($file) . '.jpg';
			case 'image/gif':
				return md5_file($file) . '.gif';
				break;
		}
		return md5_file($file);
	}


	/**
	 * @param string $string
	 * @return string|string[]|null
	 * 清除标点符号
	 */
	public static function clear(string $string)
	{
		$char = '。、！？：；﹑•＂…‘’“”〝〞∕¦‖—　〈〉﹞﹝「」‹›〖〗】【»«』『〕〔》《﹐¸﹕︰﹔！¡？¿﹖﹌﹏﹋＇´ˊˋ―﹫︳︴¯＿￣﹢﹦﹤‐­˜﹟﹩﹠﹪﹡﹨﹍﹉﹎﹊ˇ︵︶︷︸︹︿﹀︺︽︾ˉ﹁﹂﹃﹄︻︼（）';
		return preg_replace(array("/[[:punct:]]/i", '/[' . $char . ']/u', '/[ ]{2,}/'), '', $string);
	}

}
