<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/4/4 0004
 * Time: 14:57
 */

namespace Yoc\core;


use Yoc\db\ActiveRecord;
use Yoc\db\Collection;

class ArrayAccess
{

	/**
	 * @param $data
	 * @return array
	 * @throws \Exception
	 */
	public static function toArray($data)
	{
		$tmp = [];
		if (!is_array($data) && !is_object($data)) {
			return [];
		}
		if (is_object($data)) {
			if ($data instanceof Collection) {
				$data = $data->toArray();
			} else if ($data instanceof ActiveRecord) {
				$data = $data->toArray();
			} else {
				$data = get_object_vars((object)$data);
			}
		}
		foreach ($data as $key => $val) {
			if (is_array($val) || is_object($val)) {
				$tmp[$key] = self::toArray($val);
			} else {
				$tmp[$key] = $val;
			}
		}
		return $tmp;
	}

}
