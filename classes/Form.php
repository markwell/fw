<?
namespace fw\classes;


use fw\classes\Assets;
use fw\classes\Widget;
use fw\helpers\Html;
use fw\helpers\Icons;
use fw\classes\Model;
use fw\classes\Cryptor;
use fw\classes\App;
use fw\classes\File;
use fw\classes\Access;

use fw\models\ControllersAction;
use fw\models\Controllers;
use fw\models\Models;
use fw\models\Logins;
use fw\models\Forms;

class Form {
		
		//тут хранится объект модели
	protected $model;

		//конструктор
	function __construct($model, $id) {
				
			//сохраняем класс модели
		$this -> model = Model::getClass($model);
		
			//добавляем ресурсы
		Assets::add('form');
		
			//сохраняем id который будем изменять/удалять
		$this -> config["id"] = $id;
		
		return $this;
	}
	public static function init($model, $id=null) {
		$class = get_called_class();
		return new $class($model, $id);
	}
	
		//настройки
	public $config = [
		"label"   => true,
		"class"   => "form-control",
		"id"      => null,
		"fields"  => [],
		"type"    => "insert",
		"files"   => "",
		"default" => "",
		"reload" => "",
		'button'  => null,
		"ratio"   => 0,
		"copy"    => null,
	];
	
		//указывает какие поля будут использоваться
	public function fields() {
		$fields = func_get_args();
	
		foreach($fields as $field) {
			if(is_array($field)) {
				foreach($field as $f) {
					$this -> config["fields"][] = $f;
				}
			} else {
				$this -> config["fields"][] = $field;
			}
		}
		return $this;
	}

		//показывает с какого id нужно скопировать данные
	public function copy($id) {
		$this -> config['copy'] = $id;
		return $this -> get('c');
	}
	
	
	
	
		//удаляет запись из БД
	public static function delete() {
		
		$token = $_POST['form-token'];
		$form = Forms::init() -> find("token",$token) -> one();
		
		if($form["delete"] == 1) {
			$model = new $form["model"]();
			$model -> id($form["model_id"]);
			$model -> del();
			
		} else {
			App::err(403);
		}
		
	}
	
