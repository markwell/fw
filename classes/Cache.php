<?
namespace fw\classes;

use fw\models\Caches;

use fw\classes\File;

class Cache {
	
	
	public static $type = "json";
	
	
	
	public static function init($config) {
		
		
			//получаем/генерируем токен
		$token = $config["token"];
		if(is_string($token) || is_numeric($token)) { $token = md5($token); } else { $token = md5(json_encode($token)); }
		$config["token"] = $token;
		
			//тип по умолчанию
		if(!isset($config["type"])) { $config["type"] = "json"; }
		
			//время по умолчанию
		if(!isset($config["time"])) { $config["time"] = 60; }
		if(!isset($config["comment"])) { $config["comment"] = null; }
		if(!isset($config["models"])) { $config["models"] = []; }
		
		
		if($config["time"] == 0) {
		
				//если время = 0, просто выполняем то что нужно
			return self::nocache($config);
		} else {
			
				//иначе пробуем получить кеш
			return self::get($config,0);	
		}
		

		
	}
	
	
	
	
		//пробует получить кеш, и если надо - создает его
	public static function get($config,$x) {
		
		//$file = DIR."/assets/tmp/cache/".$config["token"];
		$file_j = DIR.'/assets/tmp/cache/'.File::subdirs($config["token"].".json");
		$file_h = DIR.'/assets/tmp/cache/'.File::subdirs($config["token"].".html");
		
		
		
		if($config["type"] == "render") {
			if(file_exists($file_h) && (filemtime($file_h) + $config["time"]) > $_SERVER["REQUEST_TIME"]) {
				echo file_get_contents($file_h);
			} elseif(file_exists($file_j) && (filemtime($file_j) + $config["time"]) > $_SERVER["REQUEST_TIME"]) {
				header('Content-type: application/json');
				echo file_get_contents($file_j);
			} else {
				echo self::create($config);
			}
		}
		
		else {
			if(file_exists($file_j) && (filemtime($file_j) + $config["time"]) > $_SERVER["REQUEST_TIME"]) {
				return json_decode(file_get_contents($file_j), true);
			} else {
				return json_decode(self::create($config), true);
			}
		}
		
		
	}
	
	
		//создание/обновление кеша
	public static function create($config) {
		
			//тип render
		if($config["type"] == "render") {
			ob_start();
				$config["function"]($config["vars"]);
				$content = ob_get_contents();
			ob_end_clean();
				
		}
			//тип return
		else {
			
			$content = json_encode($config["function"]($config["vars"]),JSON_UNESCAPED_UNICODE);
		}
		
		
		
		
		$file = File::subdirs($config["token"].".".self::$type);
		
		File::write(DIR."/assets/tmp/cache/".$file,$content);
		
		
		Caches::init() -> find("file",$file) -> update([
			"file"    => $file,
			"comment" => $config["comment"],
			"models"  => $config["models"],
			//"time"    => $_SERVER["REQUEST_TIME"] + $config["time"],
		],true);
		
		
		return $content;
	}
	
		//возвращает результат, когда кешировать не нужно
	public static function nocache($config) {
		
		return $config["function"]($config["vars"]);
	}
	
		//очищает кеш
	public static function clear($model) {
		
	}
	
	
	
}





?>