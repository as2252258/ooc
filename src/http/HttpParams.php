<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2019-03-18
 * Time: 14:54
 */

namespace Beauty\http;

use Beauty\exception\RequestException;
use Exception;


class HttpParams
{

	/** @var array */
	private $body = [];

	/** @var array */
	private $gets = [];

	/** @var array */
	private $files = [];

	/**
	 * HttpParams constructor.
	 * @param $body
	 * @param $get
	 * @param $files
	 */
	public function __construct($body, $get, $files)
	{
		$this->body = $body ?? [];
		$this->gets = $get ?? [];
		$this->files = $files ?? [];
	}

	/**
	 * @return int
	 */
	public function offset()
	{
		return ($this->page() - 1) * $this->size();
	}

	public function setPosts(array $data)
	{
		foreach ($data as $key => $vla) {
			$this->body[$key] = $vla;
		}
	}

	/**
	 * @return int
	 */
	private function page()
	{
		return (int)$this->get('page', 1);
	}

	/**
	 * @return int
	 */
	public function size()
	{
		return (int)$this->get('size', 20);
	}


	/**
	 * @param $name
	 * @param $defaultValue
	 * @param $call
	 * @return mixed|null
	 */
	public function get($name, $defaultValue = null, $call = null)
	{
		$data = $this->gets[$name] ?? $defaultValue;
		if ($call !== null) {
			$data = call_user_func($call, $data);
		}
		return $data;
	}

	/**
	 * @param $name
	 * @param null $defaultValue
	 * @param $call
	 * @return mixed|null
	 */
	public function post($name, $defaultValue = null, $call = null)
	{
		$data = $this->body[$name] ?? $defaultValue;
		if ($call !== null) {
			$data = call_user_func($call, $data);
		}
		return $data;
	}

	/**
	 * @return array
	 */
	public function gets()
	{
		return $this->gets;
	}

	/**
	 * @return array
	 */
	public function params()
	{
		return array_merge($this->body, $this->files);
	}

	/**
	 * @return array
	 */
	public function load()
	{
		return array_merge(
			$this->files,
			$this->body,
			$this->gets
		);
	}

	/**
	 * @param $name
	 * @param array $defaultValue
	 * @return array|mixed
	 */
	public function array($name, $defaultValue = [])
	{
		return $this->body[$name] ?? $defaultValue;
	}

	/**
	 * @param $name
	 * @return mixed|null
	 */
	public function file($name)
	{
		return $this->files[$name] ?? null;
	}

	/**
	 * @param      $name
	 * @param bool $isNeed
	 * @param null $min
	 * @param null $max
	 * @return int
	 * @throws Exception
	 */
	public function int($name, $isNeed = FALSE, $min = NULL, $max = NULL)
	{
		$int = $this->body[$name] ?? NULL;
		if ($int === NULL) {
			if ($isNeed === true) {
				throw new RequestException("You need to add request parameter $name");
			}
			if (is_numeric($isNeed)) {
				return (int)$isNeed;
			}

			return (int)$int;
		}


		if (!is_numeric($int) || intval($int) != $int) {
			throw new RequestException("The request parameter $name must integer.");
		}
		if ($min !== NULL && $int < $min) {
			throw new RequestException("The minimum value cannot be lower than $min");
		}
		if ($max !== NULL && $int > $max) {
			throw new RequestException("Maximum cannot exceed $min");
		}

		return (int)$int;
	}

	/**
	 * @param      $name
	 * @param bool $isNeed
	 * @param null $length
	 *
	 * @return string
	 * @throws
	 */
	public function string($name, $isNeed = FALSE, $length = NULL)
	{
		$int = $this->body[$name] ?? NULL;
		if ($int === NULL && $isNeed) {
			throw new RequestException("You need to add request parameter $name");
		}
		$_length = strlen($int);
		if (is_array($length)) {
			if (count($length) < 2) {
				array_unshift($length, 0);
			}
			list($min, $max) = $length;
			if ($min !== NULL && $_length < $min) {
				throw new RequestException("The minimum value cannot be lower than $min");
			}
			if ($max !== NULL && $_length > $max) {
				throw new RequestException("Maximum cannot exceed $min");
			}
		} else if (is_numeric($length)) {
			if ($_length != $length) {
				throw new RequestException("The length of the string must be $length characters");
			}
		}
		return $int;
	}

	/**
	 * @param      $name
	 * @param bool $isNeed
	 * @param null $length
	 *
	 * @return string
	 * @throws RequestException
	 */
	public function email($name, $isNeed = FALSE)
	{
		$email = $this->body[$name] ?? NULL;
		if ($email === NULL && $isNeed) {
			throw new RequestException("You need to add request parameter $name");
		}
		if (!preg_match('/^\w+([\.\-\_]\w+)+@\w+(\.\w+){1,}$/', $email)) {
			throw new RequestException("Request parameter $name is in the wrong format", 4001);
		}
		return $email;
	}


	/**
	 * @param      $name
	 * @param bool $isNeed
	 * @param null $length
	 *
	 * @return string
	 * @throws RequestException
	 */
	public function bool($name, $isNeed = FALSE)
	{
		$email = $this->body[$name] ?? NULL;
		if ($email === NULL && $isNeed) {
			throw new RequestException("You need to add request parameter $name");
		}
		return $email == 'true' ? true : false;
	}

	/**
	 * @param      $name
	 * @param null $default
	 *
	 * @return mixed|null
	 * @throws \Beauty\exception\RequestException
	 */
	public function timestamp($name, $default = NULL)
	{
		$value = $this->body[$name] ?? NULL;
		if (!empty($value)) {
			if (!is_numeric($value)) {
				throw new RequestException('The request param :attribute not is a timestamp value');
			}
			if (strlen((string)$value) != 10) {
				throw new RequestException('The request param :attribute not is a timestamp value');
			}
			if (!date('YmdHis', $value)) {
				throw new RequestException('The request param :attribute format error', 4001);
			}
			return $value;
		} else {
			return $default;
		}
	}

	/**
	 * @param      $name
	 * @param null $default
	 *
	 * @return mixed|null
	 * @throws \Beauty\exception\RequestException
	 */
	public function datetime($name, $default = NULL)
	{
		$value = $this->body[$name] ?? NULL;
		if ($value != NULL) {
			$match = '/^\d{4}.*?([1-12]).*([1-31]).*?[0-23].*?[0-59].*?[0-59].*?$/';
			$match = preg_match($match, $value, $result);
			if (!$match || $result[0] != $value) {
				throw new RequestException('The request param :attribute format error', 4001);
			}
			return $value;
		} else {
			return $default;
		}
	}


	/**
	 * @param $name
	 * @return mixed|null
	 *
	 * \Input()->account
	 * \Input()->password
	 */
	public function __get($name)
	{
		$load = $this->load();

		return $load[$name] ?? null;
	}

}
