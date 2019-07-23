<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2019-03-20
 * Time: 01:03
 */

namespace Beauty\core;


class Xml
{

	/**
	 * @param $data
	 * @return array|object
	 */
	public static function toArray($data, $asArray = true)
	{
		$data = simplexml_load_string($data, 'SimpleXMLElement', LIBXML_NOCDATA);
		if ($asArray) {
			return json_decode(json_encode($data), TRUE);
		}

		return json_decode(json_encode($data));
	}

	/**
	 * @param $str
	 * @return array|bool|object
	 */
	public static function isXml(&$str)
	{
		$xml_parser = xml_parser_create();
		if (!xml_parse($xml_parser, $str, true)) {
			xml_parser_free($xml_parser);
			return false;
		} else {
			$str = self::toArray($str);
			return true;
		}
	}

}
