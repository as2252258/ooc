<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/11/8 0008
 * Time: 18:37
 */

namespace Yoc\server;


use Yoc\base\Component;

abstract class Base extends Component implements IServer
{
	
	/** @var \swoole_server */
	protected $server;
	
	/**
	 * base constructor.
	 * @param array $all
	 */
	public function __construct(...$all)
	{
		parent::__construct([]);
		
		if (!($this->server instanceof \swoole_server)) {
			$this->server = \Yoc::$app->socket->getSocket();
		}
		
		$this->onHandler(...$all);
	}
	
}
