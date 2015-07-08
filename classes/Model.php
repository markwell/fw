<?
namespace fw\classes;

use fw\classes\Db;
use fw\classes\Cache;
use fw\classes\Form;
use fw\models\Caches;
use fw\models\Tables;

class Model {
	
	
	public $table;			//название текущей таблицы
	public $model;			//название текущей Модели
	public $class;			//абсолютное название класса
	public $name;			//название текущего класса (без namespace)
	public $file;			//абсолютное название файла
	public $fields;		//поля таблицы/модели
	public $register = false;//будет ли вестись регистр
	public $error = [];		//тут хранятся ошибки
	public $query;			//тут храниться последний запрос
	public $trigger = true;  //указывает, что при изменеияъ в модели нужно делать триггеры
	
		//составляющие запроса
	public $sql = [
		'id'    => [],
		'where' => [],		//условия запроса
		'fields'=> [],		//поля запроса
		'limit' => [],		//лимит
		'order' => [],		//сортировка
		'group' => [],		//группировка
		'index' => null,	//ключ результирующего массива
		'relations' => [],	//связи
		'array',			//
		'check' => false,	//
		'onlyarray' => false,//получить только массив в результате
		'files' => [],		//указывает какие поля - это файлы
		'cache' => ["time" => 0, "models" => []],
		'tags'  => [],		//поля, которые нужно распарсить как теги
	];
	
	public $affected_rows;		//кол-во затронутых строк
	public $primary   = 'id';	//ключ таблицы
	public $separator = 'AND';	//разделитель
	
	protected $relations = [];	//связи с другими таблицами
	
	
	
	function __construct($id=null) {
		
		
		$this -> clear = $this -> sql;
		
		
			//добавляем поля по умолчанию
		$this -> fields["_create"] = ['title' => "Дата создания",'type' => "int"];
		$this -> fields["_update"] = ['title' => "Дата обновления",'type' => "int"];
		$this -> fields["_delete"] = ['title' => "Дата удаления",'type' => "int"];
		$this -> fields["_change"] = ['title' => "Дата изменения",'type' => "int"];
		
			//запоминаем название текущего класса
		$this -> class = "\\".get_called_class();
		$this -> name  = explode("\\", $this -> class);
		$this -> name = $this->name[sizeof($this->name)-1];
		
			//запоминаем путь до текущего файла
		$this -> file = (new \ReflectionClass(get_called_class())) -> getFileName();
		
			//Подробно расписываем каждое поле
		$this -> fieldsMore();
		
			//определяем имя таблицы
		if(!isset($this->table)) {
			
			$model = explode("\\", $this -> class);
			$model = array_values(array_diff($model, [""]));
			$table = array_pop($model);
			
			$lenght = strlen($table);
			
			for($i=0; $i < $lenght; $i++) {
				if($table{$i} == ucfirst($table{$i}) && $i != 0) {
					$this->table .= "_";
				}
				$this->table .=lcfirst($table{$i});
			}
			
			if($model[0] != APP) {
				$this->table = "_".$this->table;
			}
			
		}
		
			//если был передан id - сохраняем его
		if(isset($id)) { $this -> id($id); }
		
		
	}
	//возвращеает новый объект класса
	public static function init($id=null) {
		$class = get_called_class();
		
		$class = new $class($id);
		
		return $class;
	}

		// СБОРКА УСЛОВИЙ ЗАПРОСА
	

		//собирает условие запроса
	public function where($x) {
		
		
			//если не массив - возращаем
		if(empty($x)) 		{ return $this;}
		if(!is_array($x)) 	{ return $this;}
		
		$where = &$this->sql["where"];
		$sql   = [];
			
		foreach($x as $field => $val) {
		
				//если передана строка или число - преобразуем в массив
			if(is_string($val) || is_numeric($val)) { $val = [$val]; }
			
			foreach($val as $sign => $one) {
				if(is_numeric($sign)) { $sign = "="; }
				if(!is_array($one)) { $one = [$one];}
				
				if($sign == "between") {
					
					$sql[] = "`".$field."` ".$sign." '".$one[0]."' AND '".$one[1]."' ";
					
				} else {
				
					foreach($one as $o) {
						
							//пароль
						if($this->fields[$field]["type"] == "password") {
							$sql[] = "`".$field."` ".$sign."PASSWORD('".md5($o).$this->getSecret()."')";
						} 
							//зашифрованное поле
						elseif($this -> fields[$field]["encrypt"] == true) {
							$sql[] = "AES_DECRYPT(`".$this->table."`.`".$field."`, '".$this->getSecret()."') ".$sign." '".$o."' ";	 // "AES_DECRYPT(`".$this->table."`.`".$field."`, '".$this->getSecret()."') ".$sign." '".$o."' ";
						}
							//теги
						elseif($this -> fields[$field]["type"] == "tags" && $sign == "=") {
							if(!empty($o)) {
								$sql[] = "`".$field."` LIKE '%#".$o."' OR `".$field."` LIKE '%#".$o.",%'";
							} else {
								$sql[] = "`".$field."` != '' ";
							}
						}
						elseif($this -> fields[$field]["type"] == "tags" && $sign == "!=") {
							if(!empty($o)) {
								$sql[] = "`".$field."` NOT LIKE '%#".$o."' AND `".$field."` NOT LIKE '%#".$o.",%'";
							} else {
								$sql[] = "`".$field."` != '' ";
							}
						}
							//многие связи
						elseif(isset($this->fields[$field]["relation"]) && ($this->fields[$field]["size"][1] > 1) && $sign == "=") {
							if(!empty($o)) {
								$sql[] = "`".$field."` LIKE '%#".$o."' OR `".$field."` LIKE '%#".$o.",%'";
							} else {
								$sql[] = "`".$field."` != ''";
							}
						}
						elseif(isset($this->fields[$field]["relation"]) && ($this->fields[$field]["size"][1] > 1) && $sign == "!=") {
							if(!empty($o)) {
								$sql[] = "`".$field."` NOT LIKE '%#".$o."' AND `".$field."` NOT LIKE '%#".$o.",%'";
							} else {
								$sql[] = "`".$field."` != '' ";
							}
						}
							//обычное поле
						else {
							$sql[] = "`".$field."` ".$sign." '".$o."'";
						}
						
					}
				}
			}
			
		}
		if(!empty($sql)) {
			$where[] = $sql;
		}
	
		
		return $this;
		
		
		
	}
	
