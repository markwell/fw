<?
namespace fw\models;

use fw\classes\Model;

class Controllers extends Model {
	
	public $fields = [
		"title" => [
			"title" => "Название на русском",
			"unique"=> true,
		],
		'name' => [
			'title'  => "Наименование",
			'regexp' => "engone",
			'unuque' => true,
			'size'   => [3,30],
			"change" => false,
		],
		
		"description" => [
			"title" => "Описание",
			"type"  => "html",
		],
		
		
	];
	
	
}
?>
