<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/5/24 0024
 * Time: 11:50
 */

namespace Yoc\base;

use Yoc\exception\ConfigException;


/**
 * Class Config
 * @package Yoc\base
 */
class Config extends Component
{

    const ERROR_MESSAGE = 'The not find :key in app configs.';

    public $data;

    /**
     * @param $key
     * @param bool $try
     * @return null
     * @throws ConfigException
     */
    public static function get($key, $try = FALSE)
    {
        $config = \Yoc::$app->config;
        if (isset($config->data[$key])) {
            return $config->data[$key];
        } else if ($try) {
            throw new ConfigException("The not find $key in app configs.");
        }
        return NULL;
    }

    /**
     * @param $key
     * @param $value
     * @return mixed
     */
    public static function set($key, $value)
    {
        $config = \Yoc::$app->config;
        return $config->data[$key] = $value;
    }

    /**
     * @param $key
     * @return bool
     */
    public static function has($key)
    {
        $config = \Yoc::$app->config;
        return isset($config->data[$key]);
    }

    /**
     * @param $name
     * @param $value
     */
    public function __set($name, $value)
    {
        $this->data[$name] = $value;
    }
}
