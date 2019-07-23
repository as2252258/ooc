<?php


namespace Beauty\db;


use Beauty\event\Event;

class AfterSaveEvent extends Event
{

	public $isVild = true;

	public $attributes = [];

	public $changeAttributes = [];


	public function handler()
	{

	}
}
