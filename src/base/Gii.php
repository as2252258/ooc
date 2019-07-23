<?php
/**
 * Created by PhpStorm.
 * User: 向林
 * Date: 2016/8/9 0009
 * Time: 17:43
 */

namespace Beauty\base;

use Beauty\db\Connection;
use Beauty\http\Request;

/**
 * Class gii
 *
 * @package Inter\utility
 */
class Gii
{
	public $rules = [];
	public $type = [
		'int' => ['tinyint', 'smallint', 'mediumint', 'int', 'bigint'],
		'string' => ['char', 'varchar', 'tinytext', 'text', 'mediumtext', 'longtext',],
		'date' => ['date'],
		'time' => ['time'],
		'year' => ['year'],
		'datetime' => ['datetime'],
		'timestamp' => ['timestamp'],
		'float' => ['float', 'double', 'decimal',],
	];
	private $tableName = NULL;
	private $document = NULL;
	private $isUpdate = FALSE;
	private $fileList = [];
	private $keyword = ['ADD', 'ALL', 'ALTER', 'AND', 'AS', 'ASC', 'ASENSITIVE', 'BEFORE', 'BETWEEN', 'BIGINT', 'BINARY', 'BLOB', 'BOTH', 'BY', 'CALL', 'CASCADE', 'CASE', 'CHANGE', 'CHAR', 'CHARACTER', 'CHECK', 'COLLATE', 'COLUMN', 'CONDITION', 'CONNECTION', 'CONSTRAINT', 'CONTINUE', 'CONVERT', 'CREATE', 'CROSS', 'CURRENT_DATE', 'CURRENT_TIME', 'CURRENT_TIMESTAMP', 'CURRENT_USER', 'CURSOR', 'DATABASE', 'DATABASES', 'DAY_HOUR', 'DAY_MICROSECOND', 'DAY_MINUTE', 'DAY_SECOND', 'DEC', 'DECIMAL', 'DECLARE', 'DEFAULT', 'DELAYED', 'DELETE', 'DESC', 'DESCRIBE', 'DETERMINISTIC', 'DISTINCT', 'DISTINCTROW', 'DIV', 'DOUBLE', 'DROP', 'DUAL', 'EACH', 'ELSE', 'ELSEIF', 'ENCLOSED', 'ESCAPED', 'EXISTS', 'EXIT', 'EXPLAIN', 'FALSE', 'FETCH', 'FLOAT', 'FLOAT4', 'FLOAT8', 'FOR', 'FORCE', 'FOREIGN', 'FROM', 'FULLTEXT', 'GOTO', 'GRANT', 'GROUP', 'HAVING', 'HIGH_PRIORITY', 'HOUR_MICROSECOND', 'HOUR_MINUTE', 'HOUR_SECOND', 'IF', 'IGNORE', 'IN', 'INDEX', 'INFILE', 'INNER', 'INOUT', 'INSENSITIVE', 'INSERT', 'INT', 'INT1', 'INT2', 'INT3', 'INT4', 'INT8', 'INTEGER', 'INTERVAL', 'INTO', 'IS', 'ITERATE', 'JOIN', 'KEY', 'KEYS', 'KILL', 'LABEL', 'LEADING', 'LEAVE', 'LEFT', 'LIKE', 'LIMIT', 'LINEAR', 'LINES', 'LOAD', 'LOCALTIME', 'LOCALTIMESTAMP', 'LOCK', 'LONG', 'LONGBLOB', 'LONGTEXT', 'LOOP', 'LOW_PRIORITY', 'MATCH', 'MEDIUMBLOB', 'MEDIUMINT', 'MEDIUMTEXT', 'MIDDLEINT', 'MINUTE_MICROSECOND', 'MINUTE_SECOND', 'MOD', 'MODIFIES', 'NATURAL', 'NOT', 'NO_WRITE_TO_BINLOG', 'NULL', 'NUMERIC', 'ON', 'OPTIMIZE', 'OPTION', 'OPTIONALLY', 'OR', 'ORDER', 'OUT', 'OUTER', 'OUTFILE', 'PRECISION', 'PRIMARY', 'PROCEDURE', 'PURGE', 'RAID0', 'RANGE', 'READ', 'READS', 'REAL', 'REFERENCES', 'REGEXP', 'RELEASE', 'RENAME', 'REPEAT', 'REPLACE', 'REQUIRE', 'RESTRICT', 'RETURN', 'REVOKE', 'RIGHT', 'RLIKE', 'SCHEMA', 'SCHEMAS', 'SECOND_MICROSECOND', 'SELECT', 'SENSITIVE', 'SEPARATOR', 'SET', 'SHOW', 'SMALLINT', 'SPATIAL', 'SPECIFIC', 'SQL', 'SQLEXCEPTION', 'SQLSTATE', 'SQLWARNING', 'SQL_BIG_RESULT', 'SQL_CALC_FOUND_ROWS', 'SQL_SMALL_RESULT', 'SSL', 'STARTING', 'STRAIGHT_JOIN', 'TABLE', 'TERMINATED', 'THEN', 'TINYBLOB', 'TINYINT', 'TINYTEXT', 'TO', 'TRAILING', 'TRIGGER', 'TRUE', 'UNDO', 'UNION', 'UNIQUE', 'UNLOCK', 'UNSIGNED', 'UPDATE', 'USAGE', 'USE', 'USING', 'UTC_DATE', 'UTC_TIME', 'UTC_TIMESTAMP', 'VALUES', 'VARBINARY', 'VARCHAR', 'VARCHARACTER', 'VARYING', 'WHEN', 'WHERE', 'WHILE', 'WITH', 'WRITE', 'X509', 'XOR', 'YEAR_MONTH', 'ZEROFILL'];

