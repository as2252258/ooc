<?php
/**
 * Created by PhpStorm.
 * User: qv
 * Date: 2018/10/10 0010
 * Time: 15:28
 */

namespace Yoc\gii;


class Controller extends BGii
{

    private $table;

    /** @var Structure */
    private $struc;

    private $namespace = 'controller';
    private $path = APP_PATH . '/controller';

    private $oldContent = '';
    private $template = 'template/controller.template';

    public function __construct($table, Structure $struc)
    {
        $this->table = $table;
        $this->struc = $struc;
    }

    /**
     * @throws \Exception
     */
    public function run()
    {
        $template = file_get_contents($this->template);

        $className = $this->struc->getClassName();

        $content = strtr($template, [
            '{$model}' => $className, '{$service}' => $this->dataNamespace . $className,
        ]);

        $class = $this->namespace . '\\' . $className . 'controller';
        if (file_exists($path = $this->getPath($className))) {

            $oldClass = new \ReflectionClass($class);

            $this->oldContent = $this->getOldContext($className);

            @unlink($path);
        }

        $this->write($content, $className);

        if (isset($oldClass)) {
            $newClass = new \ReflectionClass($class);

            $content = $this->oldWrite($newClass, $oldClass, $content);
            if (!empty($content)) {
                if (is_array($content)) {
                    file_put_contents($this->getPath($className), implode(PHP_EOL, $content));
                } else if (is_string($content)) {
                    file_put_contents($this->getPath($className), $content);
                } else {
                    throw new \Exception('无法解析的内容');
                }
            }
        }
    }

    /**
     * @param $newClass
     * @param $oldClass
     * @param $newContent
     * @return array
     * @throws
     */
    private function oldWrite($newClass, $oldClass, $newContent)
    {
        $newMethods = $this->getMethods($newClass);
        $oldMethods = $this->getMethods($oldClass);

        //新数据
        $explode = explode(PHP_EOL, $newContent);
        $oldContent = explode(PHP_EOL, $this->oldContent);

        foreach ($oldMethods as $key => $val) {
            /** @var \ReflectionMethod $val */
            if (!isset($newMethods[$key])) {
                continue;
            }

            $_newMethod = $newMethods[$key];

            $between = $this->diffContent($this->getBetweenLine($oldContent, $val), $this->getBetweenLine($explode, $_newMethod));

            $start = $val->getStartLine() - 1;

            //在新内容中追加旧内容
            $oldContent = $this->replaceBetweenContent($oldContent, $start, $val->getEndLine(), $between);
        }
        $this->oldContent = $oldContent;
        return $oldContent;
    }

    /**
     * @param \ReflectionClass $class
     * @return array
     */
    private function getMethods(\ReflectionClass $class)
    {
        $_tmp = [];
        foreach ($class->getMethods() as $key => $val) {
            if (!$val->isPublic()) {
                continue;
            }
            $method = $val->getPrototype();

            if ($method->class != $class->getName()) {
                continue;
            }
            if (!preg_match('/^action\w+$/', $method->name)) {
                continue;
            }
            $_tmp[$method->getName()] = $method;
        }
        return $_tmp;
    }

    /**
     * @param $content
     * @param $className
     *
     * 写入
     */
    private function write($content, $className)
    {
        $file = $this->getPath($className);
        if (file_exists($file)) {
            unlink($file);
        }

        file_put_contents($file, $content);
        $this->fileList[] = $className . 'controller.php';
    }

    /**
     * @param $file
     * @return bool|string
     * 返回已存在文件内容
     */
    private function getOldContext($file)
    {
        return file_get_contents($this->getPath($file));
    }

    /**
     * @param $class
     * @return bool|string
     *
     * 返回文件路径
     */
    private function getPath($class)
    {
        return realpath($this->path . '/' . $class . 'controller.php');
    }
}
