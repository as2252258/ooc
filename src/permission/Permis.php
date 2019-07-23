<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/10/19 0019
 * Time: 0:42
 */

namespace Beauty\permission;


use model\Auth;
use Beauty\base\Component;

class Permis extends Component
{

    public $controllerDir = APP_PATH . '/controller';
    public $namespace = 'controller';

    public $tables = [
        'auth' => 'permission_auth',
        'role' => 'permission_role',
        'group' => 'permission_group',
        'join' => 'permission_join',
    ];

    /**
     * @throws \Exception
     */
    public function createPermission(callable $callback)
    {
        $permission = [];
        foreach (glob($this->controllerDir . '/*') as $val) {
            if (is_dir($val)) {
                continue;
            }
            if (!strpos('.php', $val) === FALSE) {
                continue;
            }
            $class = $this->getControllerNamespace($val);
            list($reflect, $methods) = $this->getReflect($class);

            foreach ($methods as $key => $method) {
                /** @var \ReflectionMethod $method */
                if ($method->class != $class) {
                    continue;
                }
                $action = $method->getName();
                if (!preg_match('/^action\w+/', $action)) {
                    continue;
                }
                if ($action === 'actions') {
                    continue;
                }
                $action = str_replace('action', '', $action);
                if (empty($action)) {
                    continue;
                }
                $name = $this->getControllerName($class);

                call_user_func($callback, lcfirst($name), lcfirst($action), $name . '_' . $this->getAlias($method));

                $permission[] = $name . '/' . $action;
            }
        }

        return $permission;
    }

    /**
     * @param $file
     * @return string
     */
    private function getControllerNamespace($file)
    {
        $classFile = explode(DIRECTORY_SEPARATOR, $file);

        $classFile = str_replace('.php', '', $classFile[count($classFile) - 1]);

        return $this->namespace . '\\' . $classFile;
    }

    /**
     * @param $class
     * @return mixed
     */
    private function getControllerName($class)
    {
        $explode = explode('\\', $class);
        return str_replace('controller', '', $explode[count($explode) - 1]);
    }


    /**
     * @param $className
     * @return array[\Reflection,\ReflectionMethod[]]
     * @throws
     */
    private function getReflect($className)
    {
        $reflect = new \ReflectionClass($className);
        if (!$reflect->isInstantiable()) {
            throw new \Exception('Unable resolve class ' . $className);
        }
        return [$reflect, $reflect->getMethods(\ReflectionMethod::IS_PUBLIC)];
    }

    /**
     * @param \ReflectionMethod $method
     * @return string
     * 返回路由注解别名
     */
    private function getAlias(\ReflectionMethod $method)
    {
        $comment = $method->getDocComment();
        if (!$comment) {
            return $method->getName();
        }
        $preg = preg_match('/\@Alias\((\w+)\)/', $comment, $result);
        if (!$preg) return $method->getName();
        return $result[count($result) - 1];
    }
}
