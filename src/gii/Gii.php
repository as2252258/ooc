<?php
/**
 * Created by PhpStorm.
 * User: qv
 * Date: 2018/10/10 0010
 * Time: 15:27
 */

namespace Yoc\gii;


use Yoc\http\Request;

class Gii extends BGii
{

    /**
     * @param \Yoc\http\Request $request
     *
     * @return array
     * @throws \Exception
     */
    public static function run(Request $request)
    {
        $gii = new static();
        if (!$gii->initData($request->get('t'))) {
            return $gii->fileList;
        };
        return $gii->createFiles();
    }

    /**
     * @throws \Exception
     */
    public function createFiles()
    {
        $request = \Yoc::$app->request;
        foreach ($this->tableName as $key => $val) {
            $structure = $this->resolveTableStructure($val, $this->fileList[$val]);
            if (!$request->get('c', NULL)) {
                (new Controller($val, $structure))->run();
            }
            if (!$request->get('m', NULL)) {
                (new Model($val, $structure))->run();
            }
            if (!$request->get('s', NULL)) {
                (new Controller($val, $structure))->run();
            }
        }
        return $this->fileList;
    }
}
