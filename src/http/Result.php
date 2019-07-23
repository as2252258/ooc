<?php

namespace Beauty\http;

use Beauty\base\Component;

/**
 * Class Result
 *
 * @package app\components
 *
 * @property $code
 * @property $message
 * @property $count
 * @property $data
 */
class Result extends Component
{
	public $code;
	public $message;
	public $count = 0;
	public $data;
	public $header;

	public function __construct(array $data, $config = [])
	{
		parent::__construct($config);

		foreach ($data as $key => $val) {
			$this->$key = $val;
		}
	}

	public function __get($name)
	{
		return $this->$name;
	}


	public function __set($name, $value)
	{
		$this->$name = $value;

		return $this;
	}

	public function getHeaders()
	{
		$_tmp = [];
		foreach ($this->header as $key => $val) {
			if ($key == 0) {
				$_tmp['pro'] = $val;
			} else {
				$trim = explode(': ', $val);

				$_tmp[strtolower($trim[0])] = $trim[1];
			}
		}
		return $_tmp;
	}

	public function getTime()
	{
		return [
			'startTime' => $this->startTime,
			'requestTime' => $this->requestTime,
			'runTime' => $this->runTime,
		];
	}

	/**
	 * @param $key
	 * @param $data
	 * @return $this
	 * @throws \Exception
	 */
	public function setAttr($key, $data)
	{
		if (!property_exists($this, $key)) {
			throw new \Exception('未查找到相应对象属性');
		}
		$this->$key = $data;
		return $this;
	}

	public function isResultsOK()
	{
		return $this->code === 200;
	}

	/**
	 * @param string $name
	 * @return mixed
	 */
	public function getResponse()
	{
		$headers = $this->getHeaders();
		if (!isset($headers['content-type'])) {
			return $this->data;
		}
		if (!is_string($this->data)) {
			return $this->data;
		}
		switch (trim($headers['content-type'])) {
			case 'application/json; encoding=utf-8';
			case 'application/json;';
			case 'application/json';
			case 'text/plain';
				return json_decode($this->data, true);
				break;
		}
		return $this->data;
	}

	/**
	 * @param $key
	 * @param $data
	 * @return $this
	 */
	public function append($key, $data)
	{
		$this->data[$key] = $data;
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getMessage()
	{
		return $this->message;
	}

	/**
	 * @return mixed
	 */
	public function getCode()
	{
		return $this->code;
	}
}
