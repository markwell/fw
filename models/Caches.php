<?
namespace fw\models;

use fw\classes\Model;

class Caches extends Model {
	
	public $fields = [
		'file' => [
			'title' => "Файл c кешем",
			'size' => 50,
		],
		'time' => [
			'title' => "Время истечения срока",
			'type'  => "dt",
		],
		'models' => [
			'title' => "Удалить при изменении моделей",
			"type"  => "tags",
		],
		'comment' => [
			"title" => "Комментарий",
		],
	];
	
	public $trigger = false;
}
?>
