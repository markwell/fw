<?
namespace fw\classes;

use fw\helpers\Translit;

use fw\classes\Access;



class App {
	
	public static $controller;
	public static $action;
	public static $config;
	
		//время работы приложения
	private static $time_start;
	
	public static $dir;
	
	
		//запускает приложение
	public static function init($controller=null,$action=null) {
	
		
		self::olds();	//перенаправление для старых браузеров
		
		$time_start = microtime(true); //счетчик времени
		
		
			//если существует токен подключение
		if(isset($_COOKIE["fw_access_token"])) {
			if( !Access::get() ) { return false; }
		}
		else {
			define('DEV', false);
		}
		
		
		
			//определение контроллеров и т.д.
		$url = Translit::russian(urldecode($_SERVER["REQUEST_URI"])); 
		
		
			//get-параметры и hash
		$pos = strpos($url, "?"); if($pos) { $url = substr($url, 0, $pos); }
		$pos = strpos($url, "#"); if($pos) { $url = substr($url, 0, $pos); }
		
		
		$url = explode("/",$url);
		$url = array_diff($url,[""]);
		$url = array_values($url);
		
		
		if(isset($url[0]) && $url[0] == "index.php") {
			unset($url[0]);
			$url = array_values($url);	
		}
		
		
			//если это favicon (умный chrome)
		if(isset($url[0]) && $url[0] == "favicon.ico") { die(); }
			
			
			//если это виджет
		if(isset($url[0]) && $url[0] == "widget") {
			return self::widget($url[1],$url[2]);
		}
		if(isset($url[0]) && $url[0] == "class") {
			return self::classes($url[1],$url[2]);
		}
		
			//если это cron
		if(isset($url[0]) && $url[0] == "cron" ) {
			self::cron();
			return false;
		}
			
		if(isset($url[0]) && $url[0] == "livereload" ) {
			self::livereload($_POST["files"]);
			return false;
		}
		
			//если в приложении не указан контроллер
		if(!isset($controller)) {
			if(isset($url[0])) {
				$controller = $url[0];
				array_shift($url);
				$url = array_values($url);
			} else {
				$controller = "index";
			
			}
		}

		//если в приложении не указан экшен
		if(!isset($action)) {
			if(isset($url[0])) {
				$action = $url[0];
				array_shift($url);
				$url = array_values($url);
			} else {
				$action = "index";
			
			}
		}

		
		
		self::run($controller,$action,$url);
		
		
		//if(defined(TIME)) {
		//	$time = $time_start - microtime(true);
		//	error_log($_SERVER["REQUEST_URI"]." ".round($time,4));
		//}
		
	}




	public static function run($controller,$action="index",$vars=[]) {
		
		
		
		
		$controllerApp = "\\".APP."\controllers\\".ucfirst($controller).'Controller';
		$controllerFw  = "\\fw\\controllers\\".ucfirst($controller).'Controller';
		
		
		
		if(class_exists($controllerApp)) {
			
			$cont = new $controllerApp($controller);
			$cont -> __action($action,$vars);
		}
		
		elseif(class_exists($controllerFw)) {
			$cont = new $controllerFw($controller);
			$cont -> __action($action,$vars);
		}
		
		else {
			//error_log($controllerApp);
			self::err(404);
		}
	}

	

		//получить время выполнение приложения
	public static function getTime() {
		return microtime(true) - self::$time_start;
	}






		//обработчик ошибок
	public static function err($code=404) {
		//App::run("Error",$code);
		if(class_exists("\\".APP."\\controllers\ErrorController")) {
			
		}
		else {
		 	header("HTTP/1.0 ".$code." ");
		}
		die();
	}
	
	
		//обработчик виджетов
	public static function widget($widget,$action="ajax") {

		$class = "\\fw\\widgets\\".ucfirst($widget);
		
		if(class_exists($class)) {
			$class::$action();	
		}
	}
	
	
		//обработчик классов
	public static function classes($class, $action) {
		
			//находим класс
		$class = "\\fw\\classes\\".ucfirst($class);
		
			//проверяем, есть ли к нему доступ
		if(!class_exists($class)) { exit; }
		if(!in_array($action, $class::$open)) { exit; }
		
			//выполняем нужный метод
		$class::$action();
	}
	
	
	
		//планировкщик
	public static function cron() {
		//if(premission("!")) {
			$cronfile = dirname(dirname(__DIR__)).'/app/config/cron.php';
			if(file_exists($cronfile)) {
				include $cronfile;
				foreach($cron as $cr) {
					App::run($cr["controller"],$cr["action"]);
				}
			}
		//}
	}
	
	
	public static function livereload($files) {
		
		$files = json_decode($files);
		
		$time = self::get_times($files);
		
		for($i=0; $i<100; $i++) {
			
			if( self::get_times($files) > $time) {
				echo 'reload'; exit;
			}
			usleep(300000);
		}
		
		
		
		exit;
	}
	
	public static function get_times($files) {
		$time = 0;
		clearstatcache();
		foreach($files as $file) {
			if(file_exists($file)) {
				
				if(filemtime($file)>$time) {
					$time = filemtime($file);
				}
			}
		}
		return $time;
	}
	
	
	
	
		//для старых браузеров
	public static function olds() {
		if(preg_match('/(?i)msie [1-8]/',$_SERVER['HTTP_USER_AGENT'])) {
			header("Location: http://browser-update.org/ru/update.html");
			exit;
		}
	}
	
	
}




?>