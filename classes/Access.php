<?
namespace fw\classes;

use fw\models\ControllersAction;
use fw\models\Controllers;
use fw\models\Models;
use fw\models\Logins;
use fw\classes\File;

class Access {
	
		//возвращает права доступа для нужного пользователя
	public static function to($user,$groups) {
		
			//получаем данные из моделей
		$prem 	= [];
		$actions 	= ControllersAction::init() -> get("users","groups","name","controller.name");
	
		
			//экшены
		foreach($actions as $action) {
			if( !isset( $prem[$action["controller"]["name"]] ) ) { $prem[$action["controller"]["name"]] = []; }
			$prem[$action["controller"]["name"]][$action["name"]] = [
				"groups" => $action["groups"],
				"users"  => $action["users"],
			];
		}
		
		$list = [
			"index/index"   => true,
			"index/socket"  => true,
			"file/show_img" => true,
			"file/crop_img" => true,
		];
		
			//смотрим права для пользователя
		foreach($prem as $i => $first) {
			foreach($first as $id => $second) {
				
					//название правила
				$rule = $i.'/'.$id;
				
					//если не указаны права
				//if(empty($second["users"]) && empty($second["groups"])) {
				//	$list[$rule] = true;
				//}
				
					//если досптупно пользователю
				if( in_array($user, $second["users"]) ){
					$list[$rule] = true;
				}
				
					//если доступно группе, в которой есть пользователь
				else {
					foreach($groups as $group) {
						if( in_array($group, $second["groups"]) ){
							$list[$rule] = true;
							break;
						}
					}
				}
				
			}
		}
		
		return $list;
	}
	
		//проверяет: открыт ли доступ
	public static function open($rule) {
		
			//для разработчика доступ открыт всегда
		if(DEV == true) { return true; }
		
			//для самого сервера
		//elseif($_SERVER["REMOTE_ADDR"] === $_SERVER["SERVER_ADDR"]) { return true; }
		
			//если нет файла куки - просим авторизоваться
		elseif(!isset($_COOKIE["fw_access_token"])) { App::run("login"); }
		
			//если есть доступ
		elseif(isset(self::$content["access"][$rule])) { return true; }
		
		return false;
	}
	
		//тут храниться информация о сеансе (из файла)
	public static $content = null;

		//получает содержимое файла и записывает в self::$content;
	public static function get() {
			
		$token = $_COOKIE["fw_access_token"];
		
			//если нет файла
		$file = DIR.'/assets/tmp/access/'.File::subdirs($token.".json"); if(!file_exists($file)) { self::to_login(); return false; }
		
			//читаем содержимое файла
		self::$content = json_decode(file_get_contents($file), true);
		
			//если вышло время
		if(self::$content["time"] < time()) { self::to_login(); return false; }
		
			//если сменился IP
		if(strpos($_SERVER["REMOTE_ADDR"], '.')) {
			$last_ip = explode('.', self::$content["ip"]);
			$this_ip = explode('.', $_SERVER["REMOTE_ADDR"]);
			$last_ip = $last_ip[0].'.'.$last_ip[1].'.'.$last_ip[2];
			$this_ip = $this_ip[0].'.'.$this_ip[1].'.'.$this_ip[2];
		} else {
			$last_ip = self::$content["ip"];
			$this_ip = $_SERVER["REMOTE_ADDR"];	
		}
		if($last_ip != $this_ip) {  self::to_login(); return false; }
		
			//если сменился браузер
		if(self::$content["agent"] != $_SERVER["HTTP_USER_AGENT"]) { self::to_login(); return false; }
		
			//если это разработчик
		if(self::$content["dev"] === true) {
			define("DEV", true);
		} else {
			define("DEV", false);
		}
		
		return true;

	}
	
		//переводит на страницу авторизации
	public static function to_login() {
		define('DEV', false);
		App::run("login");
	}
	
		//выполняет выход
	public static function logout($token=null) {
		
			//если не передан токен - значит завершаем сессию текущего пользователя
		if($token == null || ( isset($_COOKIE["fw_access_token"]) && $_COOKIE["fw_access_token"] == "token") ) {
			if(!isset($_COOKIE["fw_access_token"])) { return false; }
			
			$token = $_COOKIE["fw_access_token"];
			
			setcookie("fw_access_token","0",time()-3600,'/');
		}
		
			//удаляем файл на сервере		
		$file = DIR.'/assets/tmp/access/'.File::subdirs($token.".json");
		if(file_exists($file)) { unlink($file); }


			//изменяем запись в БД
		$model = Logins::init() -> find("token",$token);
		if( $model->check() ) {
			$model -> update(["status" => 0]);
		}
		
	}
	
		//выполняем вход
	public static function login() {
		
	}
	
		//проверяет, существует ли сессия до сих пор
	public static function exist($token=null) {
		
			//если не передан токен - значит проверяем сессию текущего пользователя
		if($token == null) {
			
			if(!isset($_COOKIE["fw_access_token"])) { return false; }
			
			$token = $_COOKIE["fw_access_token"];
		}
		
		$file = DIR.'/assets/tmp/access/'.File::subdirs($token.".json");
			
		if(!file_exists($file)) {
			self::logout();
			return false;
		}
		return true;
		
	}

	
		//обновляет время активности пользователя
	public static function active($token=null) {
		
			//если не передан токен - значит выполняем для текущего пользователя
		if($token == null) {
			if(!isset($_COOKIE["fw_access_token"])) { return false; }
			
			$token = $_COOKIE["fw_access_token"];
			
		}
		
			//обновляем время активности
		Logins::init() -> find("token",$token) -> update("active",date("Y-m-d H:i:s"));
		
	}

	public static function getUserId() {
		$token = $_COOKIE["fw_access_token"];
		$file = DIR.'/assets/tmp/access/'.File::subdirs($token.".json");
		$content = json_decode(file_get_contents($file), true);
		return $content['user'];
		
	}
	
	
}

?>