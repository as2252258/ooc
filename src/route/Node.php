<?php


namespace Yoc\route;


use Yoc\exception\NotFindClassException;

/**
 * Class Node
 * @package Yoc\route
 */
class Node
{

	public $path;
	public $index = 0;
	public $method;

	/** @var Node[] $childs */
	public $childs = [];

	public $group = [];
	public $options = null;

	public $rules = [];
	public $handler;
	public $htmlSuffix = '.html';
	public $enableHtmlSuffix = false;
	public $namespace = [];
	public $middleware = [];

	/**
	 * @param Node $node
	 * @param string $field
	 * @return Node
	 */
	public function addChild(Node $node, string $field)
	{
		if (isset($this->childs[$field])) {
			$this->childs[$field] = $node;
		} else {
			$this->childs[$field] = $node;
		}
		return $this->childs[$field];
	}

	/**
	 * @param $rule
	 * @return $this
	 */
	public function filter($rule)
	{
		if (empty($rule)) {
			return $this;
		}
		if (!isset($rule[0])) {
			$rule = [$rule];
		}
		foreach ($rule as $value) {
			if (empty($value)) {
				continue;
			}
			array_push($this->rules, $value);
		}

		return $this;
	}

	/**
	 * @param string $search
	 * @return Node|mixed
	 * 查找子节点
	 */
	public function findNode(string $search)
	{
		if (empty($this->childs)) {
			return null;
		}
		foreach ($this->childs as $key => $val) {
			if (strpos($key, '<') !== false) {
				if (preg_match('/' . $search . '/', $key)) {
					return $this->childs[$key];
				}
			}

			if ($search === $key) {
				return $this->childs[$key];
			}
		}
		return null;
	}


	/**
	 * @param $call
	 * @return mixed
	 * @throws
	 */
	public function run($call)
	{
		if (empty($call)) {
			throw new NotFindClassException();
		}
		$mid = new Middleware();
		$mid->bindParams(\Yoc::$app->request);

		if (!empty($this->rules)) {
			foreach ($this->rules as $rule) {
				if (!isset($rule['class'])) {
					$rule['class'] = Filter::class;
				}

				$object = \Yoc::createObject($rule);
				$object->handler();
			}
		}

		foreach ($this->middleware as $val) {
			$mid->set($val);
		}
		return $mid->exec($call);
	}

	/**
	 * @param $call
	 * @return $this
	 */
	public function bindOptions($call)
	{
		$this->options = $call;
		return $this;
	}


	/**
	 * @return mixed
	 */
	public function execOptions()
	{
		return call_user_func($this->options);
	}
}
