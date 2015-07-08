<?
namespace fw\classes;

class File {
		
	
		//получить название файла, первые символы которого - деректории
	public static function subdirs($file,$dirs=2) {
		if($dirs == 1) {
			$file = substr($file, 0, 2).'/'.substr($file, 2);
		} elseif($dirs == 2) {
			$file = substr($file, 0, 2).'/'.substr($file, 2, 2).'/'.substr($file, 4);
		} elseif($dirs == 3) {
			$file = substr($file, 0, 2).'/'.substr($file, 2, 2).'/'.substr($file, 4, 2).'/'.substr($file, 6);
		}
		return $file;
	}
	
	
		//создает директорию вместе со всеми поддиректориями
	public static function mkdir($dir) {
		$array = explode("/", $dir);
		
			//если передан файл -> то цель = его директория
		if(strpos($array[sizeof($array)-1], ".")) {
			$dir = dirname($dir);
		}
		

			//если директория не существует
		if(!file_exists($dir)) {
			
				//если не существует директория родителя -> создаем ее
			if(!file_exists(dirname($dir))) {
				self::mkdir(dirname($dir));
			}
				//создаем текущую директорию
			mkdir($dir);		
		}
	}
	
	
		//записывает содержимое в файл
	public static function write($file,$content) {
		
		if(!file_exists($file)) { self::mkdir($file); }
		
		$fp = fopen($file, "w");
		fwrite($fp, $content);
		fclose($fp);
	}
	
}