		//легкие условия
	public function find() {
		$x = func_get_args();
		$field = $x[0];
		array_shift($x);
		$this -> where([$field => $x]);
		return $this;
	}
	
	public function filter() {
		$x = func_get_args();
		$field = $x[0];
		array_shift($x);
		$this -> where([$field => $x]);
		return $this;
	}
	
	public function id() {
		$x = func_get_args();
		
		if(sizeof($x) == 1) { $this->sql["id"] = $x[0]; }
		
		$this -> where(["id" => $x]);
		return $this;
	}

	public function not() {
		$x = func_get_args();
		$field = $x[0];
		array_shift($x);
		$this -> where([$field => ["!=" => $x]]);
		return $this;
	}
	
	public function like($x,$y) {
		$x = func_get_args();
		$field = $x[0];
		array_shift($x);
		$this -> where([$field => ["LIKE" => $x]]);
		return $this;
	}
	
	public function notlike($x,$y) {
		$x = func_get_args();
		$field = $x[0];
		array_shift($x);
		$this -> where([$field => ["NOT LIKE" => $x]]);
		return $this;
	}

	public function between($d1,$d2,$field = "date") {
		$this -> where([$field => ["between" => [$d1,$d2] ] ]);
		return $this;
	}
	
	
	
	
	//  СБОРКА ВСЕГО ЗАПРОСА
	
	
		//указывает индекс при составлении массива
	public function index($index) {
		$this -> sql["index"] = $index;
		return $this;
	}

		//делает лимит запроса
	public function limit($x,$y=null) {
		
		if(!isset($y)) { $y = $x; $x = 0; }
		$x = (int) $x;
		$y = (int) $y;

		$this -> sql["limit"] = [$x,$y];
		return $this;
	}
	
		//выполняет группировку запроса
	public function group() {
		
		$array = func_get_args();
		foreach($array as $one) {
				
				//находим таблицу и поле
			$one = $this -> findTable($one);
			
			$this->sql["group"][] = ["table" => $one['table'], "row" => $one["row"]];
		}
		return $this;
	}
	
		//выполняет соритровку запроса
	public function order() {
		$array = func_get_args();
		foreach($array as $one) {
				
				//в какую сторону сортировать
			if(strpos(strtoupper($one), "DESC") !== false) {
				$type = "DESC";
				$one = str_ireplace("desc", "", $one);
			} else {
				$type = "ASC";
				$one = str_ireplace("asc", "", $one);
			}
			$one = trim($one);
			$one = explode(".", $one);
			
			
			if(sizeof($one) == 1) {
				$one = "`".$this->table."`.`".$one[0]."`";
			} else {
				$one = strtoupper($one[1])."(`".$this->table."`.`".$one[0]."`)";
			}
			
			$one = $one.' '.$type;
			
				//сохраняем
			$this->sql["order"][] = $one;
			
		}
		return $this;
	}

		//собирает поля запроса
	public function fields() {
		
		$array = func_get_args();
		
		foreach($array as $one) {
			
			if(!is_array($one)) { $one = [$one]; }
			foreach($one as $field) {
				if(empty($field)) {continue;}
				$field = explode(".", $field);
				
					//если поле не найдено
				if(!isset($this->fields[$field[0]])) {
					error_log("Field '".$field[0]."' not found in model '".$this->class."' ");
				}
				
					//если поле содержит связи
				elseif(isset($this->fields[$field[0]]["relation"])) {
					
					$f = $field[0];
					
					if(isset($field[1])) {
					
						
						if(!in_array($f, $this->sql["relations"])) {
							$this->sql["relations"][] = $f;	
						}
						array_shift($field);
						$field = implode(".", $field);
						if(!isset($this->sql["fields"][$f])) {
							$this->sql["fields"][$f] = [];
						}
						if(!empty($field)) {
							$this->sql["fields"][$f][$field] = $field;
						}
						
					} else {
						$this->sql["fields"][$f] = $f;
						if($this -> fields[$field[0]]["size"][1] != 1) {
							if(!in_array($f, $this->sql["tags"])) {
								$this->sql["tags"][] = $f;
							}
							
						}
					}
					
				}
				
					//если не удается найти связи
				elseif(isset($field[1])) {
					
					if($field[1] == "sum") {
						$this->sql["fields"][$field[0].".".$field[1]] = [];
					}
					
					elseif($field[1] == "max") {
						$this->sql["fields"][$field[0].".".$field[1]] = [];
					}
					
					elseif($field[1] == "min") {
						$this->sql["fields"][$field[0].".".$field[1]] = [];
					}
					
					else {
						error_log("Field '".$field[0]."' don't have relation in model '".$this->model."' ");
					}
				}
				
					//если это файл
				elseif($this->fields[$field[0]]["type"] == "files") {
					if(!in_array($field[0], $this->sql["files"])) {
						$this->sql["files"][] = $field[0];
					}
					$this->sql["fields"][$field[0]] = [];
				}
				
				
					//если это теги
				elseif($this -> fields[$field[0]]["type"] == "tags") {
					if(!in_array($field[0], $this->sql["tags"])) {
						$this->sql["tags"][] = $field[0];
					}
					$this->sql["fields"][$field[0]] = [];
				}
					
					//если это обычное поле
				else {
					$this->sql["fields"][$field[0]] = [];
				}
				
			}
		}
		
		return $this;
	} 
	
	
	
	
	//  ЗАПРОСЫ НА ПОЛУЧЕНИЕ ДАННЫХ
	
	
		//получить результат
	public function get() {
	
		$args = func_get_args();
		
			//если что-то передано
		if(sizeof($args) != 0) {
	
				//если передан лимит
			if(is_numeric($args[0])) {
				if(isset($args[1])) {
					$this -> limit($args[0],$args[1]);
				} else {
					$this -> limit($args[0]);
				}
			}
			
				//если переданы поля
			else {
				$this -> sql["limit"] = [];
				foreach($args as $row) {
					$this -> fields($row);
				}
			}
			
			
		}
		
		return $this -> ifcache();
	}
	
