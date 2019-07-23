<?php
/**
 * Created by PhpStorm.
 * User: qv
 * Date: 2018/10/10 0010
 * Time: 15:24
 */

namespace Beauty\gii;


abstract class BGii
{

    public $dataNamespace = 'data\\';
    public $dataPath = APP_PATH . '/data';

    public $rules = [];
    public $type = [
        'getInt' => ['tinyint', 'smallint', 'mediumint', 'int', 'bigint'],
        'getStr' => ['char', 'varchar', 'tinytext', 'text', 'mediumtext', 'longtext',],
        'getDate' => ['date'],
        'getTime' => ['time'],
        'getYear' => ['year'],
        'getDatetime' => ['datetime'],
        'getTimestamp' => ['timestamp'],
        'getFloat' => ['float', 'double', 'decimal',],
    ];
    protected $tableName = [];
    protected $document = NULL;
    protected $isUpdate = FALSE;
    protected $fileList = [];
    protected $keyword = ['ADD', 'ALL', 'ALTER', 'AND', 'AS', 'ASC', 'ASENSITIVE', 'BEFORE', 'BETWEEN', 'BIGINT', 'BINARY', 'BLOB', 'BOTH', 'BY', 'CALL', 'CASCADE', 'CASE', 'CHANGE', 'CHAR', 'CHARACTER', 'CHECK', 'COLLATE', 'COLUMN', 'CONDITION', 'CONNECTION', 'CONSTRAINT', 'CONTINUE', 'CONVERT', 'CREATE', 'CROSS', 'CURRENT_DATE', 'CURRENT_TIME', 'CURRENT_TIMESTAMP', 'CURRENT_USER', 'CURSOR', 'DATABASE', 'DATABASES', 'DAY_HOUR', 'DAY_MICROSECOND', 'DAY_MINUTE', 'DAY_SECOND', 'DEC', 'DECIMAL', 'DECLARE', 'DEFAULT', 'DELAYED', 'DELETE', 'DESC', 'DESCRIBE', 'DETERMINISTIC', 'DISTINCT', 'DISTINCTROW', 'DIV', 'DOUBLE', 'DROP', 'DUAL', 'EACH', 'ELSE', 'ELSEIF', 'ENCLOSED', 'ESCAPED', 'EXISTS', 'EXIT', 'EXPLAIN', 'FALSE', 'FETCH', 'FLOAT', 'FLOAT4', 'FLOAT8', 'FOR', 'FORCE', 'FOREIGN', 'FROM', 'FULLTEXT', 'GOTO', 'GRANT', 'GROUP', 'HAVING', 'HIGH_PRIORITY', 'HOUR_MICROSECOND', 'HOUR_MINUTE', 'HOUR_SECOND', 'IF', 'IGNORE', 'IN', 'INDEX', 'INFILE', 'INNER', 'INOUT', 'INSENSITIVE', 'INSERT', 'INT', 'INT1', 'INT2', 'INT3', 'INT4', 'INT8', 'INTEGER', 'INTERVAL', 'INTO', 'IS', 'ITERATE', 'JOIN', 'KEY', 'KEYS', 'KILL', 'LABEL', 'LEADING', 'LEAVE', 'LEFT', 'LIKE', 'LIMIT', 'LINEAR', 'LINES', 'LOAD', 'LOCALTIME', 'LOCALTIMESTAMP', 'LOCK', 'LONG', 'LONGBLOB', 'LONGTEXT', 'LOOP', 'LOW_PRIORITY', 'MATCH', 'MEDIUMBLOB', 'MEDIUMINT', 'MEDIUMTEXT', 'MIDDLEINT', 'MINUTE_MICROSECOND', 'MINUTE_SECOND', 'MOD', 'MODIFIES', 'NATURAL', 'NOT', 'NO_WRITE_TO_BINLOG', 'NULL', 'NUMERIC', 'ON', 'OPTIMIZE', 'OPTION', 'OPTIONALLY', 'OR', 'ORDER', 'OUT', 'OUTER', 'OUTFILE', 'PRECISION', 'PRIMARY', 'PROCEDURE', 'PURGE', 'RAID0', 'RANGE', 'READ', 'READS', 'REAL', 'REFERENCES', 'REGEXP', 'RELEASE', 'RENAME', 'REPEAT', 'REPLACE', 'REQUIRE', 'RESTRICT', 'RETURN', 'REVOKE', 'RIGHT', 'RLIKE', 'SCHEMA', 'SCHEMAS', 'SECOND_MICROSECOND', 'SELECT', 'SENSITIVE', 'SEPARATOR', 'SET', 'SHOW', 'SMALLINT', 'SPATIAL', 'SPECIFIC', 'SQL', 'SQLEXCEPTION', 'SQLSTATE', 'SQLWARNING', 'SQL_BIG_RESULT', 'SQL_CALC_FOUND_ROWS', 'SQL_SMALL_RESULT', 'SSL', 'STARTING', 'STRAIGHT_JOIN', 'TABLE', 'TERMINATED', 'THEN', 'TINYBLOB', 'TINYINT', 'TINYTEXT', 'TO', 'TRAILING', 'TRIGGER', 'TRUE', 'UNDO', 'UNION', 'UNIQUE', 'UNLOCK', 'UNSIGNED', 'UPDATE', 'USAGE', 'USE', 'USING', 'UTC_DATE', 'UTC_TIME', 'UTC_TIMESTAMP', 'VALUES', 'VARBINARY', 'VARCHAR', 'VARCHARACTER', 'VARYING', 'WHEN', 'WHERE', 'WHILE', 'WITH', 'WRITE', 'X509', 'XOR', 'YEAR_MONTH', 'ZEROFILL'];

