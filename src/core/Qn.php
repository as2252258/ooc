<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/4/27 0027
 * Time: 14:02
 */

namespace Beauty\core;


use Qiniu\Auth;
use Qiniu\Storage\BucketManager;
use Qiniu\Storage\UploadManager;
use Beauty\base\Component;

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
    public function getToken()
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
    public function uploader($tmpFile, $newName)
    {
        $uploadMgr = new UploadManager();
        $this->token = $this->getToken();
        return $uploadMgr->putFile($this->token, $newName, $tmpFile);
    }

    /**
     * @param $key
     * @return bool
     */
    public function exists($key)
    {
        $bucketMgr = new BucketManager($this->auth);
        list($ret, $err) = $bucketMgr->stat($this->bucket, $key);
        if ($err === NULL) {
            return TRUE;
        } else {
            return FALSE;
        }
    }
}
