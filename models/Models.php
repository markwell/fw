<?
namespace fw\models;

use fw\classes\Model;

class Models extends Model {
	
	public $fields = [
		"title" => [
			"title" => "Название на русском",
		],
		'name' => [
			'title'  => "Наименование",
			'regexp' => "engone",
			'unuque' => true,
			'size'   => [3,50],
			"change" => false,
		],
		
		"description" => [
			"title" => "Описание",
			"type"  => "html",
		],
		
		"fields" => [
			"title" => "Поля",
			"type"  => "tags",
		],
				
		
	];
	
	
}
?>