		//получить результат с лимитом 1
	public function one() {

		$this -> limit(1);
		$args = func_get_args();
		$this -> fields($args);
		return $this -> ifcache();
	}
		
		//проверка на существование
	public function check() {
		
		$this -> limit(1);
		$temp = $this -> ifcache();
		if(empty($temp)) {
			return false;
		} else {
			return true;
		}
	}
		
		//получить в виде ассоциативного массива 
	public function arr($id,$val=null) {

			//группируем по id
		//$this->group($id);
		
			//если ассоциативный массив
		if(isset($val)) {
			$arrval = $val;
			$arrid  = $id;
			if(strpos($arrval, ".")) { $arrval = explode(".", $arrval); $arrval = $arrval[0]; }
			if(strpos($arrid,  ".")) { $arrid  = explode(".", $arrid ); $arrid  = $arrid[0];  }
			$this->sql["array"] = [$arrid,$arrval];
			return $this -> fields($id,$val) -> get();
		}
		
			//если обычный массив
		$this->sql["array"] = $id;
		
		return $this -> fields($id) -> get();
	}
	
		//вывести строку запроса в консоль
	public function logQuery() {
		$this -> build("Error");
		return $this;
	}
	
	
	
	
	
	
	
	
	//  СИСТЕМНЫЕ
	
		//выполняет sql запрос
	protected function query($sql) {
		
		return Db::query($sql,$this);
	}
	
		//собирает запрос
	protected function build($messege=null) {
		
				
		if(!isset($messege)) {
			$new = "";
			$tab = "";
		} else {
			$new 	= "\n";
			$tab  	= "\t";
		}
		
		$q = [];
		
		$q[] = "\n\nSELECT";
		
		
		//собираем поля
			$fields = $this->sql["fields"];
			
			if(empty($fields)) {	
				$this -> getFields();
				$fields = $this->sql["fields"];
			}
			$f = [];
			foreach($fields as $field => $arr) {
				if( isset($this->fields[$field]) && $this->fields[$field]["encrypt"] == true) {
					$f[] = "\n\tAES_DECRYPT(`".$this->table."`.`".$field."`, '".$this->getSecret()."') as `".$field."`";
				}
				
				//elseif($this->fields[$field]["type"] == "password") {
				//	
				//}
				
				else {
					
					$fi = explode(".", $field);
					if(sizeof($fi) == 1) {
						$f[] = $new.$tab."`".$this->table."`.`".$field."`";
					} else {
						$f[] = $new.$tab.strtoupper($fi[1])."(`".$this->table."`.`".$fi[0]."`) as `".$fi[0]."`";
					}
				}
			}
			$q[] = implode(",", $f);
		
		
		//индекс
			$index = $this->sql["index"];
			if(!empty($index)) {
				$index = $this->findTable($index);
				$q[] = ",`".$index["table"]."`.`".$index["row"]."` as `index_for_request`";
			}
		
		
		//собираем таблицы
			$q[] = $new.$new."FROM `".$this->table."`";
				
		
			//собираем where
		$where = $this->sql["where"];
		$q[] = "\nWHERE\n\t`".$this -> table."`.`_delete` IS NULL ";
		if(sizeof($where) != 0) { $q[] = $new.$new."AND ("; }
		$w = [];
	
		foreach($where as $one) {
			$w[] = "\n\t(".implode(" OR ", $one).")";
		}
	
		$q[] = implode(" AND ", $w);
		
		if(sizeof($where) != 0) { $q[] = "\n)"; }
		
		
		
		
			
			
		//собираем группировку
			$group = $this->sql["group"];
			if(sizeof($group) != 0) { $q[] = $new.$new."GROUP BY"; }
			$g = [];
			foreach($group as $one) {
				$g[] = $new.$tab."`".$one["table"]."`.`".$one["row"]."`";
			}
			$q[] = implode(",",$g);
		
			
		//собираем сортировку
			$order = $this->sql["order"];
			if(sizeof($order) != 0) { $q[] = $new.$new."ORDER BY"; }
			$o = [];
			foreach($order as $one) {
				$o[] = $new.$tab.$one;
			}
			$q[] = implode(",",$o);
		
		
			//собираем лимит
		$limit = $this->sql["limit"];
		if(!empty($limit)) {
			$q[] = $new.$new."LIMIT ".$new.$tab.$limit[0].",".$limit[1];
		}
		
		$q = implode(" ", $q);
		
		if(!isset($messege)) {
			try {
			 	return $this -> tryQuery($q);
			} catch (Exception $e) {
				$this -> build("Error");
			}
		} else {
			error_log("\n\nERROR in SQL-query \n".$q);
		}
		
		
	}
	
		//пробует выполнить запрос
	protected function tryQuery($q) {
	
		$this -> query = $q;
		$rs = $this->query($q);
		
		$array = [];		
		

		if(empty($rs)) {
			$this -> build("Error");
		} else {
			return $this -> parse($rs);
		}
				
	}
	
