<?
namespace fw\classes;

class Db {
	
		//тут хранится подключение к базе данных
	public static $db = '';
	
	public static $tables = [];

		//выполняет запрос к базе данных
	public static function query($sql,$model=null) {
		
		
		//echo '<pre>';print_r($sql);echo '</pre>';
		
				
		self::connect();
		
			//проверяем существование таблицы
		if(isset($model)) {
			if(DEV == true) {
				if(isset(self::$tables[$model->table])) {
					$filemtime = filemtime($model->file);
					if(self::$tables[$model->table] != $filemtime) {
						$model -> changeTable();
						self::$tables[$model->table] = $filemtime;
					}
				} else {
					$model -> createTable();
					$filemtime = filemtime($model->file);
					self::$tables[$model->table] = $filemtime;
				}
			}
		}
		
		return self::$db -> query($sql);
	}
	
	
	
	public $affected_rows;		//кол-во затронутых строк
	
		//подключается к базе данных, если подключение не создано
	public static function connect() {

		if(self::$db == '') {
			
 			/*	Устанавливаем соединения с базой данных	*/
			require(DIR."/config/db.php");
			
			$host = $db["host"];
			$name = $db["name"];	
			
				//если действие происходит на локальной машине
			if(strstr($_SERVER["SERVER_NAME"], ".loc") || strstr($_SERVER["SERVER_NAME"], ".xn--j1abj") || strstr($_SERVER["SERVER_NAME"], "localhost")) {
				$user = "root";
				$pass = "root";
			} else {
				$user = $db["user"];
				$pass = $db["pass"];
			}	
			self::$db = mysqli_connect($host,$user,$pass,$name);
			self::$db -> query("SET CHARACTER SET utf8");
			
			
				/*	Получаем список таблиц	*/
			if(DEV == true) {
				$query = self::$db -> query("SHOW TABLE STATUS FROM `".$name."`");
				while($row = mysqli_fetch_assoc($query)) {
					self::$tables[$row["Name"]] = $row["Comment"];
				
					//self::quickQuery("ALTER TABLE `".$row["Name"]."` ADD `_change`  INT  NULL  DEFAULT NULL  COMMENT 'Дата изменения'");
					
					//self::quickQuery("ALTER TABLE `".$row["Name"]."` CHANGE `_update` `_update` INT  NULL  DEFAULT NULL  COMMENT 'Дата изменеия'");
					//self::quickQuery("ALTER TABLE `".$row["Name"]."` CHANGE `_create` `_create` INT  NULL  DEFAULT NULL  COMMENT 'Дата создания'");
					//self::quickQuery("ALTER TABLE `".$row["Name"]."` CHANGE `_delete` `_delete` INT  NULL  DEFAULT NULL  COMMENT 'Дата удаления'");
					
					//self::quickQuery("UPDATE `".$row["Name"]."` SET `_change` = '".time()."', `_create` = '".time()."', `_update` = NULL, `_delete` = NULL ");
					
					
				}
			}
		}
	}
	
	
	public static function quickQuery($sql) {
		//error_log($sql);
		return self::$db -> query($sql);	
	}
		
	
	
}