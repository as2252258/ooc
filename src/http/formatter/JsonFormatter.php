<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/4/8 0008
 * Time: 17:18
 */

namespace Beauty\http\formatter;


use Beauty\base\Component;
use Beauty\core\ArrayAccess;
use Beauty\core\JSON;

class JsonFormatter extends Component implements IFormatter
{
	public $data;

	public $status = 200;

	public $header = [];

	/**
	 * @throws \Exception
	 * 返回请求内容
	 */
	public function send($data)
	{
		if (is_array($data) || is_object($data)) {
			$data = ArrayAccess::toArray($data);
		}
		$this->data = JSON::encode($data);
		return $this;
	}

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