		//обрабатывает результат запроса
	protected function parse($rs) {
		
		
		
		if(empty($rs->num_rows)) {
			return [];
		}
		
			//индекс
		$index = $this->sql["index"];
		$array = [];
		
		
			//если нужно что-то расшифровать
		$encrypt = [];
		foreach($this->fields as $field => $param) {
			if(isset($param["encrypt"]) == true) {
				$encrypt[] = $field;
			}
		}
		
			
			//получем массив
		if(empty($index)) {
			while($row = mysqli_fetch_assoc($rs)) {
				$array[] = $row;
			}
		} else {
			while($row = mysqli_fetch_assoc($rs)) {
				$i = $row["index_for_request"];
				unset($row["index_for_request"]);
				$array[$i] = $row;
			}
		}
			
			
			//если используются связи
		foreach($this->sql["relations"] as $relation) {
			foreach($array as $id => $val) {
				$array[$id][$relation] = $this->relation($relation,$val[$relation]);
			}
		}
		
			//если есть теги
		foreach($this -> sql["tags"] as $tag) {
			$temp;
			foreach($array as $id => $val) {
				
				//error_log(print_r($val[$tag],true));
				$temp = explode(",", $val[$tag]);
				foreach($temp as $tempid => $tempval) {
					$temp[$tempid] = substr($tempval, 1);
					if(empty($tempval)) { unset($temp[$tempid]);}
				}
				$array[$id][$tag] = $temp;
			}
		}
			
			
			
		if(!empty($this->sql["files"])) {
			
			$dir = $this->class;
			$dir = explode("\\", $dir);
			$dir = $dir[sizeof($dir)-1];
			$dir = DIR."/assets/models/".$dir;
			
			
				//если файлы
			foreach($this->sql["files"] as $file) {
				
					//для каждого поля с файлами
				foreach($array as $id => $val) {
						
					$val[$file] = explode("\n", $val[$file]);
					$array[$id][$file] = [];
					foreach($val[$file] as $v) {
						if(!empty($v)) {
							$array[$id][$file][] = $dir."/".$v;
						}
					}
					if($this->fields[$file]["size"] == 1) {
						$array[$id][$file] = $array[$id][$file][0];
					}
				}
			}
		}
			
		
			//если лимит = 1
		if(!empty($this->sql["limit"]) && $this->sql["onlyarray"] != true) {
				
				//делаем единственный массив
			if($this->sql["limit"][1] - $this->sql["limit"][0] == 1) {
				$array = $array[0];
			}
				//если нужно взять только одно поле
			if(sizeof($this->sql["fields"]) == 1) {
				foreach($this->sql["fields"] as $id => $one) {
					$id = str_replace(".sum", "", $id);
					$id = str_replace(".max", "", $id);
					$id = str_replace(".min", "", $id);
					$array = $array[$id];
				}
			}
		}
		
		//если нужно поулчить массив
		if(!empty($this->sql["array"])) {
			$arr2 = [];
			
			if(is_array($this -> sql["array"])) {
				foreach($array as $a) {
					$x = $a[$this->sql["array"][0]];
					$y = $a[$this->sql["array"][1]];
					$arr2[$x] = $y;
				}
			} else {
				foreach($array as $a) {
					$arr2[] = $a[$this->sql["array"]];
				}
			}
			
			
			return $arr2;
		}
		
		return $array;
	}
	
		//создает связи
	public function relation($field,$val) {
		if(empty($val)) { return [];}
		if(!isset($this->relations[$field])) {
			$model = $this->fields[$field]["relation"]["model"];
			$model = self::getClass($model);
			
			$result = $model 
				-> index($this->fields[$field]["relation"]["index"])
				-> fields($this->sql["fields"][$field])
				-> get();
			
			$this->relations[$field] = $result;
		}
		
		
		$max = 1;
		if(isset($this->fields[$field]["size"])) {
			if(!is_array($this->fields[$field]["size"])) {
				$max = $this->fields[$field]["size"];
			} else {
				$max = $this->fields[$field]["size"][1];
			}
		}
		
		
		
		if($max == 1) {
			if(isset($this->relations[$field][$val])) {
				return $this->relations[$field][$val];
			} else {
				return [];
			}
		} else {
			$val = explode(",", $val);
			$arr = [];
			foreach($val as $v) {
				$v = substr($v, 1);
				$v = (int) $v;
				$arr[] = $this->relations[$field][$v];
			}
			return $arr;
		}
		
		
	}

		//нахдим таблицу и поле
	public function findTable($one) {
		if(strpos($one, ".")!==false) {
			$one = explode('.',$one); 
			$table = $one[0]; 
			$one = $one[1];
		} else {
			$table = $this->table;
		}
		return ["table" => $table, "row" => $one];
	}

		//получаем все поля таблицы
	public function getFields() {
		$fields = [];
		foreach($this->fields as $field => $param) {  $fields[] = $field; } 
		$this -> fields($fields);
	}
	
		//проверка на кеширование
	public function ifcache() {
		
			//сначала проверем на кеширование
		return Cache::init([
			"token"  => $this -> sql,
			"time"   => $this->sql["cache"]["time"],
			"models" => $this->sql["cache"]["models"],
			"vars"   => [ "model" => $this],
			"comment"=> str_replace("\\", "/", $this->class),
			"function" => function($vars) {			
				return $vars["model"] -> build();
			}
		]);
	}
	
		//при изменениях
	public function trigger() {
		
		if($this -> trigger === false) { return false; }
				
			//удаляем ненужный кеш
		
		$mod = $this -> class;
		$mod = explode("\\", $mod);
		$mod = array_pop($mod);
		
		if($mod{0} != "_") {
			$caches = Caches::init() -> find("models",$mod);
			
			
				//удаляем файлы
			$files = $caches -> arr("file");
			foreach($files as $file) {
				$file = DIR."/assets/tmp/cache/".$file;
				if(file_exists($file)) { unlink($file); }
			}
			
				//удаляем данные в таблице
			$caches -> remove();
			
		}
		
		
	}
	
		//задает время кеширование запроса
	public function cache() {
		
		$args = func_get_args();
		foreach($args as $arg) {
			if(is_numeric($arg)) {
				$this -> sql["cache"]["time"] = $arg;
			} else {
				$this -> sql["cache"]["models"] = $arg;
			}
		}
		return $this;
	}
	
		//проверяет права пользователей
	public function premission($type) {
		
		return true;
		
		//$prem = App::$config["premission"];
		$prem = "*";
		if(isset($this->premission[$type])) {
			$prem = $this->premission[$type];
		} elseif(isset($this->premission["*"])) {
			$prem = $this->premission["*"];
		}
		
		
		
		return Premission::open($prem);
		
		
	}
			
		//получает секретный ключ
	protected function getSecret() {
		if(empty($this->secretKey)) {
			if(file_exists(DIR."/config/kеy.php")) {
				require(DIR."/config/kеy.php");	
			} else {
				$key = "Base mrKey 005";
			}
			$this->secretKey = md5($key);
			
		}
		return $this->secretKey;
	}
	protected $secretKey;
	
