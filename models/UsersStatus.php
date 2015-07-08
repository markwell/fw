<?
namespace fw\models;

use fw\classes\Model;

class UsersStatus extends Model {
	

	public $fields = [
		"title" => [
			"title" => "Заголовок",
			"size"  => [3,60],
		],
		"color" => [
			"title" => "Цвет",
			"type"  => "color",
		],
		"text" => [
			"title" => "Описание",
			"type"  => "text",
		],
	];
	
	
}

