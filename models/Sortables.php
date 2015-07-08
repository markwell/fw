<?
namespace fw\models;

use fw\classes\Model;

class Sortables extends Model {
	
	public $fields = [
		"token" => [
			"title" => "Токен",
			"size"  => 64,
		],
		"model" => [
			"title" => "Модель",
		],
		"field" => [
			"title" => "Поле сортировки",
			"size"  => 50,
		],
		
		
	];	
	
	
}
