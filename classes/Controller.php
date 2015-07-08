<?
namespace fw\classes;

use fw\classes\View;
use fw\classes\App;
use fw\classes\Cache;
use fw\classes\Access;

class Controller {
	
	protected $behaviors;	//поведения
	
	protected $views;		//папка с представлениями
	
	protected $controller_name; // имя контроллера
	
		//перенаправляет на нужный action
	public function render($action,$vars=[]) {
		
		
			//директория для экшена
		$dir = $this->controller_name;
		
			//если в экшене есть слеш -> значит указан контроллер
		if(strstr($action, '/')) {
			$dir = explode('/', $action);
			$action = $dir[1];
			$dir = $dir[0];
		}
		
			//находим папку с представлениями для данного контроллера
		new View($dir.'/'.$action,$vars,$this);
	}
	
	
		//проверяет и вызывает нужный action
	public function __action($method, array $args = array()) {
		
	
		
			//ищем метод
		if(!method_exists($this, $method)) {
			if(method_exists($this, "action".ucfirst($method))) {
				$method1 = "action".ucfirst($method);
			}
			elseif(method_exists($this, "action_".$method)) {
				$method1 = "action_".$method;
			} 
			elseif(method_exists($this, "action_404")) {
				$method1 = "action_".$method;
			}
			else { App::err(404); }
		} else {
			$method1 = $method;
		}
		
			//проверяем права доступа
		$access = false;
		
		
			//если есть доступ всем
		if(isset($this->public)) {
			
	
			if(is_array($this->public)) {
				if(empty($this->public) || in_array($method, $this->public)) {
					$access = true;
				}
			}
		}
		
		
			//если доступ всем закрыт
		if($access === false) {
			
				//если авторизация не пройдена
			if(!isset($_COOKIE["fw_access_token"])) {
				
					//перенаправляем на страницу авторизации
				App::run("login");
				
				return false;
			}
				
				//иначе проверяем, имеется ли доступ к этому экшену
			else {				
				$rule = $this -> controller_name.'/'.$method;
				
					//если доступ закрыт - сразу останавливаем
				if(!Access::open($rule)) {
					App::err(403);
					return false;
				}
			}
		}
				
		
			//проверяем на кеширование
		$time     = 0;
		$models   = [];
		//$token    = "controller".$this->views.$method.implode(",", $args).implode("", $_POST).implode("", $_GET);
		
		$token 	= $_SERVER["REQUEST_URI"];
		$token   .= (isset($_SERVER["HTTP_X_PJAX"])) ? " (pjax)" : "";
		
		$comment = ucfirst($this -> controller_name)."Controller -> ".$method;
		
		$type	= "html";
		
			//если нужно кешировать
		if(isset($this->cache[$method])) {
			
			if(is_numeric($this->cache[$method])) {
				$time = $this->cache[$method];
			} elseif(is_array($this->cache[$method])) {
				
				foreach($this->cache[$method] as $one) {
					if(is_numeric($one)) {
						$time = $one;
					} else {
						$models[] = $one;
					}
				}
			}
			
		}
		
		
		
		Cache::init([
			"token"  => $token,
			"time"   => $time,
			"type"   => "render",
			"models" => $models,
			"comment"=> $comment,
			"vars"   => [
				"this"   => $this,
				"method" => $method1,
				"args"   => $args,
			],
			"function" => function($vars) {
				
				extract($vars);
				$reflection = new \ReflectionMethod($this, $method);
				return $reflection->invokeArgs($this, $args);
			}
		]);
		
		

	}
	
	
	function __construct($view=null) {
		
		$dir = (new \ReflectionClass(get_called_class())) -> getFileName();
		
		if(isset($view)) {
			$dir = dirname(dirname($dir));
			$this -> views = $dir."/views/".$view;
			
			$this -> controller_name = $view;
		}
	}
}
	
?>
