<?php
/**
 * Created by PhpStorm.
 * User: qv
 * Date: 2018/10/10 0010
 * Time: 15:22
 */

namespace Yoc\gii;


class Service extends BGii
{

    private $path = APP_PATH . '/data';

    private $params = [];

    public function __construct($params)
    {
        $this->params = $params;
    }


    public function setPath($path)
    {
        $this->path = $path;
        return $this;
    }

    public function getPath()
    {
        return realpath($this->path);
    }


}