		//получает форму по ajax
	public static function save() {
		
		$error   = [];
		$success = [];
		
			//проверяем на существование токена
		$token = $_POST["form_token"];
		$form = Forms::init() -> find("token",$token) -> one();
		
		$model = new $form["model"]();
		
		$fields = explode(",", $form["fields"]);
		$values = [];
		
		if(!empty($form["default"])) {
			$def = json_decode($form["default"], true);
			foreach ($def as $key => $value) {
				$_POST[$key] = $value;
				if(!in_array($key,$fields)) {
					$fields[] = $key;
				}
			}
		}
		
		
		foreach($fields as $field) {
			if(isset($_POST[$field])) {
				$values[$field] = $_POST[$field];
			}
		}
		
		
			//если нужно обновить
		if($form["update"] == 1) {
		
			$model -> id($form["model_id"]);
		
			if($model -> update($values)) {
				echo json_encode([]);
				$form = (new Forms()) -> find("token",$token) -> remove();
			} else {
				echo json_encode($model->error);
			}
			
		}
		
			//если нужно создать
		elseif($form["create"] == 1) {
			
			if($model -> insert($values)) {
				echo json_encode([]);
				$form = (new Forms()) -> find("token",$token) -> remove();
			} else {
				echo json_encode($model->error);
			}
			
			
		}
		
		
		
		/*

		
		else if($_POST["form_action"] == "update") {
			if($form["update"] != 1) {
				echo json_encode(["Изменение запрещено"]);
			} else {	
				
					//модель, которую изменяем
				$model = new $form["model"]();
				
					//находим id, который изменяем	
				$model = $model -> id($form["model_id"]);
				
				$fields = explode(",", $form["fields"]);
				$values = [];
				
				foreach($fields as $field) {
					
					if(isset($_POST[$field])) {
						$values[$field] = $_POST[$field];
					} else {
						$values[$field] = "";
					}	
				}
				if($model -> update($values)) {
					echo json_encode([]);
					$form = (new Forms()) -> find("token",$token) -> remove();
				} else {
					echo json_encode($model->error);
				}
				
			}
		}
		else if($_POST["form_action"] == "create") {
			
			
			if($form["create"] != 1) {
				$error[] = "Создание запрещено";
			} else {
				
				$model = new $form["model"]();
				
				$fields = explode(",", $form["fields"]);
				
				$values = [];
				
				//значения по умолчанию
				if(!empty($form["default"])) {
					$def = json_decode($form["default"],true);
					foreach ($def as $key => $value) {
						$_POST[$key] = $value;
						if(!in_array($key,$fields)) {
							$fields[] = $key;
						}
					}

				}

				//перебор полей, разрешенных
				foreach($fields as $field) {
					if(isset($_POST[$field])) {
						$values[$field] = $_POST[$field];
					} else {
						$values[$field] = "";
					}
				}
				
				
				if($model -> insert($values)) {
					$form = (new Forms()) -> find("token",$token) -> remove();
					echo json_encode([]);
					
				} else {
					echo json_encode($model->error);
				}
			}
		}
		
		else if($_POST["form_action"] == "login") {
			if($form["login"] != 1) {
				$error[] = "Вход запрещен";
			}
			else {
				
				$logins = Logins::init();
				$logval = [
					"ip"    => $_SERVER["REMOTE_ADDR"],
					"agent" => $_SERVER["HTTP_USER_AGENT"],
					"login" => date("Y-m-d H:i:s"),
					"success" => 0,
					"comment" => "",
				];
				
				
				$model = new $form["model"]();
				$fields = explode(",", $form["fields"]);
				
				foreach($fields as $field) {
					
					if(isset($_POST[$field])) {
						$value = $_POST[$field];
					} else {
						$value = "";
					}
					$model -> find($field,$value);
					
					if( empty($logval["comment"]) ) {
						$logval["comment"] = $value;
					}
					
				}
				
					//удаляем токен
				$form = (new Forms()) -> find("token",$token) -> remove();
				
					//в случае успешной автоизации
				if($model->check()) {
					
					$user = $model -> one("id","groups");

					if(isset($user["id"])) {
						
						$token	= md5(uniqid()); 					//уникальный номер сессии
						$time	= $_SERVER["REQUEST_TIME"] + 3600*24*30;//время жизни сессии (месяц)
						$ip		= $_SERVER["REMOTE_ADDR"];			//ip адресс
						$agent	= $_SERVER["HTTP_USER_AGENT"];		//браузер
						$access	= Access::to($user["id"],$user["groups"]);//права для данного пользователя
						
							//если это админ
						if($user["id"] == 1) { $dev = true; } else { $dev = false; }
						
						$json = json_encode([
							"user"  => $user["id"],
							"token" => $token,
							"time"  => $time,
							"ip"	   => $ip,
							"agent" => $agent,
							"access"=> $access,
							"dev"   => $dev,
						],JSON_UNESCAPED_UNICODE);
						
							//создаем файл
						$file = DIR.'/assets/tmp/access/'.File::subdirs($token.".json");
						File::write($file,$json);
						
							//устанавливаем куку
						setcookie("fw_access_token", $token, time()+(24*3600), '/');
						
							//если этот пользователь уже есть в сети - выкидываем
						$onlineUsers = $logins -> clear() -> find("user",$user["id"]) -> not("status",0) -> get("token");
						foreach($onlineUsers as $ou) {
							Access::logout($ou["token"]);
						}
						
							//записываем успешный вход
						$logval["token"]   = $token;
						$logval["success"] = 1;
						$logval["status"]  = 1;
						$logval["user"]    = $user["id"];
						$logval["active"]  = $logval["login"];
						$logval["comment"] = "";
						
						
						$logins -> insert($logval);
						
						echo json_encode([]);
					}
					
				} else {
					
					$logins -> insert($logval);
					
					echo json_encode([
						["messege" => "Неверное имя пользователя или пароль"]
					]);
				}
				
			}
		}
		
		
*/
	}

