<?php


namespace Yoc\db;


use Yoc\event\Event;

class AfterSaveEvent extends Event
{

	public $isVild = true;

	public $attributes = [];

	public $changeAttributes = [];


	public function handler()
	{

	}
}
