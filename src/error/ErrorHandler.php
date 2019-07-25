<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/4/26 0026
 * Time: 10:00
 */

namespace Beauty\error;

use Beauty\base\Component;
use Beauty\http\formatter\IFormatter;

/**
 * Class ErrorHandler
 *
 * @package Beauty\base
 * @property-read $asError
 */
abstract class ErrorHandler extends Component implements ErrorInterface
{

	/** @var IFormatter $message */
	private $message = NULL;

	/**
	 * 错误处理注册
	 */
	public function register()
	{
		ini_set('display_errors', DISPLAY_ERRORS);
		set_exception_handler([$this, 'exceptionHandler']);
		if (defined('HHVM_VERSION')) {
			set_error_handler([$this, 'errorHandler']);
		} else {
			set_error_handler([$this, 'errorHandler']);
		}
		register_shutdown_function([$this, 'shutdown']);
	}

	/**
	 * @throws \Exception
	 */
	public function shutdown()
	{
		if (!($error = error_get_last())) {
			return;
		}
		$this->sendError($error['message'], $error['file'], $error['line'], 'shutdown');
	}

	/**
	 * @param \Exception $exception
	 *
	 * @throws \Exception
	 */
	public function exceptionHandler($exception)
	{
		$this->sendError($exception->getMessage(), $exception->getFile(), $exception->getLine(), 'exception');
	}

	/**
	 * @throws \Exception
	 */
	public function errorHandler()
	{
		$error = func_get_args();
		if (strpos($error[2], 'vendor/Reboot.php') !== FALSE) {
			return;
		}
		$this->sendError($error[1], $error[2], $error[3], 'error');
	}

	/**
	 * @param $messasge
	 * @param $file
	 * @param $line
	 * @param string $category
	 * @return false|string
	 * @throws \Exception
	 */
	public function sendError($messasge, $file, $line, $category = 'app')
	{
		$this->message = $messasge;
		$send = json_encode([
			'code' => 500,
			'msg' => $messasge,
			'pos' => [
				'file' => $file,
				'line' => $line
			]
		]);
		if (\Beauty::$app->has('response')) {
			response()->send($send);
		}
		return $send;
	}

	/**
	 * @return mixed
	 */
	public function getErrorMessage()
	{
		$message = $this->message;
		$this->message = NULL;
		return $message->getData();
	}

	/**
	 * @return bool
	 */
	public function getAsError()
	{
		return $this->message !== NULL;
	}

	/**
	 * @param $message
	 * @param $category
	 *
	 * @throws \Exception
	 */
	public function writer($message, $category = 'app')
	{

		$path = \Beauty::$app->runtimePath . '/log';
		if (!is_dir($path)) mkdir($path, 777);

		if (!empty($category)) {
			$path .= '/' . $category;
			if (!is_dir($path)) mkdir($path);
		}

		$path = realpath($path);

		$logFile = $path . '/server.log';
		if (file_exists($logFile) && filesize($logFile) >= 4 * 1024000) {
			$logCount = count(glob($path . '/*'));
			rename($logFile, $logFile . '.' . $logCount);
			if (!file_exists($logFile)) {
				touch($logFile);
			}
		};
		if ($message instanceof \Exception) {
			$message = '[' . date('Y-m-d H:i:s') . ']' . $message->getMessage();
		} else {
			if (is_array($message)) {
				if (count($message) == 1) {
					$message = '[' . date('Y-m-d H:i:s') . ']' . current($message);
				} else {
					$message = ['[' . date('Y-m-d H:i:s') . ']' => $message];
				}
			} else if (is_object($message)) {
				$message = ['[' . date('Y-m-d H:i:s') . ']' => get_object_vars($message)];
			} else {
				$message = '[' . date('Y-m-d H:i:s') . ']' . $message;
			}
		}
		if (!empty($message)) {
			file_put_contents($logFile, print_r($message, TRUE) . PHP_EOL, FILE_APPEND);
		}
	}

	/**
	 * @param $offset
	 * @param int $limit
	 * @param string $category
	 *
	 * @return array|string
	 * 从文件读取错误日志
	 */
	public function reader($offset, $limit = 1000, $category = '')
	{
		if (!empty($category)) {
			$path = \Beauty::$app->runtimePath . '/log/' . $category . '/server.log';
		} else {
			$path = \Beauty::$app->runtimePath . '/log/error/server.log';
		}
		if (!file_exists($path)) {
			return '';
		}
		$content = explode(PHP_EOL, file_get_contents($path));
		if (count($content) < $limit) {
			return $content;
		}
		return array_slice($content, $offset, $limit);
	}


	/**
	 * @return int
	 */
	public function getCount()
	{
		$path = \Beauty::$app->runtimePath . '/log/server.log';
		if (!file_exists($path)) {
			return 0;
		}
		return count(file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES));
	}
}
