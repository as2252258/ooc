<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2019-03-20
 * Time: 10:25
 */

namespace Yoc\error;


interface ErrorInterface
{


	public function sendError($messasge, $file, $line, $category = 'app');

}
