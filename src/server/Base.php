<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/11/8 0008
 * Time: 18:37
 */

namespace Beauty\server;


use Beauty\base\Component;

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
			$this->server = \Beauty::$app->socket->getSocket();
		}
		
		$this->onHandler(...$all);
	}
	
}
