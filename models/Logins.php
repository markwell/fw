<?
namespace fw\models;

use fw\classes\Model;

class Logins extends Model {
	public $fields = [
		"token" => [
			"title" => "Токен",
		],
		"ip" => [
			"title" => "IP-адресс",
		],
		"agent" => [
			"title" => "Браузер",
		],
		"success" => [
			"title" => "Успешный вход",
			"type"  => "bool",
		],
		"user" => [
			"title" => "Пользователь",
			"relation" => [
				"model" => "Users",
				"index" => "id",
				"title" => "title",
			],
		],
		"login" => [
			"title" => "Время входа",
			"type"  => "dt",	
		],
		"active" => [
			"title" => "Время последней активности",
			"type"  => "dt",
		],
		"status" => [
			"title" => "Статус",
			"relation" => [
				"model" => "UsersStatus",
				"index" => "id",
				"title" => "title",
			],
		],
		"comment" => [
			"title" => "Комментарий",
		],
	];
}	
?>