		//добавить файл
	public static function addfile() {
		
		
		foreach($_FILES as $token => $file) {
			
			
				//смотрим форму
			$form = Forms::init() -> find("token",$token);
			$f = $form -> one("files","model");
			
				//директория хранения файлов
			$dir = self::getDirForModel($f["model"]);
			
			//error_log($dir);
			
				//получаем все старые файлы
			$files = explode("\n", $f["files"]);
			$files = array_diff($files, [""]);
				
			
				//делаем имя для нового файла
			$name = md5(uniqid()).".".strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
			$name = substr($name, 0, 2)."/".substr($name, 2, 2)."/".substr($name, 4);
			Assets::mkdir($dir."/".$name);
			
			$name = str_replace(".jpeg", ".jpg", $name);
			
			
				//если фото более чем 1000px -> уменьшаем его до 1000 px
			if(strpos($name, ".jpg") || strpos($name, ".png")) {
				
				//error_log(print_r($file,true));
				
				$info = getimagesize($file['tmp_name']);
				
				if($info[0] > 1500) {
					Assets::resize($file["tmp_name"],1500);
				}
			}
			
			
				//добавляем новый файл ко всем остальным
			$files[] = $name;
			$files = implode("\n", $files);
			$form -> update("files",$files);
			
			move_uploaded_file($file['tmp_name'], $dir."/".$name);
		}		
	}

		//получить файл
	public static function getfiles() {
		
			//директория для хранения файлов
		//$dir = dirname(dirname(dirname(__DIR__)))."/app/assets/files/";	
		
			//получаем форму
		$form = Forms::init() -> find("token",$_POST["token"]) -> one("files","model");
		
			//директория хранения файлов
		$dir = self::getDirForModel($form["model"]);
		
			//работаем с файлами
		$files = $form["files"];
		if(empty($files)) { echo json_encode([]); return false; }
		$files = explode("\n", $files);
		$res = [];
		foreach($files as $file) {
			//error_log($dir."/".$file);
			
			$res[] = [
				"name"  => $file,
				"img"   => Assets::img($dir."/".$file,[120,0]),
				'crypt' => Cryptor::encrypt($dir.'/'.$file),
			];
		}
		
		echo json_encode($res);
		
	}	
	
	
		//получить директорию для хранения фалов модели
	public static function getDirForModel($model) {
		$model = explode("\\", $model);
		$model = $model[sizeof($model)-1];
		$dir = DIR."/assets/models/".$model;
		if(!file_exists($dir)) { mkdir($dir); }
		return $dir;
	}
	
		//поля по умолчанию
	public function def() {
		$args = func_get_args();
		$res = [];
		if(isset($args[0]) && isset($args[1]) && !is_array($args[0]) && !is_array($args[1])) {
			$res[$args[0]] = $args[1];
		}
		else {
			foreach($args as $arg) {
				if(is_array($arg)) {
					foreach ($arg as $key => $val) {
						$res[$key] = $val;
					}
				}
			}
		
		}
		$this->config["default"] = json_encode($res,JSON_UNESCAPED_UNICODE);
		return $this;
	}
	
		//перезагружать эту страницу при успешном сохранении
	public function reload($page=null) {
		if(!isset($page)) { $page = " "; }
		$this -> config["reload"] = 'fw-reload="'.$page.'"';
		return $this;
	}
	
