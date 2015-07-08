<?
namespace fw\models;

use fw\classes\Model;

class UsersChat extends Model {
	
	public $fields = [
		"from" => [
			"title" => "От кого",
			"relation" => [
				'model' => "Users",
				'index' => 'id',
				'title' => 'title',
			],
		],
		'to_user' => [
			'title' => "Какому пользователю",
			"relation" => [
				'model' => "Users",
				'index' => 'id',
				'title' => 'title',
			],
		],
		'to_group' => [
			'title' => 'Какой группе',
			"relation" => [
				'model' => 'UsersGroup',
				'index' => 'id',
				'title' => 'title',
			],
		],
		'text' => [
			"title" => "Сообщение",
			"type"  => "text",
		],
		'readed' => [
			"title" => "Прочтено",
			"type"  => "bool",
		],
	];
	
	
}
?>