    protected $fileds = [];

    /**
     * @param $tables
     * @return array
     * @throws \Exception
     */
    protected function initData($tables)
    {
        if (!empty($tables)) {
            if (strpos(',', $tables)) {
                $res = explode(',', $tables);
            } else {
                $res = [$tables];
            }
        }
        if (!isset($res)) {
            $res = \Db::findAllBySql('show tables');
        }

        if (empty($res)) {
            return NULL;
        }

        $this->fileds = $this->getFields($res);
        return $this->fileds;
    }

    /**
     * @param $table
     * @return bool|int
     * @throws \Exception
     */
    protected function getIndex($table)
    {
        $data = \Db::findAllBySql('SHOW INDEX FROM ' . $table);

        return empty($data) ? NULL : $data[0];
    }

    /**
     * @param $tables
     *
     * @return array
     * @throws
     */
    protected function getFields($tables)
    {
        $res = [];
        if (!is_array($tables)) {
            $tables = [$tables];
        }
        foreach ($tables as $key => $val) {
            if (empty($val)) continue;
            if (is_array($val)) $val = array_shift($val);
            $_tmp = \Db::findAllBySql('SHOW FULL FIELDS FROM ' . $val);
            if (empty($_tmp)) {
                continue;
            }
            $res[$val] = $_tmp;
            $this->tableName[] = $_tmp;
        }
        return $res;
    }

    /**
     * @param $tableName
     * @param $tables
     *
     * @return Structure
     * @throws \Exception
     */
    protected function resolveTableStructure($tableName, $tables)
    {
        $fields = [];
        $structure = new Structure();

        foreach ($tables as $_key => $_val) {
            if ($_val['Field'] == 'id' && $_val['Extra'] == 'auto_increment') {
                $keys = $tableName;
            }
            if (!isset($keys) && !($index = $this->getIndex($tableName))) {
                $keys = $index['Column_name'];
            }
            if (in_array(strtoupper($_val['Field']), $this->keyword)) {
                throw new \Exception('You can not use keyword "' . $_val['Field'] . '" as field at table "' . $tableName . '"');
            }
            array_push($fields, $_val['Field']);
        }

        if (!isset($keys)) {
            throw new \Exception('please check table ' . $tableName . ', the table do not have primary id or id is not auto_increment');
        }

        $structure->setTableName($tableName);
        $structure->setPrimary($keys);
        $structure->setClassName($this->getClassName($tableName));
        $structure->setFields($fields);
        $structure->setStructure($tables);

        return $structure;
    }

    /**
     * @param $tableName
     * @return string
     */
    protected function getClassName($tableName)
    {
        $res = [];
        foreach (explode('_', $tableName) as $n => $val) {
            $res[] = ucfirst($val);
        }

        $name = ucfirst(rtrim(\Beauty::$app->db->tablePrefix, '_'));

        return str_replace($name, '', implode('', $res)) . 'controller';
    }

    /**
     * @param \ReflectionClass $object
     * @param string $content
     * @return string
     */
    protected function getClassUpContext($object, $content)
    {
        $explode = explode(PHP_EOL, $content);
        $exists = array_slice($explode, 0, $object->getStartLine());
        $_tmp = [];
        foreach ($exists as $key => $val) {
            if (trim($val) == '/**') {
                break;
            }
            $_tmp[] = $val;
        }
        return trim(implode(PHP_EOL, $_tmp));
    }

    /**
     * @param $file
     * @param $line
     * @param $append
     * 指定行追加内容
     */
    protected function writeByLineContent(&$content, $line, $append)
    {
        $tmp = [];
        if (is_string($content)) {
            $content = explode(PHP_EOL, $content);
        }
        foreach ($content as $key => $val) {
            if ($key + 1 >= $line) {
                array_push($tmp, $append);
            } else {
                array_push($tmp, $val);
            }
        }
    }

    /**
     * @param $content
     * @param $startLine
     * @param $endLine
     * @param $newContent
     * @return array
     * 替换指定行数内容
     */
    protected function replaceBetweenContent($content, $startLine, $endLine, $newContent)
    {
        $length = $endLine - $startLine;

        return array_splice($content, $startLine, $length, [$newContent]);
    }

    /**
     * @param array $content
     * @param \ReflectionMethod $method
     * @return array
     */
    protected function getBetweenLine($content, $method)
    {

        $startLine = $method->getStartLine();
        $endLine = $method->getEndLine();

        $between = array_slice($content, $startLine - 1, $endLine - $startLine + 1);

        return $between;
    }

    /**
     * @param $oldContent
     * @param $newContent
     * @return array
     * @throws \Exception
     */
    protected function diffContent($oldContent, $newContent)
    {
        $_old = $this->clearSpace($oldContent);
        foreach ($newContent as $key => $value) {
            $value = $this->clearSpace($value);
            if (empty($value)) {
                continue;
            }
            if (in_array($value, $_old)) {
                continue;
            }
            $oldContent = array_splice($oldContent, $key, 0, [$value]);
        }
        return $oldContent;
    }

    /**
     * @param $content
     * @return array|null|string|string[]
     * @throws \Exception
     */
    private function clearSpace($content)
    {
        if (is_string($content)) {
            return preg_replace('/\s+/', '', $content);
        } else if (is_array($content)) {
            $_tmp = [];
            foreach ($content as $key => $val) {
                $_tmp[] = preg_replace('/\s+/', '', $content);
            }
            return $_tmp;
        } else {
            throw new \Exception('data must is array or string.');
        }
    }
}
