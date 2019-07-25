<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/4/27 0027
 * Time: 14:02
 */

namespace Beauty\core;


use Qiniu\Auth;
use Qiniu\Config;
use Qiniu\Storage\BucketManager;
use Qiniu\Storage\UploadManager;
use Beauty\base\Component;


/**
 * Class Qn
 * @package Beauty\core
 *
 * @method static uploader($tmpFile, $newName)
 * @method static exists($hashKey)
 * @method static remove($hashKey)
 * @method static getState($hashKey)
 * @method static batchRemove(array $hashKeys)
 */
class Qn extends Component
{

	public $access_key;

	public $secret_key;

	public $bucket;

	/** @var Auth */
	private $auth;

	/** @var string $token */
	private $token;

	/**
	 * @return string
	 */
	private function rungetToken()
	{
		if (!$this->auth) {
			$this->auth = new Auth($this->access_key, $this->secret_key);
		}
		return $this->auth->uploadToken($this->bucket);
	}

	/**
	 * @param $tmpFile
	 * @param $newName
	 * @return array
	 * @throws
	 */
	private function runuploader($tmpFile, $newName)
	{
		$uploadMgr = new UploadManager();
		$this->token = $this->rungetToken();
		return $uploadMgr->putFile($this->token, $newName, $tmpFile);
	}

	/**
	 * @param $key
	 * @return bool
	 */
	private function runexists($key)
	{
		$bucketMgr = new BucketManager($this->auth);
		list($ret, $err) = $bucketMgr->stat($this->bucket, $key);
		if ($err === NULL) {
			return TRUE;
		} else {
			return FALSE;
		}
	}


	/**
	 * @param string $hashKey
	 * @return bool
	 */
	private function runremove(string $hashKey)
	{
		$bucketManager = new BucketManager($this->auth);
		$err = $bucketManager->delete($this->bucket, $hashKey);
		if ($err) {
			return false;
		}
		return true;
	}

	/**
	 * @param string $hashKey
	 * @return bool
	 * 获取文件信息
	 */
	private function rungetState(string $hashKey)
	{
		$bucketManager = new BucketManager($this->auth);
		list($fileInfo, $err) = $bucketManager->stat($this->bucket, $hashKey);
		if ($err) {
			return false;
		}
		return $fileInfo;
	}

	/**
	 * @param array $hashKeys
	 * @return bool
	 * 批量删除
	 */
	private function runbatchRemove(array $hashKeys)
	{
		$bucketManager = new BucketManager($this->auth);
		$ops = $bucketManager->buildBatchDelete($this->bucket, $hashKeys);
		list($fileInfo, $err) = $bucketManager->batch($ops);
		if ($err) {
			return false;
		}
		return $fileInfo;
	}

	/**
	 * @param $name
	 * @param $arguments
	 * @return mixed
	 * @throws
	 */
	public static function __callStatic($name, $arguments)
	{
		$service = \Beauty::getApp('qn');

		return $service->{'run' . $name}(...$arguments);
	}
}
