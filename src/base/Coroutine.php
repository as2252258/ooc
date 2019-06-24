<?php
/**
 * Created by PhpStorm.
 * User: qv
 * Date: 2018/10/11 0011
 * Time: 15:54
 */

namespace Yoc\base;


use Swoole\Channel;

class Coroutine extends \Swoole\Coroutine
{

    private $callback = [];
    private $param = [];
    private $coroutineIds = [];
    private $result = [];

    private static $instance;

    private $name;


    /**
     * @return static
     */
    public static function getInstance()
    {
        if (!(static::$instance instanceof Coroutine)) {
            static::$instance = new self();
        }
        return static::$instance;
    }

    public function add($name, $callback, $param = [])
    {
        if (!is_array($param)) {
            $param = [$param];
        }
        $this->callback[$name] = $callback;
        $this->param[$name] = $param;
        return $this;
    }


    /**
     * @param string $name
     * @return bool|mixed
     * 运行一个携程
     */
    public function run(string $name)
    {
        if (!isset($this->callback[$name])) {
            return FALSE;
        }
        $param = $this->param[$name] ?? [];
        $callback = $this->callback[$name];
        $exec = function () use ($callback, $param, $name) {
            call_user_func($callback, ...$param);
            Coroutine::unset($name);
        };
        $this->coroutineIds[$name] = static::create($exec);
        return $this->coroutineIds[$name];
    }

    /**
     * @param $name
     * 删除执行完成的携程信息
     */
    public static function unset($name)
    {
        $class = static::getInstance();
        unset($class->param[$name], $class->coroutineIds[$name], $class->callback[$name]);
    }

	/**
	 * @param $name
	 */
    public function remove($name)
    {
        unset($this->param[$name], $this->coroutineIds[$name], $this->callback[$name]);
    }

    /**
     * @param $name
     * @return \Swoole\Coroutine
     * @throws \Exception
     */
    public function getCoroutine($name)
    {
        $coroutine = $this->coroutineIds[$name];
        if (!isset($coroutine)) {
            throw new \Exception('该携程暂时还未运行');
        }
        $this->name = $name;
        return $this;
    }

    /**
     * @param $name
     * @return mixed|null
     */
    public function getCoroutineId($name)
    {
        return $this->coroutineIds[$name] ?? NULL;
    }

    /**
     * @param $name
     * @return mixed|null
     * 返回数据
     */
    public function getData($name)
    {
        return $this->result[$name] ?? NULL;
    }
}
