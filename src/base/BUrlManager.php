<?php
/**
 * Created by PhpStorm.
 * User: qv
 * Date: 2018/10/9 0009
 * Time: 9:27
 */

namespace Beauty\base;


use Beauty\di\Service;
use Beauty\exception\RequestException;
use Beauty\http\Request;

abstract class BUrlManager extends Service
{
    public $default = 'site/index';

    public $enablePrettyUrl = FALSE;
    public $showScriptName = FALSE;
    public $suffix = '';

    /** @var \Beauty\web\Controller $controller */
    public $controller;

    /**
     * @param $param
     * @return mixed
     * @throws RequestException
     */
    public function runWithParam($param)
    {
        return $this->controller->getAction()->runWithParam($param);
    }

    /**
     * @throws RequestException
     * @throws \ReflectionException|\Exception
     */
    public function createController()
    {
        list($control, $action) = $this->resolveUrl();
        $control = $this->namespace . $control . 'Controller';

        /** @var Controller $control */
        if (!class_exists($control, TRUE)) {
            throw new RequestException("{$control} Class Not Find.", 404);
        }
        $control = new \ReflectionClass($control);
        if (!$control->isInstantiable()) {
            throw new RequestException("The page not find.", 404);
        }

        $control = $control->newInstance();

        /** @var \Beauty\web\Controller $control */
        $control->id = $control;

        $control->action = $control->createAction($action);

        return $this->controller = $control;
    }

    /**
     * @return array|string
     * @throws \Exception
     *
     * 路由解析
     */
    public function resolveUrl()
    {
        /** @var Request $request */
        $request = \Beauty::$app->request;
        $uri = $this->before($request->headers->getHeader('request_uri'));
        $path = explode('/', ltrim($uri, '/'));
        if (count($path) < 2) {
            $action = 'index';
        } else {
            $action = $path[count($path) - 1];
            unset($path[count($path) - 1]);
        }
        $path[count($path) - 1] = ucfirst($path[count($path) - 1]);
        $controller = implode('\\', $path);
        if (strpos($action, '-') !== FALSE) {
            $action = $this->explode($action);
        }
        if (strpos($controller, '-') !== FALSE) {
            $controller = $this->explode($controller);
        }
        return [$controller, $action];
    }

    /**
     * @param $controller
     * @return string
     * 横线分割
     */
    private function explode($controller)
    {
        foreach (explode('-', $controller) as $val) {
            $_control[] = ucfirst($val);
        }
        if (isset($_control)) {
            $controller = implode($_control);
        }
        return $controller;
    }

    /**
     * @param $uri
     * @return mixed
     * @throws \Exception
     */
    private function before($uri)
    {
        if (empty($uri) || $uri == '/') {
            return $this->default;
        }
        return str_replace($this->suffix, '', $uri);
    }
}
