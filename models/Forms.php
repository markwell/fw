<?
namespace fw\models;

use fw\classes\Model;

class Forms extends Model {
	
		//все публично
	public $public = [];
	
	public $fields = [
		'id' => [
			'title' 	=> "Номер",
		],
		'token' => [
			'title' 	=> "Токен",
			'size'	=> 64,
		],
		'model' => [
			'title' => "Модель",
		],
		'model_id' => [
			'title' => "Номер поля в модели",
			'type'  => "int",
		],
		'fields' => [
			'title' => "Доступные поля",
		],
		'create' => [
			'title' => "Создать",
			'type' => "bool",
		],
		'update' => [
			'title' => "Обновить",
			'type' => "bool",
		],
		'delete' => [
			'title' => "Удалить",
			'type' => "bool",
		],
		'login' => [
			'title' => "Авторизация",
			'type' => "bool",
		],
		'files' => [
			'title' => "Файлы",
			'type' => "text",
		],
		'ratio' => [
			"title" => "Соотношение сторон",
			"type"  => "float",
		],
		'default' => [
			'title' => "Значения по умолчанию",
		],
		
		
	];
	
	public $trigger = false;
	
}