	/** @var Connection */
	private $db;

	/**
	 * @param \Beauty\http\Request $request
	 *
	 * @return array
	 * @throws \Exception
	 */
	public static function run(Request $request, Connection $db = NULL)
	{
		$gii = new Gii();
		if (!empty($db)) $gii->db = $db;
		if (!$gii->db) {
			$gii->db = \Beauty::$app->db;
		}
		$redis = \Beauty::$app->redis;
		if (!empty(Input()->get('t'))) {
			$gii->tableName = Input()->get('t');
			$redis->del('column:' . $gii->tableName);
		}
		if (Input()->get('m', NULL)) {
			$model = 1;
		}
		if (Input()->get('c', NULL)) {
			$c = 1;
		}
		if (Input()->get('isUpdate') == 1) {
			$gii->isUpdate = TRUE;
		}
		$gii->getTable($c, $model);
		return $gii->fileList;
	}

	/**
	 * @param $m
	 * @param $c
	 *
	 * @throws \Exception
	 */
	private function getTable(&$c, &$m)
	{
		if (!empty($this->tableName)) {
			if (strpos(',', $this->tableName)) {
				$res = explode(',', $this->tableName);
			} else {
				$res = [$this->tableName];
			}
		} else {
			$_tables = \Db::findAllBySql('show tables', [], $this->db);

			if (!empty($_tables)) {
				$res = [];
				foreach ($_tables as $key => $val) {
					$res[] = array_shift($val);
				}
			}
		}
		$tables = $this->getFields($res);
		if (!empty($tables)) {
			foreach ($tables as $key => $val) {
				$data = $this->createModelFile($key, $val);
				if ($m == 1 && $c == 1) {
					$this->createCFile($data['classFileName'], $data['fields']);
					$this->createMFile($data['classFileName'], $data['tableName'], $data['visible'], $data['res'], $data['fields']);
				} else if ($m == 1) {
					$this->createMFile($data['classFileName'], $data['tableName'], $data['visible'], $data['res'], $data['fields']);
				} else {
					$this->createCFile($data['classFileName'], $data['fields']);
				}
			}
		}
	}

	/**
	 * @param $table
	 * @return bool|int
	 * @throws \Exception
	 */
	private function getIndex($table)
	{
		$data = \Db::findAllBySql('SHOW INDEX FROM ' . $table, [], $this->db);

		return empty($data) ? NULL : $data[0];
	}

	/**
	 * @param $tables
	 *
	 * @return array
	 * @throws
	 */
	private function getFields($tables)
	{
		$res = [];
		if (!is_array($tables)) {
			$tables = [$tables];
		}
		foreach ($tables as $key => $val) {
			if (empty($val)) continue;
			$_tmp = \Db::findAllBySql('SHOW FULL FIELDS FROM ' . $val, [], $this->db);
			if (!empty($_tmp)) {
				$res[$val] = $_tmp;
			}
		}
		return $res;
	}