	public function get($rule="cru") {
		
		
		Assets::add('admin');
		
		if(strpos($rule, "c") !== false) { $this->create = true; }
		if(strpos($rule, "r") !== false) { $this->read   = true; }
		if(strpos($rule, "u") !== false) { $this->update = true; }
		if(strpos($rule, "d") !== false) { $this->delete = true; }
		
		
			//массив со всеми результатами
		$array = [];
			
			//параметры
		$config = &$this->config;
		
			//если id не передан и тип - crud, то изменяем тип на insert
		if($config["id"] == null && $config["type"] == "crud") { $config["type"] = "create"; }
		
			//если передан id => получаем старые значения
		if($config["id"] != null && $config["type"] != "create" && $config["type"] != "login") {
			
			$values = $this->model->id($config["id"]) -> one();
			if(empty($values)) {
				echo '<p class="text-danger">Не найдено!</p>';
				return false;
			}
			
		}
		
			//если нужно копировать - то получаем значения
		if($config["copy"] != null) {
			$values = $this->model->id($config["copy"]) -> one();
			if(empty($values)) {
				echo '<p class="text-danger">Не найдено!</p>';
				return false;
			}
		}
		
			//формируем токен
		$token = $token = md5(uniqid(""));
		
			//активные поля формы
		$ff = [];
		
		$real_fields = [];
		
			//цикл по полям
		foreach($this->model->fields as $field => $param) {

				//убираем лишние поля
			if($field    == "id") { continue; }
			if($field{0} == "_" ) { continue; }
			if($param["form"] === false) {continue;}
				//если указаны поля - показываем только эти поля
			if(!empty($config["fields"])) {
				if(!in_array($field, $this->config["fields"])) { continue; }
			} else {
				$real_fields[] = $field;
			}
			
				//если в модели указано что это поле - не для формы - убираем
			if($param["form"] == false) { continue; }
			
				//размер
			if(isset($param["size"])) {
				if(is_array($param["size"])) { $max = $param["size"][1]; $min = $param["size"][0]; }
				else { $max = $param["size"]; $min = 0; }
			} else {
				$min = 0; $max = 255;
			}
			
				//если не указан тип
			if(isset($param["relation"])) { $param["type"] = "select"; } 
			
				//значение, которое уже было
			if(isset($values[$field])) { $value = $values[$field]; } else { $value = ""; }
			
				//валидация
			if(isset($param["regexp"])) {
				$validators = [];
				foreach($param["regexp"] as $reg) {
					$validators[] = $this->model->validators[$reg];
				}
			}
			
				//для входа
			if($config["type"] == "login") { $validators = []; $min = 0; $max = 255; } $validators = json_encode($validators);
			
				//disabled
			if($config["type"] == "delete") {
				$disabled = 'disabled';
			}
			elseif(!isset($param["change"])) {
				$disabled = "";
			}
			elseif($config["type"] == "create") {
				$disabled = "";
			}
			elseif($param["change"] == false) {
				$disabled = 'disabled';
			} 
			else {
				$disabled = "";
			}
			
			/* ТИПЫ */
			
			$array[$field] = [
				"field" => null,
				"title" => null,
				"value" => null,
				"type"  => $param["type"],
			];
			
			$elem = null;	//элемент
			$attr = [];	//атрибуты
			
			
			
				//число, строка
			if($param["type"] == "int" || $param["type"] == "varchar" || $param["type"] == "float") {
				$value = str_replace('"', '', $value);
				$array[$field]['field'] = '<input '.$disabled.' max="'.$max.'" min="'.$min.'" id="'.$token.$field.'" name="'.$field.'" form="'.$token.'" type="text" class="form-control" value="'.$value.'">';
			}
			
				//цвет
			elseif($param["type"] == "color") {
				$array[$field]['field'] = '<input '.$disabled.' max="'.$max.'" id="'.$token.$field.'" name="'.$field.'" type="color" class="form-control" form="'.$token.'" value="'.$value.'">';
			}
			
				//дата
			elseif($param["type"] == "date") {
				$array[$field]['field'] = '<input '.$disabled.' id="'.$token.$field.'" name="'.$field.'" form="'.$token.'" type="date" class="form-control " value="'.$value.'">';
			}
			
				//текстовое поле
			elseif($param["type"] == "text") {
				$array[$field]['field'] = '<textarea form="'.$token.'" rows="3"  '.$disabled.' max="'.$max.'" min="'.$min.'" id="'.$token.$field.'" name="'.$field.'" class="form-control" >'.$value.'</textarea>';
			}
			
				//да или нет
			elseif($param["type"] == "bool") {
				Assets::add("bootstrap-toggle");
				$array[$field]['field'] = '<input '.$disabled.' id="'.$token.$field.'" name="'.$field.'" form="'.$token.'" type="checkbox" class="form-control"';
				if($value == 1) { $array[$field]['field'] .= " checked ";}
				$array[$field]['field'] .= ' data-toggle="toggle">';
			}
			
				//html (визуальный редактор)
			elseif($param["type"] == "html") {
				Assets::add("summernote");
				$array[$field]['field'] = '<textarea form="'.$token.'" style="display:none"  '.$disabled.' max="'.$max.'" min="'.$min.'" id="'.$token.$field.'" name="'.$field.'" class="form-control" >'.$value.'</textarea>';
				$array[$field]['field'].= '<div class="fw-form-html" data-for="#'.$token.$field.'" >'.$value.'</div>';
			}
			
				//пароль
			elseif($param["type"] == "password") {
				$array[$field]['field'] = '<input form="'.$token.'" placeholder="Введите пароль" '.$disabled.' max="'.$max.'" min="'.$min.'" id="'.$token.$field.'" name="'.$field.'" type="password" class="form-control" value="">';
				if($config["type"] != "login") {
					$array[$field]['field'].= '<input placeholder="Повторите пароль" '.$disabled.' name="'.$field.'_repeat" type="password" class="form-control" value="">';
				}
			}
			
				//теги
			elseif($param["type"] == "tags") {
				Assets::add("bootstrap-tagsinput");
				$attr = [
					"name" => $field."[]",
					"id"	  => "'.$token.$field.'",
					"min"  => $min,
					"max"  => $max,
					"multiple" => "true",
					"class" => "fw-form-tags",
					"form" => $token,
				];
				$options = [];
				if(!empty($value)) {
					foreach($value as $val) {
						$options[$val] = $val;
					}
				}
				
				
				$array[$field]['field'] = html::select($attr,$options,$value);
			}
			
				//иконка
			elseif($param["type"] == "icon") {
				
				$icons = Icons::get();
				
				$array[$field]['field'] = '<select form="'.$token.'" '.$disabled.' max="1" min="0" id="'.$token.$field.'" name="'.$field.'" data-live-search=true class="form-control selectpicker">';
					$array[$field]['field'].= '<option value=""> - не выбрано - </option>';
					foreach($icons as $icon) {
						if($value == str_replace("fa-","",$icon)) {
							$array[$field]['field'].= '<option data-icon="fa '.$icon.'" selected>&nbsp;&nbsp;&nbsp;'.str_replace("fa-","",$icon).'</option>';
						} else {
							$array[$field]['field'].= '<option data-icon="fa '.$icon.'">&nbsp;&nbsp;&nbsp;'.str_replace("fa-","",$icon).'</option>';	
						}
					}	
				$array[$field]['field'].= '</select>';
				

			}
			
				//выбор из другой таблицы
			else if($param["type"] == "select") {

				$model = Model::getClass($param["relation"]["model"]);
				$options = $model -> arr($param["relation"]["index"],$param["relation"]["title"]);
				
				//$value = explode(",", $value);
				
				$attr = [];
				$attr["name"] 	= $field;
				$attr["class"] = "form-control selectpicker";
				$attr["id"]	= "'.$token.$field.'";
				$attr["min"] = $min;
				$attr["max"] = $max;
				$attr['form'] = $token;
				if(!empty($disabled)) { $attr["disabled"] = "disabled"; }
				
				if($max != 1) {
					$attr["multiple"] = "true";
					$attr["name"] = $field.'[]';
					$attr["data"]["selected-text-format"] = "count";
				}
				if(sizeof($options) >= 10) {
					$attr["data"]["live-search"] = "true";
				}
				if($min == 0 && $max == 1) {
					$options = array_merge([0 => " - не выбрано - "],$options);
				}
				$array[$field]['field'].= html::select($attr,$options,$value);
			}
			
				//файлы
			else if($param["type"] == "files") {
				
				Assets::add("dropzone");
				
				if(!empty($value)) {
					if(!is_array($value)) { $value = [$value]; }
					foreach($value as $vvv => $vv) {
						$vv = explode("/", $vv);
						$value[$vvv] = $vv[sizeof($vv)-3]."/".$vv[sizeof($vv)-2]."/".$vv[sizeof($vv)-1];
					}
					
					$value = implode("\n", $value);
					
					$config["files"] = $value;
					
				} else {
					$config["files"] = "";
				}
				
				if(isset($param["ratio"])) {
					$config["ratio"] = $param["ratio"];
				}
				
				
				$array[$field]['field'] .= '<div class="fw-form-files" data-name="'.$field.'" form="'.$token.'">
						<div class="files"></div>
						<div class="dropzone">
							<div class="border">
								<i class="fa fa-plus-circle"></i>
							</div>
						</div>
						<div class="fw-form-files-get" style="display:none"></div>
					</div>';
				
			}
			
			
				//добавялем разметку для ошибок
			$array[$field]['field'] = '<div class="fw-field" data-validators=\''.$validators.'\' data-form="'.$token.'" data-name="'.$field.'">'.$array[$field]['field'].'<div class="fw-error">Необходимо от 1 до 70 символов</div></div>';
			
			
			
				//заголовок
			if(!empty($param["title"])) {
				$array[$field]["title"] = $param["title"];
			}
			
			if(!empty($value)) {
				$array[$field]["value"] = $value;
			}
			
			
			
				//при обновлении поля которые нельзя изменять - убираем
			if($config["type"] == "update" && (!isset($param["change"]) || $param["change"] != false ) ) { $ff[] = $field; } else { $ff[] = $field; }
			
			
			
		}
		
		if(!empty($real_fields)) { $this->config["fields"] = $real_fields; }
		
		
			//создаем форму в таблице
		
		if($config["id"] != null) { $id = $config["id"]; } else {$id = null;}
		
		
		$values["token"] 	= $token;
		$values["model"] 	= str_replace("\\", "\\\\", $this->model->class);
		$values["fields"]	= implode(",", $ff);
		$values["model_id"] = $id;
		$values["files"]    = $config["files"];
		$values["ratio"]    = $config["ratio"];
		$values["default"]  = $config["default"];
	
		
		
		if($this->update == true) {
			if(!empty($this->config["id"])) {
				$values["update"] = 1;
			}
		}
		if($this->create == true) {
			if(empty($this->config["id"])) {
				$values["create"] = 1;
				$this->delete = false;
			}
		}
		
		if($this->delete == true) {
			$values["delete"] = 1;
		}
		
		Forms::init() -> insert($values);
		
		$this -> array = $array;
		$this -> config["token"] = $token;
		
		return $this;
			
	}
	
	
	
