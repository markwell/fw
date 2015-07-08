<?
namespace fw\models;

use fw\classes\Model;

class UsersGroup extends Model {
	

	public $fields = [
		"title" => [
			"title" => "Заголовок",
			"size"  => [3,60],
		],
		"text" => [
			"title" => "Описание",
			"type"  => "text",
		],
		'chat' => [
			'title' => 'Группа для чата',
			'type'  => 'bool',
		],
		'icon' => [
			"title" => "Иконка",
			"type"  => "icon",
		],
	];
	
	
}