	/**
	 * @param $tableName
	 * @param $tables
	 *
	 * @return array
	 * @throws \Exception
	 */
	public function createModelFile($tableName, $tables)
	{

		$res = $visible = $fields = $keys = [];
		$_fields = array_column($tables, 'Field');
//        foreach (['id'] as $key => $val) {
//            if (!in_array($val, $_fields)) {
//                throw new \exception('必填字段' . $val . '不存在');
//            }
//        }

		foreach ($tables as $_key => $_val) {
			$keys = $tableName;
			if ($_val['Extra'] == 'auto_increment' || $_val['Key'] == 'PRI') {
				$keys = $tableName;
			}
			if (!isset($keys) && !($index = $this->getIndex($tableName))) {
				$keys = $index['Column_name'];
			}
			if (in_array(strtoupper($_val['Field']), $this->keyword)) {
				throw new \Exception('You can not use keyword "' . $_val['Field'] . '" as field at table "' . $tableName . '"');
			}
			array_push($visible, $this->createVisible($_val['Field']));
			array_push($fields, $_val);
			$res[] = $this->createSetFunc($_val['Field'], $_val['Comment']);
		}
//
//        if (empty($keys)) {
//            throw new \exception('please check table ' . $tableName . ', the table do not have primary id or id is not auto_increment');
//        }

		$classFileName = $this->getClassName($tableName);

		return [
			'classFileName' => $classFileName,
			'tableName' => $keys,
			'visible' => $visible,
			'fields' => $fields,
			'res' => $res,
		];
	}

	private function createVisible($field)
	{
		return '
 * @property $' . $field;
	}

	private function createSetFunc($field, $comment)
	{
		return '
            ' . str_pad('\'' . $field . '\'', 20, ' ', STR_PAD_RIGHT) . '=> \'' . (empty($comment) ? ucfirst($field) : $comment) . '\',';
	}

	private function getClassName($tableName)
	{
		$res = [];
		foreach (explode('_', $tableName) as $n => $val) {
			$res[] = ucfirst($val);
		}

		$name = ucfirst(rtrim($this->db->tablePrefix, '_'));

		return str_replace($name, '', implode('', $res)) . 'Comply';
	}

