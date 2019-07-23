<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/4/8 0008
 * Time: 17:51
 */

namespace Beauty\http\formatter;


use Beauty\base\Component;
use Beauty\core\ArrayAccess;

class HtmlFormatter extends Component implements IFormatter
{

	public $data;

	/** @var \swoole_http_response */
	public $status;

	public $header = [];

	/**
	 * @throws \Exception
	 */
	public function send($data)
	{
		if (!is_string($data)) {
			$data = ArrayAccess::toArray($this->data);
			if (is_array($data)) {
				$data = json_encode($data, JSON_UNESCAPED_UNICODE);
			}
		}

		$this->data = $data;
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getData()
	{
		$data = $this->data;
		$this->clear();
		return $data;
	}

	public function clear()
	{
		unset($this->data);
	}
}
