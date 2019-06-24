<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/10/7 0007
 * Time: 2:17
 */

namespace Yoc\console;


use Yoc\base\Controller;
use Yoc\http\formatter\IFormatter;
use Yoc\web\Action;

class Command extends Controller
{
    /** @var string */
    public $id;

    /** @var Action */
    public $action;

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return Action
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * @param $id
     * @return mixed|Action
     * @throws \ReflectionException
     */
    public function createAction($id)
    {
        $class = new Action();
        $class->id = $id;
        $class->controller = $this;
        return $class;
    }

    /**
     * @param mixed ...$param
     * @return string
     * @throws
     */
    public function output(...$param)
    {
        /** @var IFormatter $build */
        $build = \Yoc::$app->response->sender(...$param);
        if (!empty($build)) {
            print_r($build);
        }
        echo 'Command Success!' . PHP_EOL;
        exit;
    }

    /**
     * @param Action $action
     * @return bool
     */
    public function beforeAction(Action $action)
    {
        return TRUE;
    }

    /**
     * @param $action
     * @param $result
     * @return mixed
     */
    public function afterAction($action, $result = NULL)
    {
        return TRUE;
    }
}