//	private function rename(){
//
//	}

	/**
	 * @param $className
	 * @param $fields
	 * @throws \Exception
	 */
	private function createCFile($className, $fields)
	{
		$path = $this->getControllerPath();
		$modelPath = $this->getModelPath();


		$managerName = str_replace('Comply', '', $className);
//		$managerName = str_replace($name, '', $_className);

		$namespace = ltrim($path['namespace'], '\\');
		$model_namespace = ltrim($modelPath['namespace'], '\\');

		$class = '';
		$controller = $namespace . '\\' . $managerName . 'Controller';
		if (file_exists($path['path'] . '/' . $managerName . 'Controller.php')) {
			try {
				$class = new \ReflectionClass($controller);
			} catch (\Exception $e) {
				var_dump($e->getMessage());
			}
		}

		$routeFile = current(glob(APP_PATH . '/routes/*'));

		foreach (explode('/', $managerName) as $val) {
			if (!$val) {
				continue;
			}
		};

//		$naem = $this->getModule() . '\\\\' . $managerName . 'controller';
//		$routeHtml = '
//$router->group([\'prefix\' => \'' . lcfirst($managerName) . '\'], function(Router $router){
//	$router->post(\'add\', \'' . $naem . '@actionAdd\');
//	$router->post(\'update\', \'' . $naem . '@actionUpdate\');
//	$router->post(\'delete\', \'' . $naem . '@actionDelete\');
//	$router->get(\'detail\', \'' . $naem . '@actionDetail\');
//	$router->get(\'list\', \'' . $naem . '@actionList\');
//});';
//		file_put_contents($routeFile, $routeHtml, FILE_APPEND);

		$html = $this->getUseContent($class, $controller);
		if (empty($html)) {


			$html .= "<?php
namespace {$namespace};

use Beauty;
use Code;
use exception;
use Beauty\core\Str;
use Beauty\core\JSON;
use Beauty\http\Request;
use Beauty\http\Response;
use components\Authorize;
use components\ActiveController;
use {$model_namespace}\\{$managerName};
";
		}

		$html .= "
		
/**
 * Class {$managerName}Controller
 *
 * @package controller
 */
class {$managerName}Controller extends ActiveController
{

	
";
		$funcNames = [];
		$default = ['actionAdd', 'actionUpdate', 'actionDetail', 'actionDelete', 'actionList'];
		if (is_object($class)) {
			$methods = $class->getMethods(\ReflectionMethod::IS_PUBLIC);
			$funcNames = array_column($methods, 'name');
			if (!empty($methods)) foreach ($methods as $key => $val) {
				if ($val->class != $class->getName()) continue;
				$html .= "
	" . $val->getDocComment() . "\n";
				$content = $this->getFuncLineContent($class, $controller, $val->name) . "\n";
				if (in_array($val->name, $default)) {
					$newContent = $this->{'controller' . str_replace('action', 'Method', $val->name)}($fields, $managerName, $managerName);
//					print_r(array_diff_assoc(explode(PHP_EOL,$content),explode(PHP_EOL,$newContent)));
				}
				$html .= $this->getFuncLineContent($class, $controller, $val->name) . "\n";
			}
		}


		foreach ($default as $key => $val) {
			if (in_array($val, $funcNames)) continue;
			$html .= $this->{'controllerMethod' . str_replace('action', '', $val)}($fields, $managerName, $managerName) . "\n";
		}
		$html .= '
}';

		$file = $path['path'] . '/' . $managerName . 'Controller.php';
		if (file_exists($file)) {
			unlink($file);
		}

		file_put_contents($file, $html);
		$this->fileList[] = $managerName . 'Controller.php';
	}

	/**
	 * @param \ReflectionClass $object
	 * @param                  $className
	 *
	 * @return string
	 */
	public function getUseContent($object, $className)
	{
		$file = $this->getFilePath($className);
		if (!file_exists($file)) {
			return '';
		}
		$content = file_get_contents($file);
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
	 * @param $table
	 * @return string
	 * @throws \Exception
	 */
	private function setCreateSql($table)
	{
		$text = \Db::showCreateSql($table, $this->db)['Create Table'] ?? '';

		$_tmp = [];
		foreach (explode(PHP_EOL, $text) as $val) {
			$_tmp[] = '// ' . $val;
		}

		return implode(PHP_EOL, $_tmp);
	}


	private function getFilePath($className)
	{
		if (strpos($className, '\\')) {
			$className = str_replace('\\', '/', $className);
		}
		if (strpos($className, '\\')) {
			$className = str_replace('\\', '/', $className);
		}

		return APP_PATH . '/' . $className . '.php';
	}

	/**
	 * @return array
	 */
	private function getModelPath()
	{
		$dbName = $this->db->id;
		if (empty($dbName) || $dbName == 'default') {
			$dbName = 'home';
		}
		$modelPath = [
			'namespace' => 'app\\model\\' . $dbName,
			'path' => APP_PATH . '/app/model/' . $dbName,
		];

		if (!is_dir($modelPath['path'])) {
			mkdir($modelPath['path']);
		}
		return $modelPath;
	}

	/**
	 * @return array
	 */
	private function getControllerPath()
	{
		$dbName = $this->db->id;
		if (empty($dbName) || $dbName == 'default') {
			$dbName = 'home';
		}
		$modelPath = [
			'namespace' => 'app\\controller\\' . $dbName,
			'path' => APP_PATH . '/app/controller/' . $dbName,
		];

		if (!is_dir($modelPath['path'])) {
			mkdir($modelPath['path']);
		}
		return $modelPath;
	}

	private function getModule()
	{
		$dbName = $this->db->id;
		if (empty($dbName) || $dbName == 'default') {
			$dbName = 'home';
		}
		return ucfirst($dbName);
	}

	/**
	 * @param \ReflectionClass $object
	 * @param                  $className
	 * @param                  $method
	 * @return string
	 * @throws \Exception
	 */
	public function getFuncLineContent($object, $className, $method)
	{
		$fun = $object->getMethod($method);

		$content = file_get_contents($this->getFilePath($className));
		$explode = explode(PHP_EOL, $content);
		$exists = array_slice($explode, $fun->getStartLine() - 1, $fun->getEndLine() - $fun->getStartLine() + 1);
		return implode(PHP_EOL, $exists);
	}

	/**
	 * @param $classFileName
	 * @param $tableName
	 * @param $visible
	 * @param $res
	 * @param $fields
	 * @throws \Exception
	 */
	private function createMFile($classFileName, $tableName, $visible, $res, $fields)
	{

		$class = '';
		$modelPath = $this->getModelPath();

//		$managerName = str_replace('Xl', '', $classFileName);
		$managerName = str_replace('Comply', '', $classFileName);
		$namespace = ltrim($modelPath['namespace'], '\\');
		$classFileName = ltrim($modelPath['namespace'], '\\') . '\\' . $managerName;
		if (file_exists($modelPath['path'] . '/' . $managerName . '.php')) {
			try {
				$class = new \ReflectionClass($modelPath['namespace'] . '\\' . $managerName);
			} catch (\Exception $e) {
				var_dump($e->getMessage());
			}
		}

		$html = $this->getUseContent($class, $classFileName);
		if (empty($html)) {
			$html = '<?php
namespace ' . $namespace . ';

use Beauty\db\ActiveRecord;';
		}
		$html .= '
' . $this->setCreateSql($tableName) . '

/**
 * Class ' . $managerName . '
 * @package Inter\mysql
 *' . implode('', $visible) . '
 * @sql
 */
class ' . $managerName . ' extends ActiveRecord
{';

		if (!empty($class)) {
			foreach ($class->getConstants() as $key => $val) {
				if (is_numeric($val)) {
					$html .= '
    const ' . $key . ' = ' . $val . ';' . "\n";
				} else {
					$html .= '
    const ' . $key . ' = \'' . $val . '\';' . "\n";
				}
			}

			foreach ($class->getDefaultProperties() as $key => $val) {
				$property = $class->getProperty($key);
				if ($property->class != $class->getName()) continue;
				if (is_array($val)) {
					$val = '[\'' . implode('\', \'', $val) . '\']';
				} else if (!is_numeric($val)) {
					$val = '\'' . $val . '\'';
				}

				if ($property->isProtected()) {
					$debug = 'protected';
				} else if ($property->isPrivate()) {
					$debug = 'private';
				} else {
					$debug = 'public';
				}


				if ($property->isStatic()) {
					$html .= '
    ' . $debug . ' static $' . $key . ' = ' . $val . ';' . "\n";
				} else {
					$html .= '
    ' . $debug . ' $' . $key . ' = ' . $val . ';' . "\n";
				}

			}
		} else {
			$primary = $this->createPrimary($fields);
			if (!empty($primary)) {
				$html .= $primary . "\n";
			}
		}

		$html .= $this->createTableName($tableName) . "\n";

		$html .= $this->createRules($fields);


		$html .= '        
        
    /**
     * @inheritdoc
     */
    public function attributes() : array
    {
        return [' . implode('', $res) . '
        ];
    }' . "\n";


		$out = ['rules', 'tableName', 'attributes'];
		if (is_object($class)) {
			$methods = $class->getMethods(\ReflectionMethod::IS_PUBLIC);
			if (!empty($methods)) foreach ($methods as $key => $val) {
				if ($val->class != $class->getName()) continue;
				if (in_array($val->name, $out)) continue;
//				var_dump($val);
				$html .= "
	" . $val->getDocComment() . "\n";
				$html .= $this->getFuncLineContent($class, $classFileName, $val->name) . "\n";
			}
		} else {
			$html .= $this->createDatabaseSource();
		}
		$html .= '
}';

		$file = rtrim($modelPath['path'], '/') . '/' . $managerName . '.php';
		if (file_exists($file)) {
			unlink($file);
		}

		file_put_contents($file, $html);
		$this->fileList[] = $managerName . '.php';
	}

	private function createDatabaseSource()
	{
		return '
    /**
	 * @return mixed|\Beauty\db\Connection
	 * @throws \Exception
	 */
    public static function getDb()
    {
	    return static::setDatabaseConnect(\'' . $this->db->id . '\');
    }
';
	}

	/**
	 * 用来生成文档的
	 * 格式
	 * array(
	 *      'field' ,'字段類型' ,'是否必填' ,'字段长度' , '字段解释',
	 *      'field' ,'字段類型' ,'是否必填' ,'字段长度' , '字段解释',
	 *      'field' ,'字段類型' ,'是否必填' ,'字段长度' , '字段解释',
	 *      'field' ,'字段類型' ,'是否必填' ,'字段长度' , '字段解释',
	 *      'field' ,'字段類型' ,'是否必填' ,'字段长度' , '字段解释',
	 *      'field' ,'字段類型' ,'是否必填' ,'字段长度' , '字段解释',
	 * )
	 */
	private function createPrimary($fields)
	{
		foreach ($fields as $key => $val) {
			if ($val['Extra'] == 'auto_increment' || $val['Key'] == 'PRI') {
				return '
	protected static $primary = \'' . $val['Field'] . '\';';
			}
		}
		return '';
	}

	private function createTableName($field)
	{

		$prefixed = $this->db->tablePrefix;
		if (!empty($prefixed)) {
			$field = str_replace($prefixed, '', $field);
			$field = '{{%' . $field . '}}';
		}

		return '
    /**
     * @inheritdoc
     */
    public static function tableName(){
        return \'' . $field . '\';
    }
    ';
	}

	private function createRules($fields)
	{
		$data = [];
		foreach ($fields as $key => $val) {
			if ($val['Extra'] == 'auto_increment') continue;
			$type = preg_replace('/\(.*?\)|\s+\w+/', '', $val['Type']);
			foreach ($this->type as $_key => $_val) {
				if (in_array($type, $_val)) {
					$type = lcfirst(str_replace('get', '', $_key));
					break;
				}
			}
			$data[$type][] = $val;
		}

		$_field_one = '';
		$required = $this->getRequired($fields);
		if (!empty($required)) {
			$_field_one .= $required;
		}
		foreach ($data as $key => $val) {
			$field = '[\'' . implode('\', \'', array_column($val, 'Field')) . '\']';
			if (count($val) == 1) {
				$field = '\'' . current($val)['Field'] . '\'';
			}
			$_field_one .= '
			[' . $field . ', \'' . $key . '\'],';
		}
		foreach ($data as $key => $val) {
			$length = $this->getLength($val);
			if (!empty($length)) {
				$_field_one .= $length . ',';
			}
		}
		$required = $this->getUnique($fields);
		if (!empty($required)) {
			$_field_one .= $required;
		}
		return '
	/**
	 * @return array
	 */
    public function rules(){
        return [' . $_field_one . '
        ];
    }
        ';
	}

	public function getRequired($val)
	{
		$data = [];
		foreach ($val as $_key => $_val) {
			if ($_val['Extra'] == 'auto_increment') continue;
			if ($_val['Key'] == 'PRI' || $this->checkIsRequired($_val) === 'true') {
				array_push($data, $_val['Field']);
			}
		}
		if (empty($data)) {
			return '';
		}
		return '
			[[\'' . implode('\', \'', $data) . '\'], \'required\'],';
	}

	private function checkIsRequired($val)
	{
		return strtolower($val['Null']) == 'no' && $val['Default'] === NULL ? 'true' : 'false';
	}

	public function getLength($val)
	{
		$data = [];
		foreach ($val as $key => $_val) {
			$preg = preg_match('/\((.*?)\)/', $_val['Type'], $results);
			if ($preg && isset($results[1])) {
				$data[$results[1]][] = $_val['Field'];
			}
		}
		if (empty($data)) return '';
		$string = [];
		foreach ($data as $key => $_val) {
			if (count($_val) == 1) {
				$_tmp = '
			[\'' . current($_val) . '\', \'maxLength\' => ' . $key . ']';
			} else {
				$_tmp = '
			[[\'' . implode('\', \'', $_val) . '\'], \'maxLength\' => ' . $key . ']';
			}
			$string[] = $_tmp;
		}
		return implode(',', $string);
	}

	public function getUnique($fields)
	{
		$data = [];
		foreach ($fields as $_key => $_val) {
			if ($_val['Extra'] == 'auto_increment') continue;
			if (strpos($_val['Type'], 'unique') !== FALSE) {
				$data[] = $_val['Field'];
			}
		}
		if (empty($data)) {
			return '';
		}
		return '
			[[\'' . implode('\', \'', $data) . '\'], \'unique\'],';
	}

	public function setDocuemnt($document)
	{
		$this->document = $document;
		if (empty($this->document)) {
			return $_SERVER['DOCUMENT_ROOT'] . '/model';
		}
		return $this->document;
	}

	public function controllerMethodAdd($fields, $className, $object = NULL)
	{
		return '
    /**
	 * @return array
	 * @throws exception
	 */
	public function actionAdd(){
		$model = new ' . $className . '();
		$model->attributes = [' . $this->getData($className, $fields) . '
		];
		if (!$model->save()) {
			return JSON::to(500, $model->getLastError());
		}
		return JSON::to(Code::SUCCESS, $model->toArray());
	}';
	}

	private function getData($object, $fields, $request = 'post')
	{
		$html = '';

		$length = $this->getMaxLength($fields);

		foreach ($fields as $key => $val) {
			preg_match('/\d+/', $val['Type'], $number);
			$type = strtolower(preg_replace('/\(\d+\)/', '', $val['Type']));
			$first = preg_replace('/\s+\w+/', '', $type);
			if ($val['Field'] == 'id') continue;
			if ($type == 'timestamp') continue;
			$_field = [];
			$_field['required'] = $this->checkIsRequired($val);
			foreach ($this->type as $_key => $value) {
				if (!in_array(strtolower($first), $value)) continue;
				$comment = '//' . $val['Comment'];
				$_field['type'] = $_key;
				if ($type == 'date' || $type == 'datetime' || $type == 'time') {
					switch ($type) {
						case 'date':
							$_tps = 'Input()->' . $_key . '(\'' . $val['Field'] . '\', date(\'Y-m-d\'))';
							break;
						case 'time':
							$_tps = 'Input()->' . $_key . '(\'' . $val['Field'] . '\', date(\'H:i:s\'))';
							break;
						default:
							$_tps = 'Input()->' . $_key . '(\'' . $val['Field'] . '\', date(\'Y-m-d H:i:s\'))';
					}
					$html .= '
            \'' . str_pad($val['Field'] . '\'', $length, ' ', STR_PAD_RIGHT) . ' => ' . str_pad($_tps . ',', 60, ' ', STR_PAD_RIGHT) . $comment;
				} else {
					$tmp = 'null';
					if (isset($number[0])) {
						if (strpos(',', $number[0])) {
							$tp = explode(',', $number[0]);
							$tmp = '[' . $tp[0] . ',' . $tp[1] . ']';
							$_field['min'] = $tp[0];
							$_field['max'] = $tp[1];
						} else {
							$tmp = '[0,' . $number[0] . ']';
							$_field['min'] = 0;
							$_field['max'] = $number[0];
						}
					}
					if ($key == 'string') {
						$_tps = 'Input()->' . $_key . '(\'' . $val['Field'] . '\', ' . $_field['required'] . ', ' . $tmp . ')';
					} else if ($type == 'int') {
						if ($number[0] == 10) {
							$_tps = 'Input()->' . $_key . '(\'' . $val['Field'] . '\', time())';
						} else {
							$_tps = 'Input()->' . $_key . '(\'' . $val['Field'] . '\', ' . $_field['required'] . ', ' . ($_field[0] ?? 'null') . ', ' . ($_field[1] ?? 'null') . ')';
						}
					} else if ($key == 'email') {
						$_tps = 'Input()->' . $_key . '(\'' . $val['Field'] . '\', ' . $_field['required'] . ')';
					} else if ($key == 'timestamp') {
						$_tps = 'Input()->' . $_key . '(\'' . $val['Field'] . '\', time())';
					} else {
						$_tps = 'Input()->' . $_key . '(\'' . $val['Field'] . '\', ' . $_field['required'] . ')';
					}
					$html .= '
            \'' . str_pad($val['Field'] . '\'', $length, ' ', STR_PAD_RIGHT) . ' => ' . str_pad($_tps . ',', 60, ' ', STR_PAD_RIGHT) . $comment;
				}
			}
			$this->rules[$val['Field']] = $_field;
		}
		return $html;
	}

	private function getMaxLength($fields)
	{
		$length = 0;
		foreach ($fields as $key => $val) {
			if (mb_strlen($val['Field'] . ' >=') > $length) $length = mb_strlen($val['Field'] . ' >=');
		}
		return $length;
	}

	public function controllerMethodUpdate($fields, $className, $object = NULL)
	{
		return '
    /**
	 * @return array
	 * @throws exception
	 */
	public function actionUpdate(){
		$model = ' . $className . '::findOne(Input()->post(\'id\', 0));
		if (empty($model)) {
			return JSON::to(500, \'指定数据不存在\');
		}
		$model->attributes = [' . $this->getData($className, $fields) . '
		];
		if (!$model->save()) {
			return JSON::to(500, $model->getLastError());
		}
		return JSON::to(Code::SUCCESS, $model->toArray());
	}';
	}

	public function controllerMethodDetail($fields, $className, $managerName)
	{
		return '
    /**
	 * @return array
	 * @throws exception
	 */
    public function actionDetail(){
        $model = ' . $managerName . '::findOne(Input()->get(\'id\'));
        if(empty($model)){
            return JSON::to(404, \'Data Not Exists\');
        }
        return JSON::to(Code::SUCCESS, $model->toArray());
    }';
	}

	public function controllerMethodDelete($fields, $className, $managerName)
	{
		return '
    /**
	 * @return array
	 * @throws exception
	 */
    public function actionDelete(){
		$_key = Input()->int(\'id\', true);
		$pass = Input()->string(\'password\', true, 32);		
		
		$user = Authorize::getAuthorizationInfo();
		if (strcmp(Str::encrypt($pass), $user->password)) {
			return JSON::to(500, \'密码错误\');
		}
		
		$model = ' . $managerName . '::findOne($_key);
		if (empty($model)) {
			return JSON::to(500, \'指定数据不存在\');
		}
        if(!$model->delete()){
			return JSON::to(500, $model->getLastError());
        }
        return JSON::to(Code::SUCCESS, $model->toArray());
    }';
	}

	public function controllerMethodList($fields, $className, $managerName, $object = NULL)
	{
		return '
    /**
	 * @return array
	 * @throws exception
	 */
    public function actionList()
    {
        $pWhere = array();' . $this->getWhere($fields, $object) . '
        
        //分页处理
	    $count   = Input()->get(\'count\', -1);
	    $order   = Input()->get(\'order\', \'id\');
	    if(!empty($order)) {
	        $order .= !Input()->get(\'isDesc\', 0) ? \' asc\' : \' desc\';
	    }else{
	        $order = \'id desc\';
	    }
	    
	    //列表输出
	    $model = ' . $managerName . '::find()->where($pWhere)->orderBy($order);
	    if($count != -100){
		    $model->limit(Input()->offset() ,Input()->size());
	    }
        if((int) $count === 1){
		    $count = $model->count();
	    }
	    
		$data = $model->all()->toArray();
		
        return JSON::to(Code::SUCCESS, $data, $count);
    }
    ';
	}

	private function getWhere($fields, $object)
	{
		$html = '';

		$length = $this->getMaxLength($fields);

		foreach ($fields as $key => $val) {
			preg_match('/\d+/', $val['Type'], $number);

			$type = strtolower(preg_replace('/\(\d+\)/', '', $val['Type']));

			$first = preg_replace('/\s+\w+/', '', $type);

			if ($val['Field'] == 'id') continue;
			if ($type == 'timestamp') continue;

			foreach ($this->type as $_key => $value) {
				if (!in_array(strtolower($first), $value)) continue;
				$comment = '//' . $val['Comment'];
				if ($type == 'date' || $type == 'datetime' || $type == 'time') {
					$_tps = 'Input()->get(\'' . $val['Field'] . '\', null)';
					$html .= '
        $pWhere[\'' . str_pad($val['Field'] . ' <=\']', $length, ' ', STR_PAD_RIGHT) . ' = ' . str_pad($_tps . ';', 60, ' ', STR_PAD_RIGHT) . $comment;
					$html .= '
        $pWhere[\'' . str_pad($val['Field'] . ' >=\']', $length, ' ', STR_PAD_RIGHT) . ' = ' . str_pad($_tps . ';', 60, ' ', STR_PAD_RIGHT) . $comment;
				} else {

					$_tps = 'Input()->get(\'' . $val['Field'] . '\', null)';
					$html .= '
        $pWhere[\'' . str_pad($val['Field'] . '\']', $length, ' ', STR_PAD_RIGHT) . ' = ' . str_pad($_tps . ';', 60, ' ', STR_PAD_RIGHT) . $comment;
				}
			}
		}
		return $html;
	}

	public function getFieldLength($fieldType)
	{
		preg_match('/\d+/', $fieldType, $number);
		$tmp = 'null';
		if (isset($number[0])) {
			if (strpos(',', $number[0])) {
				$tp = explode(',', $number[0]);
				$tmp = '[' . $tp[0] . ',' . $tp[1] . ']';
			} else {
				$tmp = '[0,' . $number[0] . ']';
			}
		}
		return $tmp;
	}

	public function getUnsigned($val)
	{
		$data = [];
		foreach ($val as $_key => $_val) {
			if ($_val['Key'] == 'PRI' || $this->checkIsRequired($_val)) {
				$data[] = $_val['Field'];
			}
		}
		if (empty($data)) {
			return '';
		}
		return '
			[\'' . implode('\', \'', $data) . '\', \'required\'],';
	}
}
