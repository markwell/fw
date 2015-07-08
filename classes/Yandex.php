<?
namespace fw\classes;

use \Yandex\Disk\DiskClient;
use \fw\classes\App;

class Yandex {
	
	
	
		//получает содержимое файла
	public static function getContent($file) {
			
			//устанавливаем соединение с диском
		self::connect();
		
			//определяем временную папку для файла
		$dir = DIR.'/assets/tmp/';
		if(!file_exists($dir)) { mkdir($dir); }
		$dir = DIR.'/assets/tmp/'.date("Y-m-d")."/";
		if(!file_exists($dir)) { mkdir($dir); }
		
			//генерируем имя файла
		$name = md5(uniqid()).".txt";
		
			//скачиваем файл и получаем его содержимое
		self::$disk->downloadFile($file, $dir, $name);
		$content = file_get_contents($dir.$name);
		
			//удаляем файл (с яндекс диска тоже)
		//unlink($dir.$name);
		self::$disk->delete($file);
		
			//возвращаем содержимое файла
		return $content;
	}
	
	
	
		//получает все файлы из папки
	public static function getDir($dir) {
		
			//устанавливаем соединение с диском
		self::connect();
		
			//получаем все что лежит в папке на диске
		$files = self::$disk->directoryContents($dir);
		$res = [];
		
			//получаем дату создания и контент для каждого файла
		foreach($files as $file) {
			
			if($file["resourceType"] != "dir") {
				$res[] = [
					"file" 	=> $file["displayName"],
					"create" 	=> $file["creationDate"],
					"content" => self::getContent($file["href"]),
					
				];	
			}
		}
		
			//возвращаем массив с файлами
		return $res;
	}
	
	
	
	
		//подключается к яндекс диску
	private static function connect() {
		if(empty(self::$disk)) {
			require_once(DIR."/config/yandex.php");
			self::$disk = new DiskClient($yandex);
		}
	}
		
		//объект для работы с диском
	private static $disk;
	
	
}