		//регулярные выражения для валидации
	public $validators = [
		
		"email" 	=> [	//электронная почта
			'messege' => "Email введен некорректно",
			'pattern' => "^[-\w.]+@([A-z0-9][-A-z0-9]+\.)+[A-z]{2,4}$",
		],
		"engone" 	=> [	//английские буквы и цифры (одно слово)
			'messege' => "Допустимы только английские буквы и цифры (без пробелов)",
			'pattern' => "^[a-zA-Z0-9]+$",
		],
		"ruone" 	=> [	//русские буквы и цифры (одно слово)
			'messege' => "Допустимы только русские буквы и цифры (без пробелов)",
			'pattern' => "^[а-яА-ЯёЁ0-9]+$",
		],
		"engruone" => [//русские и английские буквы (одно слово)
			'messege' => "Допустимы только русские, английские буквы и цифры (без пробелов)",
			'pattern' => "^[а-яА-ЯёЁa-zA-Z0-9]+$",
		],
		"engru"	=> [	//русские и английские символы
			'messege' => "Допустимы только русские, английские буквы и цифры (без пробелов)",
			'pattern' => "^[а-яА-ЯёЁa-zA-Z0-9]+$",
		],
		"pass"	=> [	//пароль
			'messege' => "Нужна хотя бы 1 латинская буква, хотя бы 1 цифра, минимум 7 символов",
			//'pattern' => "^(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?!.*\s).*$",
			'pattern' => "^(?=.*\d)(?=.*[a-zA-Z])(?!.*\s)(?=^.{7,}$).*$",
		],
		"date"	=> [	//дата в формате DD/MM/YYYY
			'messege' => "Допустима только дата в формате DD/MM/YYYY",
			'pattern' => "[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])",
		],
		"date2"	=> [	//lата в формате YYYY-MM-DD
			'messege' => "Допустима только дата в формате YYYY-MM-DD",
			'pattern' => "(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d",
		],
		"int"	=> [	//целые числа
			'messege' => "Допустимы только целые числа",
			'pattern' => "^[0-9]+$",
		],
		"float"	=> [	//целые и дробные числа
			'messege' => "Допустимы только целые и дробные",
			'pattern' => "\-?\d+(\.\d{0,})?",
		]
		
	];	
	
	
	
	
	
	
	
	
		//валидация (если не прошла - возвращает массив с ошибками) (если прошла - возвращает value)
	public function validate($row,$val) {
		
		$error = [];
		
		$min = $this -> fields[$row]["size"][0];
		$max = $this -> fields[$row]["size"][1];


			//ВАЛИДАЦИЯ на КОЛИЧЕСТВО


			//если есть связи
		if(isset($this->fields[$row]["relation"]) || $this->fields[$row]["type"] == "tags") {
			if(!is_array($val)) { $val = [$val]; }
			if(sizeof($val) > $max || sizeof($val) < $min) {
				if($min == $max) {
					$this -> error[] = ["field" => $row, "messege" => "Нужно выбрать ".$min, "value" => $val];
				} else {
					$this -> error[] = ["field" => $row, "messege" => "Нужно выбрать от ".$min." до ".$max, "value" => $val];
				}
			}
			
			if(isset($this->fields[$row]["relation"]) && $this->fields[$row]["size"][1] == 1) {
				$val = $val[0];
			}
			
		}
			//если это файлы
		elseif($this->fields[$row]["type"] == "files") {
			
			$files = explode(",", $val);
			
			if(sizeof($files) > $max || sizeof($files) < $min) {
				if($min == $max) {
					$this -> error[] = ["field" => $row, "messege" => "Необходимо выбрать только ".$min." файл", "value" => $val];
				} else {
					$this -> error[] = ["field" => $row, "messege" => "Необходимо от ".$min." до ".$max." файлов", "value" => $val];
				}
				
			}
			
				//разрешенные расширения файлов
			if(isset($this->fields[$row]["ext"])) {
				$exts = $this->fields[$row]["ext"];
				if(!is_array($exts)) { $exts = [$exts]; }
			} else {
				$exts = [];
			}
			
			$_i = 0;
			
			$files = array_diff($files, [""]);
			
			foreach($files as $file) {
				$_i++;
				
				//проверяем на расширения
				$ext = pathinfo($file, PATHINFO_EXTENSION);
				if(in_array($ext, $exts) || empty($exts)) {}
				else {
					$this -> error[] = ["field" => $row, "messege" => "Недопустимый тип файла №".$_i];
				}
				
				$dir = explode("\\",$this->class);
				$dir = DIR."/assets/models/".$dir[sizeof($dir)-1];
				
				if(!file_exists($dir."/".$file)) {
					$this -> error[] = ["field" => $row, "messege" => "Что-то пошло не так..."];
				}
							
			}
			
			$val = str_replace(",", "\n", $val);
			
		}
		
			//валидация на минимум/максимум
		elseif($max != 0) {
			
			if(strlen($val) > $max || strlen($val) < $min) {
				if($min == $max) {
					$this -> error[] = ["field" => $row, "messege" => "Необходимо ".$min." символ(ов)", "value" => $val];
				} else {
					$this -> error[] = ["field" => $row, "messege" => "Необходимо от ".$min." до ".$max." символов", "value" => $val];
				}
				
			}
		}
		
		if($this -> fields[$row]["type"] == "float") {
			$val = str_replace(",", ".", $val);
		}
		

		
		
			//валидация на регулярные выражения
		if(!empty($val)) {
			
			$field = $this->fields[$row];
			
			foreach($field["regexp"] as $reg) {
				$pattern = '/'.$this->validators[$reg]["pattern"].'/';
				if(is_array($val)) {
					foreach($val as $v) {
						if(!preg_match($pattern, $v)) {
							$this -> error[] = ["field" => $row, "messege" => $this->validators[$reg]["messege"], "value" => $val];
						}	
					}
				} else {
					if(!preg_match($pattern, $val)) {
						$this -> error[] = ["field" => $row, "messege" => $this->validators[$reg]["messege"], "value" => $val];
					}	
				}
				
			}
		} elseif($min != 0 && $max != 0) {
			$this -> error[] = ["field" => $row, "messege" => "Поле не может быть пустым"];
		}
		
		
			//проверка на уникальность
		if(isset($this->fields[$row]["unique"]) && $this->fields[$row]["unique"] == true) {
			$model = $this -> class;
			if((new $model()) -> find($row,$val) -> check()) {
				$this -> error[] = ["field" => $row, "messege" => "Такое значение уже существует"];
			}
		}
		
		
		
			//если есть связи (ко многим) или это теги 
		if( isset($this->fields[$row]["relation"])  || $this->fields[$row]["type"] == "tags") {
				
			if(is_array($val)) {
				
				foreach($val as $ID => $VAL) {
					if(empty($VAL)) { unset($val[$ID]); }
					else {$val[$ID] = "#".$VAL; }
				}
				$val = implode(",", $val);
				
			}
		
		}
		
			//если это bool
		if($this->fields[$row]["type"] == "bool") {
			
			if($val === "on" || $val === 1  || $val === "1") { $val = 1;  } 
			else {$val = 0;}
			
		}
		
		
			//если нужно зашифровать
		if($this->fields[$row]["encrypt"] == true && !empty($val)) {
			return "AES_ENCRYPT('".$val."','".$this->getSecret()."')";
		}
		
			//если это пароль
		if($this->fields[$row]["type"] == "password") {
			if(empty($val)) {
				$this -> error[] = ["field" => $row, "messege" => "Поле не может быть пустым"];
			}
			return "PASSWORD('".md5($val).$this->getSecret()."')";
		}
		
			//если это html
		if($this->fields[$row]["type"] == "html") {
			$val = str_replace("'", "\'", $val);
		}
		
		
			//если допустип NULL
		if($this -> fields[$row]["null"] == "NULL" && $val === null) {
			return "NULL";	
		}
		
		$val = "'".$val."'";
		return $val;
		
		
	}
	
	
	
	
	
	
	// ОПРЕРАЦИИ ПО ИЗМЕНЕНИЮ ДАННЫХ
	
	
		//очищает все значения
	public function clear() {
		$this -> sql = $this -> clear;
		$this -> error = [];
		return $this;
	}
	
