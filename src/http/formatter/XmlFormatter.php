<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/4/8 0008
 * Time: 17:29
 */

namespace Beauty\http\formatter;


use Beauty\base\Component;
use Beauty\core\ArrayAccess;

class XmlFormatter extends Component implements IFormatter
{

	public $data = '';

	/** @var \swoole_http_response */
	public $status;

	public $header = [];

	/**
	 * @throws \Exception
	 */
	public function send($data)
	{
		if (!is_string($data)) {
			// TODO: Implement send() method.
			$dom = new \SimpleXMLElement('<root/>');
			if ($data != NULL) {
				$data = ArrayAccess::toArray($data);
			}
			$this->toXml($dom, $data);

			$this->data = $dom->saveXML();
		}
		return $this;
	}

	/**
	 * @return string
	 */
	public function getData()
	{
		$data = $this->data;
		$this->clear();
		return $data;
	}

	/**
	 * @param \SimpleXMLElement $dom
	 * @param $data
	 */
	public function toXml($dom, $data)
	{
		foreach ($data as $key => $val) {
			if (is_numeric($key)) {
				$key = 'item' . $key;
			}
			if (is_array($val)) {
				$node = $dom->addChild($key);
				$this->toXml($node, $val);
			} else if (is_object($val)) {
				$val = get_object_vars($val);
				$node = $dom->addChild($key);
				$this->toXml($node, $val);
			} else {
				$dom->addChild($key, htmlspecialchars($val));
			}
		}
	}

	public function clear()
	{
		unset($this->data);
	}
}
