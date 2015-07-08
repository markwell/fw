<?
namespace fw\models;

use fw\classes\Model;

class Users extends Model {

	public $fields = [
		"name" => [
			"title" => "Имя",
			"regexp" => "engone",
		],
		"pass" => [
			"title" => "Пароль",
			"type"  => "password",
		],
		"title" => [
			"title" => "Имя на русском",
			"encrypt" => false,
		],
		"active" => [
			"title" => "Активный",
			"type"  => 'bool',
		],
		'groups' => [
			"title" => "Группа",
			"size"  => 10,
			"relation" => [
				"model" => "UsersGroup",
				"index" => "id",
				"title" => "title",
			]
		],
		'photo' => [
			"title" => "Фото",
			"type"  => "files",
			"ext"   => ["jpg","png"],
			"size"  => 1,
			"ratio" => 1/1,
		],
		'chat' => [
			"title" => "Участвует в чате",
			"type"  => "bool",
		],
	];
	
}