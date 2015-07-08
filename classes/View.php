<?
namespace fw\classes;

use fw\classes\Assets;
use fw\classes\File;
use fw\classes\Cache;


class View {
	
	public static $head;
	public static $foot;
	
	
	private $controller;
	
	private $config = [
		"content"  => "",	//содержимое
		"template" => "",	//шаблон
		"template" => [
			"head" => "",
			"foot" => "",
		],
		"meta" 	=> [],	//дополнительные мета-теги
		"dir"	=> "",	//директория view
		"web"	=> "",	//директория web
		"icon"	=> "",
		"favicon" => "",
		"offline" => false,
		"style"   => [],
		"livereload" => [],
	];
	
	
	private $description;
	
	private $title; 		//заголовок html страницы
	private $type = "html";	//тип документа
	private $content;		//содержимое
	private $icon;			//иконка (favicon)
	
	
	function __construct($dir,$vars,$controller) {
		
		$view_app = DIR.'/views/'.$dir;
		$view_fw  = FW .'/views/'.$dir;
		
		if(file_exists($view_app.".php")) {
			$file_view_name = $view_app.".php";
		}
		elseif(file_exists($view_app.'/index.php')) {
			$file_view_name = $view_app."/index.php";
		}
		elseif(file_exists($view_fw.".php")) {
			$file_view_name = $view_fw.".php";
		}
		elseif(file_exists($view_fw.'/index.php')) {
			$file_view_name = $view_fw."/index.php";
		}
		else {
			return false;
		}
		
		
		
		
		$this -> config["dir"] = dirname($file_view_name);
		
		
			//подключаем файл
		ob_start();
			extract($vars);
			
			require($file_view_name);
			$this -> content = ob_get_contents();
		ob_end_clean();
		
			//если PJAX
		if(isset($_SERVER['HTTP_X_PJAX'])) {
			Cache::$type = "json";
			header('Content-type: application/json');
			echo json_encode([
				"title" => $this -> title,
				"html"  => $this -> content,
			]);
			return true;
		}
		
			//если используется шаблонизатор
		if(!empty($this->template)) {
			$template = $this->template;
			$controller->$template($this->title, $this->content);
			return true;
		}
		
		
		$this -> config["dir"]  = $dir;
		$this -> config["web"]  = WEB;
		$this -> config["vars"] = $vars;
		
		
		if($this -> type == "html") {
			Cache::$type = "html";
			$this -> html();
		} else if($this -> type == "json") {
			Cache::$type = "json";
			$this -> json();
		}
		
		
	}
		
		//заголовок страницы
	public function title($title) {
		$this -> title = $title;
	}
	
		//мета-теги
	public function meta($meta) {
		Assets::addMeta($meta);
	}
	
		//favicon
	public function icon($img) {
		$img = $this->config["dir"]."/".$img;
		$img = str_replace("./", "", $img);
		$this -> icon  = $img;
	}
	
		//ресурсы, которые нужно добавить
	public function assets() {
		$assets = func_get_args();
		foreach($assets as $asset) {
			if(!strpos($asset, "/")) {
				//if($asset == "livereload" && DEV == false) { continue; }
				Assets::add($asset);
			} else {
				Assets::add($this->config["dir"]."/".$asset);
			}
		}
	}
	
	
		//добавляет ресурсы (сжатые)
	public function compress() {
		$assets = func_get_args();
		foreach($assets as $asset) {
			if(!strpos($asset, "/")) {
				if($asset == "livereload" && DEV == false) { continue; }
				Assets::add($asset,true);
			} else {
				Assets::add($this->config["dir"]."/".$asset,true);
			}
		}
	}
	
	
	
		//запоминает Параметры к Body
	public function style($x,$y=null) {
		
		if(isset($y)) { $x = [$x => $y]; }
		
		if(is_array($x)) {
			foreach($x as $svvo => $val) {
				if(is_numeric($val)) { $val = $val."px"; }
				$this -> config["style"][$svvo] = $val;
			}
		}
		
	}
	
	
	public function html() {
		
		//$scripts = Assets::scripts();
		//$styles  = Assets::styles();
		
		echo "<!doctype html>";
		echo '<html lang="ru">';
			
			echo "<head>";
				
					//выводим иконку
				if(!empty($this->icon)) {
					echo '<link rel="icon" href="'.Assets::img($this->icon,[16,16]).'" type="image/x-icon">';
					echo '<link rel="apple-touch-icon" href="'.Assets::img($this->icon,[180,180]).'" >';
				}
				
					//тег description
				if(!empty($this->description)) {
					echo '<meta name="description" content="'.$this->description.'">';
				}
			
					//выводим заголовок страницы
				echo '<title>'.$this->title.'</title>';
				
					//выводим дополнительные мата-теги
				echo Assets::metas();
				
					
					//выводим стили
				foreach( Assets::styles() as $style) {
					echo '<link rel="stylesheet" href="'.$style.'">';
				}
				
				
			echo "</head>";
			
			$style = [];
			foreach($this->config["style"] as $svvo => $val) { $style[] = $svvo.':'.$val; }
			$style = (empty($style)) ? "" : ' style="'.implode(";", $style).'"';
			echo '<body'.$style.'>';
				
					//делаем нужные замены в контенте
				$replaced = [
					"\n" => "",
					"\t" => "",
					' align="center"' => '',
				];
				$from = $to = []; foreach($replaced as $r1 => $r2) { $from[] = $r1; $to[] = $r2; }
				
					//выводим измененный контент
				echo str_replace($from, $to, View::$head . $this->content . View::$foot);
				
					//livereload
				if(!empty($this->config["livereload"])) {
					echo "<script>var FWliveReloadFiles = '".json_encode($this->config["livereload"])."';</script>";
				}
				
					//выводим скрипты
				foreach( Assets::scripts() as $script ) {
					echo '<script src="'.$script.'"></script>';
				}
				
				
				
			echo '</body>';
		echo "</html>";
		
	}
	
	
	public function json() {
		header('Content-type: application/json');
		echo $this -> content;
	}
	
		//указывает - что к странице можно обращаться оффлайн
	public function offline() {
		$this -> config["offline"] = true;
		return $this;
	}
	
	
		//презагружает страницу при изменении указанных файлов
	public function livereload() {
		$files = func_get_args();
		foreach($files as $file) {
			$file = $this -> config["dir"].'/'.$file;
			$file = str_replace("//", "/", $file);
			$this -> config["livereload"][] = $file;
		}
		$this -> assets("livereload");
		return $this;
	}
	
	public static function get_live_reload($files) {
		
	}
	
	
	
}