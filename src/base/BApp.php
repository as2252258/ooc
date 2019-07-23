<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/10/7 0007
 * Time: 2:13
 */

namespace Beauty\base;


use Beauty\di\Service;
use Beauty\error\ErrorHandler;
use Beauty\error\RestfulHandler;
use Beauty\event\Event;
use Beauty\exception\InitException;
use Beauty\http\Request;
use Beauty\http\Response;
use Beauty\permission\Permis;
use Beauty\route\Router;

/**
 * Class BApp
 * @package Beauty\base
 */
abstract class BApp extends Service
{

	/**
	 * @var string
	 */
	public $runtimePath = APP_PATH . '/runtime';


	public $isCli = true;

	public $isCommand = false;

	/**
	 * Init constructor.
	 *
	 * @param array $config
	 *
	 * @throws
	 */
	public function __construct(array $config = [])
	{
		\Beauty::$app = $this;

		$this->parseInt($config);

		$this->initErrorHandler($config);

		parent::__construct($config);
	}

	/**
	 * @param $config
	 *
	 * @throws
	 */
	public function parseInt(&$config)
	{
		if (isset($config['id'])) {
			$this->id = $config['id'];
			unset($config['id']);
		}
		if (isset($config['runtime'])) {
			$this->runtimePath = $config['runtime'];
			unset($config['runtime']);
		}
		if (!empty($this->runtimePath)) {
			if (!is_dir($this->runtimePath)) {
				mkdir($this->runtimePath, 777);
			}
			if (!is_dir($this->runtimePath) || !is_writeable($this->runtimePath)) {
				throw new InitException("Directory {$this->runtimePath} does not have write permission");
			}
		}
		if (isset($config['aliases'])) {
			$this->classAlias($config);
		}
		if (isset($config['components'])) {
			foreach ($this->moreComponents() as $key => $val) {
				if (isset($config['components'][$key])) {
					$config['components'][$key] = array_merge($val, $config['components'][$key]);
				} else {
					$config['components'][$key] = $val;
				}
			}
		}
	}

	/**
	 * @param array $data
	 * 类别名
	 */
	private function classAlias(array &$data)
	{
		foreach ($data['aliases'] as $key => $val) {
			class_alias($val, $key, true);
		}
		unset($data['aliases']);
	}

	/**
	 * @param $config
	 *
	 * @throws \Exception
	 */
	public function initErrorHandler(&$config)
	{
		if (isset($config['components']['error'])) {
			$this->set('error', $config['components']['error']);
			$this->get('error')->register();

			unset($config['components']['error']);
		}
	}

	/**
	 * @return Request
	 * @throws \Exception
	 */
	public function getRequest()
	{
		return $this->get('request');
	}

	/**
	 * @return mixed
	 * @throws \Exception
	 */
	public function getResponse()
	{
		return $this->get('response');
	}

	/**
	 * @return mixed
	 * @throws \Exception
	 */
	public function getRedis()
	{
		return $this->get('redis');
	}

	/**
	 * @return ErrorHandler
	 * @throws \Exception
	 */
	public function getError()
	{
		return $this->get('error');
	}

	/**
	 * @return Router
	 * @throws \Beauty\exception\ComponentException
	 */
	public function getRouter()
	{
		return $this->get('router');
	}

	/**
	 * @return array
	 */
	private function moreComponents()
	{
		return [
			'error' => ['class' => RestfulHandler::class],
			'response' => ['class' => Response::class],
			'request' => ['class' => Request::class],
			'router' => ['class' => Router::class],
			'permis' => ['class' => Permis::class],
			'event' => ['class' => Event::class],
		];
	}
}
