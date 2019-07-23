<?php


namespace Beauty\http;


use Beauty\base\Component;
use Beauty\validator\Validator;
use Beauty\exception\AuthException;
use Beauty\exception\RequestException;

class HttpFilter extends Component
{

	public $body = [];

	public $header = [];

	public $grant = [];

	/**
	 * @throws \Exception
	 */
	public function handler()
	{
		if (!empty($this->body)) {
			$this->bodyRun();
		}
		if (!empty($this->header)) {
			$this->headerRun();
		}

		if (!is_callable($this->grant, true)) {
			return;
		}
		if (!call_user_func($this->grant)) {
			throw new AuthException("Authentication error.");
		}
	}

	/**
	 * @return bool
	 * @throws \Exception
	 */
	private function bodyRun()
	{
		if (!is_array($this->body)) {
			return true;
		}

		$this->validator($this->body, Input()->load());

		return true;
	}

	/**
	 * @return bool
	 * @throws \Exception
	 */
	private function headerRun()
	{
		if (!is_array($this->header)) {
			return true;
		}

		$headers = request()->headers->getHeaders();

		$this->validator($this->header, $headers);

		return true;
	}

	/**
	 * @param $rule
	 * @param $data
	 * @return bool
	 * @throws \Exception
	 */
	private function validator($rule, $data)
	{
		$validator = Validator::getInstance();
		$validator->setParams($data);
		foreach ($rule as $val) {
			$field = array_shift($val);
			if (empty($val)) {
				continue;
			}
			$validator->make($field, $val);
		}

		if (!$validator->validation()) {
			throw new RequestException($validator->getError());
		}
		return true;
	}
}
