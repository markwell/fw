<?
namespace fw\models;

use fw\classes\Model;

class ControllersAction extends Model {
	
	public $fields = [
		"title" => [
			"title" => "Название на русском",
		],
		
		"controller" => [
			"title" => "Контроллер",
			"relation" => [
				"model" => "fw\models\Controllers",
				"index" => "id",
				"title" => "name",
			],
			"change" => false,
		],
		
		'name' => [
			'title'  => "Наименование",
			'regexp' => "engone",
			'size'   => [3,30],
			"change" => false,
		],
		
		'tab' => [
			"title" => "Это вкладка",
			"type"  => "bool",
		],
		'btn' => [
			"title" => "Это кнопка",
			"type"  => "bool",
		],
		
		
		'icon' => [
			'title' => 'Иконка',
			"type"  => "icon"
		],
		
		"users" => [
			"title" => "Пользователи, имеющие доступ",
			"size"  => 100,
			"relation" => [
				"model" => "Users",
				"index" => "id",
				"title" => "title",
			],
		],
		"groups" => [
			"title" => "Группы, имеющие доступ",
			"size"  => 100,
			"relation" => [
				"model" => "UsersGroup",
				"index" => "id",
				"title" => "title",
			],
		],
		
		"description" => [
			"title" => "Описание",
			"type"  => "html",
		],
		
	];
	
	
}
?>
