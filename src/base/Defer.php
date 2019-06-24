<?php
/**
 * Created by PhpStorm.
 * User: qv
 * Date: 2018/7/12 0012
 * Time: 9:37
 */

namespace Yoc\base;


class Defer
{

    private static $_defer = [];

	/**
	 * @param callable $callback
	 * @param array $param
	 */
    public static function add(callable $callback, array $param)
    {
        $fd = \Yoc::$app->request->fd;
        if (!is_array($param)) {
            return;
        }
        if (!isset(static::$_defer[$fd])) {
            static::$_defer[$fd][] = [$callback, $param];
        } else {
            array_unshift(static::$_defer[$fd], [$callback, $param]);
        }
    }

    public static function try()
    {
        $fd = \Yoc::$app->request->fd;
        if (count(static::$_defer[$fd]) <= 0) {
            return;
        }
        foreach (static::$_defer[$fd] as $value) {
            call_user_func($value[0], ...$value[1]);
        }
        static::clear($fd);
    }


	/**
	 * @param null $fd
	 */
    public static function clear($fd = NULL)
    {
        if ($fd != NULL) {
            static::$_defer[$fd] = [];
        } else {
            static::$_defer = [];
        }
    }

	/**
	 * @param null $fd
	 * @return bool
	 */
    public static function hasDefer($fd = NULL)
    {
        if ($fd) {
            return isset(static::$_defer[$fd]) ? !(boolean)count(static::$_defer[$fd]) : FALSE;
        } else {
            return static::$_defer ? !(boolean)count(static::$_defer) : FALSE;
        }
    }
}
