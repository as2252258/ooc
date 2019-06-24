<?php
/**
 * Created by PhpStorm.
 * User: qv
 * Date: 2018/10/10 0010
 * Time: 15:03
 */

namespace Yoc\http;


class Kafka
{

    private static $instance;

    private $client;

	/**
	 * Kafka constructor.
	 */
    private function __construct()
    {
        $this->client = new \swoole_client(SWOOLE_TCP);
    }

    /**
     * @return static
     */
    public static function getInstance()
    {
        if (!static::$instance) {
            static::$instance = new self();
        }
        return static::$instance;
    }

    /**
     * @param $data
     * @return string
     * @throws
     */
    public function send($data)
    {
        if (!$this->client->connect('127.0.0.1', '9900')) {
            throw new \Exception('服务器链接失败!');
        }
        $this->client->send(pack($data));
        return $this->client->recv();
    }
}
