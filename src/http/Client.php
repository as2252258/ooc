<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/5/24 0024
 * Time: 11:34
 */

namespace Yoc\http;


use Yoc\base\Component;
use \Swoole\Coroutine\Http\Client as SClient;

class Client extends Component
{
	private $header = [];
	private $url = 'https://api.weixin.qq.com';

	/**
	 * @param $url
	 * @param string $pushType
	 * @param array $data
	 * @param array $header
	 * @return mixed
	 * @throws \Exception
	 */
	private static function run($url, $pushType = 'get', $data = [], $header = [])
	{
		/** @var Client $_class */
		static $_class = NULL;
		if ($_class == NULL) $_class = \Yoc::createObject(Client::class);
		return $_class->request($url, $pushType, $data, $header);
	}

	/**
	 * @param        $url
	 * @param string $pushType
	 * @param array $data
	 * @param array $header
	 *
	 * @return Result
	 */
	private function request($url, $pushType = 'get', $data = [], array $header = NULL)
	{

		if (
			strpos($url, 'http://') === FALSE &&
			strpos($url, 'https://') === FALSE
		) {
			$url = $this->url . '/' . $url;
		}
		if (!empty($header)) {
			$this->setHeaders($header);
		}

		if (getIsCli() || getIsCommand()) {
			return $this->continue($this->url, $url, $pushType, $data);
		}

		return $this->curl_push($url, $pushType, $data);
	}

	/**
	 * @param        $url
	 * @param string $type
	 * @param array $data
	 *
	 * @return Result
	 * curl请求
	 */
	private function curl_push($url, $type = 'get', $data = [])
	{
		$_data = $this->paramEncode($data);
		if ($type == 'get' && is_array($_data)) {
			$url .= '?' . http_build_query($_data);
		}

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_TIMEOUT, 5);// 超时设置
		curl_setopt($ch, CURLOPT_HEADER, TRUE);
		if (!empty($this->header)) {
			curl_setopt($ch, CURLOPT_HTTPHEADER, $this->header);
		}
		curl_setopt($ch, CURLOPT_NOBODY, FALSE);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);// 超时设置
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);//返回内容
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);// 跟踪重定向
		curl_setopt($ch, CURLOPT_ENCODING, 'gzip,deflate');
		switch (strtolower($type)) {
			case 'post':
				curl_setopt($ch, CURLOPT_POST, 1);
				if (!is_string($_data)) {
					$_data = http_build_query($_data);
				}
				curl_setopt($ch, CURLOPT_POSTFIELDS, $_data);
				break;
			case 'delete':
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
				curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($_data));
				break;
			case 'put':
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
				curl_setopt($ch, CURLOPT_POSTFIELDS, $_data);
				break;
			default:
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
		}
		$output = curl_exec($ch);
		if ($output === FALSE) {
			return new Result(['code' => 500, 'message' => curl_error($ch)]);
		}
		curl_close($ch);

		$this->header = [];

		list($header, $body) = explode("\r\n\r\n", $output, 2);
		$header = explode(PHP_EOL, $header);
		$status = (int)explode(' ', trim($header[0]))[1];

		$data = $this->headerFormat($header);

		if ($status !== 200) {
			$body = ['code' => 500, 'message' => $body, 'header' => $data];
		} else {
			$body = [
				'code' => $status, 'message' => 'Client Success!',
				'data' => $this->resolve($data, $body), 'header' => $header
			];
		}
		return new Result($body);
	}

	/**
	 * @param $host
	 * @param $url
	 * @param string $type
	 * @param array $data
	 * @return mixed
	 */
	public function continue($host, $url, $type = 'get', $data = [])
	{
		$host = \Co::gethostbyname($host);
		$client = new SClient($host, 443, true);
		if (!empty($this->header)) {
			$client->setHeaders($this->header);
		}

		if ($type == 'get') {
			if (!empty($data)) {
				$client->setData($data);
			}
			$client->get($url);
		} else {
			$client->post($url, $data);
		}

		$header = $client->getHeaders();
		$result = $this->resolve($header, $client->body);
		$client->close();
		return $result;
	}

	/**
	 * @param $data
	 * @param $body
	 * @return mixed
	 */
	private function resolve($data, $body)
	{
		switch ($data['Content-Type']) {
			case 'application/json; charset=utf-8':
			case 'application/json;charset=utf-8':
			case 'application/json;':
			case 'application/json':
			case 'text/json; charset=utf-8':
			case 'text/json;charset=utf-8':
			case 'text/json;':
			case 'text/json':
			case 'text/plain':
				$body = json_decode($body, true);
				break;
			case 'text/xml':
			case 'application/xml':
				$data = simplexml_load_string($body, 'SimpleXMLElement', LIBXML_NOCDATA);
				$body = json_decode(json_encode($data), TRUE);
				break;
		}
		return $body;
	}

	/**
	 * @param $headers
	 * @return array
	 */
	private function headerFormat($headers)
	{
		$_tmp = [];
		foreach ($headers as $key => $val) {
			$trim = explode(': ', trim($val));

			$_tmp[$trim[0]] = $trim[1] ?? '';
		}
		return $_tmp;
	}

	/**
	 * @param        $arr
	 * @param string $pushType
	 *
	 * @return array|string
	 * 将请求参数进行编码
	 */
	private function paramEncode($arr, $pushType = 'post')
	{
		if (!is_array($arr)) {
			return $arr;
		}
		$_tmp = [];
		foreach ($arr as $Key => $val) {
			$_tmp[$Key] = $val;
		}

		return ($pushType == 'post' ? $_tmp : http_build_query($_tmp));
	}

	/**
	 * @param $url
	 * @param $filePath
	 * @return Result
	 * @throws \Exception
	 */
	public static function upload($url, $filePath)
	{
		$data = [
			'media' => new \CURLFile(realpath($filePath)),
			'form-data[filename]' => realpath($filePath),
			'form-data[content-type]' => 'image/png'
		];
		return static::run($url, 'post', $data, ['Content-Type' => 'application/json']);
	}

	/**
	 * @param $url
	 * @param array $data
	 * @param array|NULL $header
	 * @return Result
	 * @throws \Exception
	 */
	public static function post($url, $data = [], array $header = NULL)
	{
		return static::run($url, 'post', $data, $header);
	}

	/**
	 * @param $url
	 * @param array $data
	 * @param array $header
	 * @return Result
	 * @throws \Exception
	 */
	public static function get($url, $data = [], $header = [])
	{
		return static::run($url, 'get', $data, $header);
	}

	/**
	 * @param $url
	 * @param array $data
	 * @param array $header
	 * @return mixed
	 * @throws \Exception
	 */
	public static function option($url, $data = [], $header = [])
	{
		return static::run($url, 'option', $data, $header);
	}

	/**
	 * @param $url
	 * @param array $data
	 * @param array $header
	 * @return mixed
	 * @throws \Exception
	 */
	public static function delete($url, $data = [], $header = [])
	{
		return static::run($url, 'delete', $data, $header);
	}

	/**
	 * @param array $headers
	 * @return array
	 */
	public function setHeaders(array $headers)
	{
		if (empty($headers)) {
			return [];
		}
		foreach ($headers as $key => $val) {
			$str = $key . ':' . $val;
			if (in_array($str, $this->header)) {
				continue;
			}
			$this->header[] = $str;
		}
		return $this->header;
	}
}
