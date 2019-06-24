<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/10/7 0007
 * Time: 2:16
 */

namespace Yoc\console;


use Yoc\base\BApp;
use Yoc\core\Str;
use Yoc\web\Action;

class Application extends BApp
{

	/**
	 * @var string
	 */
	public $id = 'uniqueId';

	/**
	 * @throws
	 */
	public function run()
	{
		global $argv;

		/** @var UrlManager $urlManager */
		$urlManager = $this->get('urlManager');
		try {
			if ($argv[1] == 'list') {
				$this->getCommandList();
				return;
			}

			/** @var Action $action */
			$action = $urlManager->requestHandler($argv);
			$action->runWithParam();
		} catch (\Exception $exception) {
			echo 'error: ' . $exception->getMessage() . PHP_EOL;
		}
	}


	/**
	 * @throws \ReflectionException
	 */
	private function getCommandList()
	{
		$dir = APP_PATH . '/commands';

		$controllers = [];
		foreach (glob($dir . '/*') as $value) {
			$explode = explode('/', $value);

			$_contro = str_replace('.php', '', $explode[count($explode) - 1]);

			array_push($controllers, $_contro);
		}

		$methods = [];
		foreach ($controllers as $val) {
			$this->reflect('commands\\' . $val, $methods);
		}

		echo str_pad('Commands', 20) . '注释' . PHP_EOL;
		foreach ($methods as $key => $val) {
			list($method, $ts) = $val;

//			echo "\033[32;40;1;1m " . $method . " \033[0m" . str_pad(Str::cut_str_utf8($ts, 1000), 20, ' ', STR_PAD_LEFT);
			echo str_pad("\033[32;40;1;1m " . $method . " \033[0m", 50, ' ') . $ts;
			echo PHP_EOL;
		}

		return $methods;
	}

	/**
	 * @param $class
	 * @return array
	 * @throws \ReflectionException
	 */
	private function reflect($class, &$methods)
	{
		$class = new \ReflectionClass($class);

		$actions = $class->getMethods(\ReflectionMethod::IS_PUBLIC);

		foreach ($actions as $key => $val) {
			if (!preg_match('/^action\w{1,}/', $val->getName())) {
				continue;
			}

			if ($val->getName() === 'actions') {
				continue;
			}

			$zs = $val->getDocComment();

			preg_match('/@Alias\((.*)?\)/', $zs, $data);

			$controller = str_replace('commands\\', '', $class->getName());
			$controller = str_replace('controller', '', $controller);

			$method = lcfirst($controller) . '/' . lcfirst(str_replace('action', '', $val->getName()));

			$ts = ($data[1] ?? $method);

			$methods[] = [$method, $ts];
		}

		return $methods;
	}

}