	public $array;
	public $btns;
	
	
	public $values = [];
	
	public $create = false;
	public $update = false;
	public $delete = false;
	public $read   = false;
	
	
	
	
	public function field($name, $attr=null) {
		return $this->array[$name]["field"];
	}
	
	public function title($name) {
		return $this->array[$name]["title"];
	}
	
	public function value($name) {
		return $this->array[$name]["value"];
	}
	

	public function btnSave($text="Сохранить",$attr=['class' => 'btn btn-primary']) {
		
			//формируем кнопку
		$dop = ''; foreach($attr as $a => $v) { $dop .= ' '.$a.'="'.$v.'"'; }
		$btn = '<button'.$dop.'>'.$text.'</button>';
			//формируем форму
		$form = '<form id="'.$this->config["token"].'" class="fw-form" '.$this->config["reload"].'>
				<div class="fw-field" data-form="'.$this->config['token'].'" data-name="form-full-errors">
					'.$btn.'
					<div class="fw-error"></div>
				</div>
			   </form>';
		return $form;
	}
	
	public function btnDelete($text="Удалить",$attr=['class' => 'btn btn-danger']) {
		
		if($this->delete == false) { return '';}
		
		$dop = ''; foreach($attr as $a => $v) { $dop .= ' '.$a.'="'.$v.'"'; }
		$btn = '<button'.$dop.' fw-form-delete="'.$this->config["token"].'">'.$text.'</button>';
		return $btn;
	}
	
	
	
	
	
		//какие методы открыты через /class/form
	public static $open = [ 'save', 'delete', 'getfiles' ];
		

}

?>