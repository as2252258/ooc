<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2019-03-18
 * Time: 14:54
 */

namespace Yoc\http;


class HttpHeaders
{

	private $headers = [];

	private $response = [];

	/**
	 * HttpHeaders constructor.
	 * @param $headers
	 */
	public function __construct($headers)
	{
		$this->headers = $headers;
	}

	/**
	 * @param $name
	 * @param $value
	 */
	public function setHeader($name, $value)
	{
		$this->response[$name] = $value;
	}

	/**
	 * @param array $headers
	 */
	public function setHeaders(array $headers)
	{
		foreach ($headers as $key => $val) {
			$this->response[$key] = $val;
		}
	}

	/**
	 * @return array
	 */
	public function getResponseHeaders()
	{
		return $this->response;
	}

	/**
	 * @param $name
	 * @return mixed|null
	 */
	public function getHeader($name)
	{
		return $this->headers[$name] ?? null;
	}

	/**
	 * @return array
	 */
	public function getHeaders()
	{
		return $this->headers;
	}

}
