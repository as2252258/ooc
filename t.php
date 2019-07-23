<?php


require_once 'vendor/autoload.php';


class Ac extends \Beauty\db\ActiveRecord{


	public static function tableName()
	{
		return 'aaaa';
	}

}

$model = new Ac();
$model->attributes = [
	'id' => 1,
	'name' => 2
];
$model->save();