		//псевдо-удаляет из таблицы все что поподает под условие where
	public function del() {
		
		if(!$this->premission("delete")) {
			$this -> error[] = ["messege" => "Недостаточно прав доступа"];
			return false;
		}
		
		
			//получаем старые значения
		if(!empty($this->sql["where"])) {
			
			$where = $this -> sql["where"];
			$this -> clear();
			$this -> sql["where"] = $where;
			
			$olds = $this -> get("id");
			if(empty($olds)) { $olds = false; }
		} else {
			$this -> error[] = ["messege" => "Нечего удалять"];
			return false;
		}
		if(empty($olds)) {
			return false;
		}
			//делаем перебор старых значений
		foreach($olds as $old) {
			
			$query = "UPDATE `".$this-> table."` SET `_delete` = '".time()."', `_change` = '".time()."' WHERE `id` = '".$old["id"]."' ";
			
			$this -> query = $query;
			$this->query($query);
			
		}
		$this -> trigger();
		return true;
		
	}
	
		//удаляет из таблицы насовсем
	public function remove() {
		if(!$this->premission("delete")) {
			$this -> error[] = ["messege" => "Недостаточно прав доступа"];
			return false;
		}
		
			//получаем старые значения
		if(!empty($this->sql["where"])) {
			
			$where = $this -> sql["where"];
			$this -> clear();
			$this -> sql["where"] = $where;
			

			
			$olds = $this -> get("id");
			if(empty($olds)) { $olds = false; }
		} else {
			$this -> error[] = ["messege" => "Нечего удалять"];
			return false;
		}
		
		if(empty($olds)) {
			return false;
		}
		
			//делаем перебор старых значений
		foreach($olds as $old) {
			
			$query = "DELETE FROM `".$this-> table."` WHERE `id` = '".$old["id"]."' ";

			$this -> query = $query;
			$this->query($query);
			
		}
		$this -> trigger();
		return true;
	}
	
		//Обновляет данные
	public function update() {
		
		$sql = $this -> sql;
		
		if(!$this->premission("update")) {
			$this -> error[] = ["messege" => "Недостаточно прав доступа"];
			return false;
		}
		
		$values = [];		//значения
		$insert = false;	//можно ли создавать - если нечего изменять
		$error = [];		//ошибки
		$arg = func_get_args();
		
		
			//если переданы значения как массив
		if(is_array($arg[0])) {
			$values = $arg[0];
			if(isset($arg[1])) { $insert = $arg[1]; }
		}
			//если переданы значения как строки
		elseif(is_string($arg[0]) && isset($arg[1])) {
			$values[$arg[0]] = $arg[1];
			if(isset($arg[2])) { $insert = $arg[2]; }
		}
	
		
			//получаем старые значения
		if(!empty($this->sql["where"])) {
			
			$where = $this -> sql["where"];
			$this -> clear();
			$this -> sql["where"] = $where;
			
			$olds = $this -> get();
			
			
			if(empty($olds)) { $olds = false; }
		} else {
			$olds = false;
		}
		
			//если старых значений нет и можно создавать
		if(!is_array($olds) && $olds == false && $insert == true) {
			if($insert == true) {
				return $this -> insert($values);
			} else {
				$this -> error[] = ["messege" => "Не найдено поле, которое нужно изменить"];
				return false;	
			}
		}
		
		$querys = [];
		
		
			//если нет старых значений
		if(!is_array($olds)) {
			$this -> error[] = ["messege" => "Не найдено полей, которые нужно изменить"];
			return false;
		}
		
		
			//иначе делаем перебор старых значений
		foreach($olds as $old) {
			
			
			
			$update = [];
			
			$vals = $values;
			
				//смотрим какие поля действительно изменяются
			foreach($vals as $field => $val) {
				
				
					//пустой массив
				//if(is_array($val) && sizeof($val) == 1 && empty($val[0])) { $val = []; }
				
					
					//если это пароль и передано пустое значение - пропускаем
				if(empty($val) && $this->fields[$field]["type"] == "password") {
					unset($vals[$field]); 
				} 
					
					//если сторое и новое значения совпадают
				elseif($old[$field] == $val) {
					unset($vals[$field]);
				} 
				elseif($old[$field] == 0 && $val == "") {
					unset($vals[$field]);
				}
				
				
					//если нельзя менять это значение
				elseif($this -> fields[$field]["change"] == false) {
					unset($vals[$field]);
				}
					
				
				
					//если значение действительно изменилось
				else {
			
					
					
					$val = $this -> validate($field,$val);
					
															
						//иначе добавляем поле => значение
					$update[] = "`".$field."` = ".$val;
				}
				
			}
			
			
		
				//если нечего изменять - то пропускаем
			if(empty($update)) { continue; }
		
			$update[] = " `_update` = '".time()."', `_change` = '".time()."' ";
			
			$update = implode(", ", $update);

			$query = "UPDATE `".$this-> table."` SET ".$update." WHERE `id` = '".$old["id"]."' ";
			
			
			
			$querys[] = $query;
			
			
		}
		if(empty($this -> error)) {
			foreach($querys as $query) {
				$this->query($query);
			}
			$this -> trigger();
			$this -> sql = $sql;
			return true;
		} else {
			
			return false;
		}
		
		
		
	}
	
