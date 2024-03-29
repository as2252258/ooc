<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/5/2 0002
 * Time: 14:51
 */

namespace Beauty\cache;


use Beauty\base\Component;

class File extends Component implements ICache
{
	public $path;
	
	/**
	 * @throws \Exception
	 */
	public function init()
	{
		parent::init(); // TODO: Change the autogenerated stub
		
		if (!\Beauty::$app->runtimePath) {
			\Beauty::$app->runtimePath = APP_PATH . '/runtime';
			if (!is_dir(\Beauty::$app->runtimePath)) {
				mkdir(\Beauty::$app->runtimePath, 775);
			}
		}
		
		if (empty($this->path)) {
			$this->path = \Beauty::$app->runtimePath . '/data';
		}
		
		if (!is_dir($this->path)) {
			mkdir($this->path, 775);
		}
		
		if (!is_writeable($this->path)) {
			throw new \Exception("Directory has no write permission: {$this->path} .");
		}
	}
	
	/**
	 * @param $key
	 * @param $value
	 */
	public function setCache($key, $value)
	{
		$value = serialize($value);
		$tmpFile = $this->path . '/' . $key;
		if (!file_exists($tmpFile)) {
			touch($tmpFile);
		}
		file_put_contents($tmpFile, $value);
	}
	
	/**
	 * @param $key
	 * @return mixed|null
	 */
	public function get($key)
	{
		$tmpFile = $this->path . '/' . $key;
		if (!file_exists($tmpFile)) {
			return NULL;
		}
		$content = file_get_contents($tmpFile);
		return unserialize($content);
	}
}
