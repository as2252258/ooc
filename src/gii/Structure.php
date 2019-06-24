<?php
/**
 * Created by PhpStorm.
 * User: qv
 * Date: 2018/10/10 0010
 * Time: 15:41
 */

namespace Yoc\gii;


class Structure
{

    private $fields = [];

    private $primary = '';

    private $structure = [];

    private $tableName = '';

    private $className = '';

    public function setClassName($class)
    {
        $this->className = $class;
    }

    public function getClassName()
    {
        return $this->className;
    }

    /**
     * @return array
     */
    public function getFields(): array
    {
        return $this->fields;
    }

    /**
     * @param array $fields
     */
    public function setFields(array $fields): void
    {
        $this->fields = $fields;
    }

    /**
     * @return string
     */
    public function getPrimary(): string
    {
        return $this->primary;
    }

    /**
     * @param string $primary
     */
    public function setPrimary(string $primary): void
    {
        $this->primary = $primary;
    }

    /**
     * @return array
     */
    public function getStructure(): array
    {
        return $this->structure;
    }

    /**
     * @param array $structure
     */
    public function setStructure(array $structure): void
    {
        $this->structure = $structure;
    }

    /**
     * @return string
     */
    public function getTableName(): string
    {
        return $this->tableName;
    }

    /**
     * @param string $tableName
     */
    public function setTableName(string $tableName): void
    {
        $this->tableName = $tableName;
    }
}