		//дабавляет данные
	public function insert($val) {
		
		
		
		if(!$this->premission("create")) {
			$this -> error[] = ["messege" => "Недостаточно прав доступа"];
			return false;
		}
		
		if(!is_array($val)) { return false; }
		
		
		$f = $v = [];
			
		foreach($this->fields as $field => $param) {
			
			if(!isset($val[$field]) || $field{0} == "_" || $field == "id") { continue; }
			
			$val[$field] = $this -> validate($field,$val[$field]);
			
			$f[] = "`".$field."`";
			$v[] = $val[$field];
		}
		
	
		$f[] = "`_create`";
		$v[] = "'".time()."'";
		
		$f[] = "`_change`";
		$v[] = "'".time()."'";
			
		$f = implode(",", $f);
		$v = implode(",", $v);
	
		$query = "INSERT INTO `".$this->table."` (".$f.") VALUES (".$v.") ";
		
	
		if(empty($this->error)) {
			$this -> query = $query;
			$this -> query($query);
			$this -> trigger();
			return true;
		} else {
			//echo '<pre>';
			//	print_r($this -> error);
			//echo '</pre>';
			return false;
		}
		

		
		
	}
	
	
	
	
	// СОЗДАНИЕ / ИЗМЕНЕНИЕ СТРУКТУРЫ ТАБЛИЦ В БД
	
	
		//создает все дополнительные записи в полях
	protected function fieldsMore() {
		
		
		
		if(!isset($this -> fields["id"])) {
			$this -> fields["id"] = [
				"title" => "Идентификатор",
			];
		}
		
		$id = [ "id" => $this->fields["id"] ];
		unset($this->fields["id"]);
		
		$this->fields = array_merge($id,$this -> fields);
		
			//проходимся по полям 
		foreach($this->fields as $field => $param) {
			
			
				//если это id
			if($field == "id") {
				if(!isset($param["type"])) 		{ $param["type"] = "int";			}
				if(!isset($param["extra"])) 		{ $param["extra"] = "unsigned"; 		} 
				if(!isset($param["increment"])) 	{ $param["default"] = "AUTO_INCREMENT"; }
			}
			
				//если есть связи
			if(isset($param["relation"])) {
				if(!isset($param["size"])) {
					$param["size"] = [0,1];
				} elseif(!is_array($param["size"])) {
					$param["size"] = [0,$param["size"]];
				}
				if($param["size"][1] == 1 && !isset($param["type"])) {
					$param["type"] = "varchar";
				}
			}
			
		
				//Определяем тип
			if(!isset($param["type"])) {
				$param["type"] = "varchar";
			}
			if($param["type"] == "file") { $param["type"] = "files"; }
			
			
				//Определяем размер
			$nosize = ["bool","text","html","blob","float","timestamp","date","time","dt",""];
			if(in_array($param["type"], $nosize)) {
				$param["size"] = [0,0];
			}
			else if(isset($param["size"])) {	//если указан размер - преобразуем всегда в массив (мин - макс)
				if(!is_array($param["size"])) {
					$param["size"] = [0,$param["size"]];
				}
			} else {	//если не указан размер
				if($param["type"] == "int") {
					$param["size"] = [0,11];
				} elseif($param["type"] == "files") {
					$param["size"] = [0,1];
				} else {
					$param["size"] = [0,255];
				}
			}
			
			
			
				//Определяем NULL / NOT NULL
			$notNULL = ["bool","int"];
			$yesNULL = ["timestamp","data","time","dt","varchar","password"];
			
			if(in_array($param["type"], $notNULL)) {
				$param["null"] = "NOT NULL";
			} elseif(in_array($param["type"], $yesNULL)) {
				$param["null"] = "NULL";
			} else {
				$param["null"] = "";
			}
			
			
				//по умолчанию
			if(!isset($param["default"])) {
				if($param["null"] == "NULL") {
					 $param["default"] = "DEFAULT NULL";
				} elseif($param["type"] == "int" || $param["type"] == "float" || $param["type"] == "bool") {
					$param["default"] = "DEFAULT '0'";
				} else {
					$param["default"] = "";
				}
			} elseif($param["default"] != 'AUTO_INCREMENT') {
				$param["default"] = "DEFAULT '".$param["default"]."'";
			}
			
			if($field == "_create" || $field == "_update" || $field == "_delete") {
				$param["null"] 	= "NULL";
				$param["default"] 	= "DEFAULT NULL";
			}
			
			
				//можно ли редактировать при помощи виджета формы
			if(!isset($param["form"])) {
				$param["form"] = true;
			}
			
			
			
				//заголовок
			if(!isset($param["title"])) {
				$param["title"] = "";
			}
			
			
				//регулярные выражения по умолчанию
			if(!isset($param["regexp"])) {
				$param["regexp"] = [];
			}
			elseif(!is_array($param["regexp"])) {
				$param["regexp"] = [$param["regexp"]];
			}
			if($param["type"] == "password") {
				if(!in_array("password", $param["regexp"])) {
					$param["regexp"][] = "pass";
				}
			}
			if($param["type"] == "int") {
				if(!in_array("int", $param["regexp"])) {
					$param["regexp"][] = "int";
				}
			}
			if($param["type"] == "float") {
				if(!in_array("float", $param["regexp"])) {
					$param["regexp"][] = "float";
				}
			}
			
				//можно/нельзя менять
			if(!isset($param["change"])) {
				$param["change"] = true;
			}
			
			
				//шифровка
			if(!isset($param["encrypt"])) {
				$param["encrypt"] = false;
			}
				
				
				//дополнительные параметры
			if(!isset($param["extra"])) {
				$param["extra"] = "";
			}
			
			
			$this->fields[$field] = $param;
			
		}
		
		
	}
	
