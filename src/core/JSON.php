<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2019-03-20
 * Time: 01:04
 */

namespace Yoc\core;


class JSON
{

	/**
	 * @param $data
	 * @return false|string
	 * @throws \Exception
	 */
	public static function encode($data)
	{
		if (empty($data)) {
			return $data;
		}
		if (is_string($data) || is_numeric($data)) {
			return $data;
		}
		$data = ArrayAccess::toArray($data);

		return json_encode($data, JSON_UNESCAPED_UNICODE);
	}


	/**
	 * @param $data
	 * @param bool $asArray
	 * @return mixed
	 */
	public static function decode($data, $asArray = true)
	{
		return json_decode($data, $asArray);
	}

	/**
	 * @param $code
	 * @param string $message
	 * @param array $data
	 * @param int $count
	 * @param array $exPageInfo
	 * @return mixed
	 * @throws
	 */
	public static function to($code, $message = '', $data = [], $count = 0, $exPageInfo = [])
	{
		$params['code'] = $code;
		if (!is_string($message)) {
			$params['param'] = $message;
			if (!empty($data)) {
				$params['exPageInfo'] = $data;
			}
			$params['message'] = 'System success.';
		} else {
			$params['message'] = $message;
			$params['param'] = $data;
		}
		if (!empty($exPageInfo)) {
			$params['exPageInfo'] = $exPageInfo;
		}
		$params['count'] = $count;
		if (is_numeric($data) || !is_numeric($count)) {
			$params['count'] = $data;
			$params['exPageInfo'] = $count;
		}

		ksort($params, SORT_ASC);

		return static::encode($params);
	}

}
