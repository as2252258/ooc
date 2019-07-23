<?php
/**
 * Created by PhpStorm.
 * User: dell
 * Date: 2019/1/14 0014
 * Time: 13:50
 */

namespace Beauty\core;


class DateFormat
{
	
	private static function check($time)
	{
		if ($time === null) {
			$time = time();
		} else if (is_numeric($time)) {
			$length = strlen(floatval($time));
			if ($length != 10 && $length != 13) {
				return false;
			}
		} else if (is_string($time)) {
			$time = strtotime($time);
		}
		
		if (date('Y-m-d', $time)) {
			return $time;
		}
		return false;
	}
	
	
	/**
	 * @param null $time
	 * @return bool|false|int
	 *
	 * 获取指定日期当周第一天的时间
	 */
	public static function getWeekCurrentDay($time = null)
	{
		if (!($time = static::check($time))) {
			return false;
		}
		
		$time = strtotime('-' . (date('N') - 1) . 'days', $time);
		
		return strtotime(date('Y-m-d'), $time);
	}
	
	
	/**
	 * @param null $time
	 * @return bool|false|int
	 *
	 * 获取指定日期当月第一天的时间
	 */
	public static function getMonthCurrentDay($time = null)
	{
		if (!($time = static::check($time))) {
			return false;
		}
		
		return strtotime(date('Y-m', $time) . '-01');
	}
	
	/**
	 * @param $time
	 * @return bool|false|int|string
	 * 指定的月份有几天
	 */
	public static function getMonthTotalDay($time)
	{
		if (!($time = static::check($time))) {
			return false;
		}
		
		$time = date('t', $time);
		
		return $time;
	}
}