	public function createTable() {
		
		$sql = $this -> fieldsForChanges();
	
		$str = "\nCREATE TABLE IF NOT EXISTS `".$this->table."` ( \n\t".implode(",\n\t", $sql). ",\n\tPRIMARY KEY (`id`)\n) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT '".filemtime($this -> file)."'";

		Db::quickQuery($str);
	}
	
	public function changeTable() {
		
		$sql = $this -> fieldsForChanges();
				
		$str = "SHOW COLUMNS FROM ".$this->table;
		$colums = Db::quickQuery($str);
		
			//старые
		while($row = mysqli_fetch_assoc($colums)) {
			$field = $row["Field"];
			
				//если теперь такого поля нет - удаляем его
			if(!isset($sql[$field])) {
				$str = "ALTER TABLE ".$this->table." DROP COLUMN `".$field."`";
			} else {
				$str = "ALTER TABLE ".$this->table." MODIFY `".$field."` ".str_replace('`'.$field.'`', "", $sql[$field]);
				unset($sql[$field]);
			}
			
			Db::quickQuery($str);
		}
		
			//новые				
		foreach($sql as $field => $f) {
			$str = "ALTER TABLE ".$this->table." ADD ".$sql[$field];
			Db::quickQuery($str);
		}
		
			//сохраняем время
		Db::quickQuery("ALTER TABLE `".$this -> table."` COMMENT = '".filemtime($this->file)."';
");
		
	}
	
	public function fieldsForChanges() {
		$sql = []; foreach($this->fields as $field => $param) {
			
				//размер
			if($param["size"][1] == 0) { $size = "";}
			else {$size = "(".$param["size"][1].")";}
			
				//тип
			if($param["type"] == "html") { 
				$param["type"] = "longtext";
			}
			elseif($param["type"] == "password") {
				$param["type"] = "varchar";
			}
			elseif($param["type"] == "files") {
				$param["type"] = "text";
				$size = "";
			}
			elseif($param["type"] == "dt") {
				$param["type"] = "datetime";
			}
			elseif($param["type"] == "color") {
				$param["type"] = "varchar";
			}
			elseif($param["type"] == "icon") {
				$param["type"] = "varchar";
				$size = "(99)";
			}
			elseif($param["type"] == "tags" || (isset($param["relation"]) && $param["size"][1] > 1 )) {
				$param["type"] = "varchar";
				$size = "(1000)";
			}
			elseif(isset($param["relation"])) {
				if($param["type"] == "varchar") { $size = "(255)"; }
				else { $size = "(11)"; }
			}
		
			if($param["encrypt"] == true) {
				$param["type"] = "blob";
				$param["null"] = "";
				$param["default"] = "";
				$size = "";
			}
					
			$sql[$field] = "`".$field."` ".$param["type"].$size." ".$param["null"]." ".$param["default"]." COMMENT '".$param["title"]."'";
		}
		return $sql;
	}
	
	
	
	
	// СТАТИЧНЫЕ МЕТОДЫ
	
	
	
		//получает путь до файла модели
	public static function get_file($model) {
		

		if($model == str_replace("_", "", $model)) {
			$model = str_replace("_", "", $model);
			return DIR.'/models/'.$model.'.php';
		} else {
			return dirname(dirname(__DIR__)).'/model/'.$model.'.php';
		}
			
	}

		//получает класс нужной модели
	public static function getClass($model) {
		
		
		if(is_string($model)) {
			
			if(!strstr($model,"\\")) {
				if(class_exists("\\".APP.'\\models\\'.$model)) {
					$model = "\\".APP.'\\models\\'.$model;
				}
				elseif(class_exists("\\fw\\models\\".$model)) {
					$model = "\\fw\\models\\".$model;	
				}
			}
			
			$model = new $model();
		}
		
		return $model;
	}
	
	
	
		//ожидает изменения в таблице
	public function wait($time=null, $max=60000) {
		
			//запоминаем параметры текущего запроса
		$sql = $this -> sql;
		
		//error_log("start");
		
		if($time == null) {
			if(!isset($_SERVER["HTTP_X_PULL_TIME"])) {
				return $this;
			} elseif($_SERVER["HTTP_X_PULL_TIME"] == 0) {
				return $this;
			} else {
				$time = $_SERVER["HTTP_X_PULL_TIME"];
			}
		}
		
		
			//добавляем условие
		$this -> where([ "_change" => [">=" => $_SERVER["REQUEST_TIME"]] ]);
		$this -> fields('id');
		
			//время одной итерации
		$step = $time * 1000;
		$max  = floor($max / $time);
		
			//цикл, в котором будем следить за изменениями
		for($i=0; $i<=$max; $i++) {
			
			//error_log("step");
			
				//если действительно были изменения
			if($this->check()) {
				
				//error_log("success");
					//возвращаем старые параметры запроса
				$this -> sql = $sql;
				return $this;
			}
			
			usleep($step);
		}
		
		header("HTTP/1.1 304 Continue");
		exit;
	}
	
	
		//получает форму
	public function form($rule='cru',$reload=false) {
		
		
		
		if(isset($this->sql["id"])) {
			$form = Form::init($this,$this->sql["id"]);
		} else {
			$form = Form::init($this);
		}
		
		if($reload === false) {}
		elseif($reload===true) { $form->reload();}
		else { $form-> reload($reload); }
		
		if(!empty($this->sql["fields"])) {
			$ff = [];
			foreach($this->sql["fields"] as $f => $i) { $ff[] = $f; }
			$form->fields($ff);
		}
		
		return $form->get($rule);
	
	}
	
	
	
}