<?
namespace fw\classes;

use fw\classes\File;

class Assets {

	public static $files = [
		'script' => [],
		'style'  => [],
		'other'  => []
	];
	
	public static $filesC = [
		'script' => [],
		'style'  => [],
		'other'  => []
	];
	
	public static $meta = ['<meta charset="utf-8">'];
	
	public static $types = [
		'script' => ['js',"coffee"],
		'style'  => ['css',"scss","less","styl"],
		'other'  => ['woff','woff2','ttf','svg','map',"otf","eot"],
		'image'  => ['jpg','png',"gif"]
	];
	
	public static $libs = [];
	
	public static function get_libs() {
		
			//получаем системные библиотеки
		if(file_exists(FW.'/assets/assets.json')) {
			$libs = file_get_contents(FW.'/assets/assets.json');
			$libs = json_decode($libs,true);
			
			if(is_array($libs)) {
				foreach($libs as $lib => $cont) {
					if(isset($cont["files"])) {
						foreach ($cont["files"] as $key => $value) {
							$cont["files"][$key] = FW."/assets/".$value;
						}
					}
					self::$libs[$lib] = $cont;
					if(isset($cont["meta"])) {
						foreach ($cont["meta"] as $key => $value) {
							self::addMeta($value);
						}
					}
				}
			}
		}
		
			//получаем бибилиотеки приложения
		if(file_exists(DIR.'/assets/assets.json')) {
			$libs = file_get_contents(DIR.'/assets/assets.json');
			$libs = json_decode($libs,true);
			if(is_array($libs)) {
				foreach($libs as $lib => $cont) {
					if(isset($cont["files"])) {
						foreach ($cont["files"] as $key => $value) {
							$cont["files"][$key] = DIR."/assets/".$value;
						}
					}
					self::$libs[$lib] = $cont;
					if(isset($cont["meta"])) {
						foreach ($cont["meta"] as $key => $value) {
							self::addMeta($value);
						}
					}
				}
			}
			
		}
		
	
		
	}
	
		
		//добавляет ресурсы, которые нужно будет подключить
	public static function add($arg,$compress=false) {
		
			//получаем аргументы функции
		//$args = func_get_args();
		//foreach($args as $arg) {
			
				//если это файл,
			if(file_exists($arg)) {
				self::addFile($arg,$compress);
			}
			
				//если это библиотека
			else {
					//если библиотеки не загружены - загружаем
				if(empty(self::$libs)) { self::get_libs(); }
				
					//если такая библиотека существует
				if(isset(self::$libs[$arg])) {
					
						//если есть зависимоси
					if(isset(self::$libs[$arg]["depends"])) {
						
							//подключаем зависимости
						foreach(self::$libs[$arg]["depends"] as $dep) {
							self::add($dep,$compress);
						}
					}
					
						//добавляем файлы
					if(isset(self::$libs[$arg]["files"])) {
						foreach(self::$libs[$arg]["files"] as $file) {
							//self::addFile(dirname(dirname(__DIR__)).'/'.$file);
							self::addFile($file,$compress);
						}
					}
					
				}
				
			}
			
		//}
	}
	
	
	
	public static function addMeta($meta) {
		
		
		if(is_array($meta)) {
			$temp=[];
			foreach($meta as $name => $val) {
				$temp[] = $name.'="'.$val.'"';
			}
			$meta = '<meta '.implode(" ", $temp).'>';
		}
		
		if(!in_array($meta, self::$meta)) {
			self::$meta[] = $meta;
		}
	}
	
	
	
	public static function getFiles() {
		error_log(print_r(self::$files,true));
	}


		//добавляет файлы
	public static function addFile($file,$compress=false) {
		
		//error_log($compress);
			//если это директория
		if(is_dir($file)) {
			$dir = scandir($file);
			foreach($dir as $one) {
				if($one != '.' && $one != '..') {
					self::addFile($file.'/'.$one,$compress);
				}
			}
		}
			//если это скрипт
		else if(in_array(pathinfo($file, PATHINFO_EXTENSION), self::$types['script'])){
				//если файл не добавляелся ранее
			if( !in_array($file, self::$files['script']) && !in_array($file, self::$filesC['script']) ) {
				if($compress === false) { 	self::$files['script'][] = $file; }	//если не надо сжимать
				else { self::$filesC['script'][] = $file; }						//если надо сжимать
			}
		}
			//если это стили
		else if(in_array(pathinfo($file, PATHINFO_EXTENSION), self::$types['style'])){
			if( !in_array($file, self::$files['style']) && !in_array($file, self::$filesC['style']) ) {
				if($compress === false) { self::$files['style'][] = $file; }	//если не надо сжимать
				else {  self::$filesC['style'][] = $file; }					//если надо сжимать
			}
		}
		
			//если это прочие нужные файлы
		else if(in_array(pathinfo($file, PATHINFO_EXTENSION), self::$types['other'])){
			if(!in_array($file, self::$files['other'])) {
				self::$files['other'][] = $file;
			}
		}
	}




		//компиляция
	public static function js($file) {
		return file_get_contents($file);
		//$content = file_get_contents($file);
		//$res = $content;
		//return $res;
	}

	public static function coffee($file) {
		
		$content = file_get_contents($file);
		if(empty($content)) { 
			$content = "";
		} else {
			$content = \CoffeeScript\Compiler::compile($content,array('filename' => $file));
		}
		return $content;
	}

	public static function css($file) {
		return file_get_contents($file);
	}

	public static function less($file) {
		$less = new \lessc();
		$content = $less->compileFile($file);
		return $content;
	}

	public static function scss($file) {
		$scss = new \scssc();
		return $scss->compile(file_get_contents($file));
	}

	public static function styl($file) {
		$stylus = new \Stylus\Stylus();
		$content = file_get_contents($file);
		$css = $stylus->fromString($content)->toString();
		return $css;
	}




		//вывод
	public static function styles() {
		$styles = [];
		foreach(self::$files['style'] as $file) {
			$styles[] = self::md($file,'css');
		}
		foreach(self::$files['other'] as $file) {
			self::md($file);
		}
		
			//если есть сжатые стили
		if(!empty(self::$filesC["style"])) {
			$styles[] = self::md(self::$filesC["style"],'css');
		}
		
		return $styles;
	}

	public static function scripts() {

		$scripts = [];
		foreach(self::$files['script'] as $file) {
			$scripts[] = self::md($file,'js');
		}
		
			//если есть сжатые скрипты
		if(!empty(self::$filesC["script"])) {
			$scripts[] = self::md(self::$filesC["script"],'js');
		}
		
		
		return $scripts;
	}

	public static function metas() {
		$metas = "";
		foreach(self::$meta as $meta) {
			$metas .= $meta;
		}
		return $metas;
	}



		//генерация файлов
	public static function md($file,$ext=null,$param=null) {
		
			//определяем расширение файла
		if(!is_array($file)) {
			$info = pathinfo($file, PATHINFO_EXTENSION);
		} else {
			$info = $ext;
		}
		if(!isset($ext)) { 
			$ext = $info;
		}
			
			
		
		
			
			//дополнительные параметры
		if(!isset($param)) { $paramSTR = ""; }
		else if(is_string($param)) { $paramSTR = $param; }
		else if(is_numeric($param)) { $paramSTR = "".$param; }
		else if(is_array($param)) { $paramSTR = implode("",$param); }
		else { $paramSTR = ""; }
		
		
		if(in_array($info, self::$types["other"])) {
			
				//определяем имя файла
			$fileName = explode('/', $file);
			$fileName = $fileName[sizeof($fileName)-1];
			
			if(!file_exists(WEB."/assets/other/".$fileName)) {
				if(!file_exists(WEB."/assets/other")) {
					mkdir(WEB."/assets/other");
				}
				copy($file,WEB."/assets/other/".$fileName);
			}
			
			return "/assets/other/".$fileName;
		}
		
			//для всех остальных типов файлов
		else {
			
			
			
				//если передан массив
			if(is_array($file)) {
				$mdName = '';
				foreach($file as $f) {
					$mdName.= $f.filemtime($f);
				}
				$mdName = md5($mdName).'.'.$ext;
			} else {
				$mdName = md5($file.filemtime($file).$paramSTR).'.'.$ext;
			}
			
			$mdName = File::subdirs($mdName);
			
			//$mdName = substr($mdName, 0, 2)."/".substr($mdName, 2, 2)."/".substr($mdName, 4);
			
				//определяем подпапку
			    if(in_array($info, self::$types["image"]))  { $path = "images"; }
			elseif(in_array($info, self::$types["style"]))  { $path = "styles"; }
			elseif(in_array($info, self::$types["script"])) { $path = "scripts";}
			
				//если такой файл не найден - создаем
			if(!file_exists(WEB."/assets/".$path."/".$mdName)) {
				
					//создаем нужную папку, если ее нет
				File::mkdir(WEB."/assets/".$path."/".$mdName);
				
					//скрипты - стили
				if($path == "scripts") {
					
						
						//для обычных файлов
					if(!is_array($file)) {
						$content = self::$info($file);
					} 
						//для массивов (сжимаем в 1)
					else {
						$content = '';
						foreach($file as $f) {
							$info = pathinfo($f, PATHINFO_EXTENSION);
							$content.= ';'. \WebSharks\JsMinifier\Core::compress(self::$info($f));
						}
					}

					$fp = fopen(WEB."/assets/".$path."/".$mdName,'w');
					fwrite($fp, $content);
					fclose($fp);
				}
				elseif($path == "styles") {
					
						//для обычных файлов
					if(!is_array($file)) {
						$content = self::$info($file);
					} 
						//для массивов (сжимаем в 1)
					else {
						$content = '';
						foreach($file as $f) {
							$info = pathinfo($f, PATHINFO_EXTENSION);
							$content.= self::$info($f);
						}
					}
					
					$fp = fopen(WEB."/assets/".$path."/".$mdName,'w');
					fwrite($fp, $content);
					fclose($fp);
				}
					//изображения
				elseif($path == "images") {
					
						//копируем в публичную папку
					$outFile = WEB."/assets/".$path."/".$mdName;
					copy($file,$outFile);
					
						//если задан размер - обрезаем
					if($param != 0) {
						if(is_string($param) || is_numeric($param)) {$size = [$param,$param]; }
						else { $size = $param; }
						self::resize($outFile,$size[0],$size[1]);
					}
					
				}

			}
				//возвращаем web путь к файлу
			return "/assets/".$path."/".$mdName;
			
		}
		

	}
	
	
		//находит изображение и сохдает его шифрованную копию
	public static function img($file,$size=0,$nophoto="base") {
		
			//если передан массив файлов - берем только первый файл
		if(is_array($file) && !empty($file)) { $file = $file[0];}
		
		$img = $file;
		
			//если файл не существует
		if(empty($file) || !file_exists($file)) {
			
			$img = FW.'/assets/img/noPhoto/'.$nophoto.'.jpg';
			if(!file_exists($img)) {
				$img = FW.'/assets/img/noPhoto/base.jpg';
			}
		}
		
		
		$ext = strtolower(pathinfo($img, PATHINFO_EXTENSION));
		
		if($ext == "jpg" || $ext == "png" || $ext =="jpeg") {
			return self::md($img,null,$size);
		}
		else {
		
			//если есть картинка для этого расширения
			
			$dir = FW."/assets/img/filetype/";
			$file = $dir.$ext.".jpg";
			if(file_exists($file)) {
				$img = $file;
			}
		
			//если нет картинки для этого расширения
			else {
				$img = FW.'/assets/img/filetype/file.jpg';
			}
			
			return self::md($img,null,$size);
		}
		
		
	}
	
		
	

		//$x_o и $y_o - координаты левого верхнего угла выходного изображения на исходном
		//$w_o и h_o - ширина и высота выходного изображения
	public static function crop($image, $x_o, $y_o, $w_o, $h_o) {
		
		
		
		if (($x_o < 0) || ($y_o < 0) || ($w_o < 0) || ($h_o < 0)) {
			//Некорректные входные параметры
			return false;
		}
		list($w_i, $h_i, $type) = getimagesize($image); // Получаем размеры и тип изображения (число)
			$types = array("", "gif", "jpeg", "png"); // Массив с типами изображений
			$ext = $types[$type]; // Зная "числовой" тип изображения, узнаём название типа
		if ($ext) {
			$func = 'imagecreatefrom'.$ext; // Получаем название функции, соответствующую типу, для создания изображения
			$img_i = $func($image); // Создаём дескриптор для работы с исходным изображением
		} else {
			//echo 'Некорректное изображение'; // Выводим ошибку, если формат изображения недопустимый
			return false;
		}
		if ($x_o + $w_o > $w_i) $w_o = $w_i - $x_o; // Если ширина выходного изображения больше исходного (с учётом x_o), то уменьшаем её
		if ($y_o + $h_o > $h_i) $h_o = $h_i - $y_o; // Если высота выходного изображения больше исходного (с учётом y_o), то уменьшаем её
		$img_o = imagecreatetruecolor($w_o, $h_o); // Создаём дескриптор для выходного изображения
		
		imagealphablending($img_o, false);
		imagesavealpha($img_o, true);
		
		
		imagecopy($img_o, $img_i, 0, 0, $x_o, $y_o, $w_o, $h_o); // Переносим часть изображения из исходного в выходное
		$func = 'image'.$ext; // Получаем функция для сохранения результата
		return $func($img_o, $image); // Сохраняем изображение в тот же файл, что и исходное, возвращая результат этой операции
	}


		//$w_o и h_o - ширина и высота выходного изображения
	public static function resize($image, $w_o = false, $h_o = false) {
		
		if (($w_o < 0) || ($h_o < 0)) {
			//echo "Некорректные входные параметры";
			return false;
		}
		list($w_i, $h_i, $type) = getimagesize($image); // Получаем размеры и тип изображения (число)
		$types = array("", "gif", "jpeg", "png"); // Массив с типами изображений
		$ext = $types[$type]; // Зная "числовой" тип изображения, узнаём название типа
		if ($ext) {
			$func = 'imagecreatefrom'.$ext; // Получаем название функции, соответствующую типу, для создания изображения
			$img_i = $func($image); // Создаём дескриптор для работы с исходным изображением
		} else {
			echo 'Некорректное изображение'; // Выводим ошибку, если формат изображения недопустимый
			return false;
		}
		/* Если указать только 1 параметр, то второй подстроится пропорционально */
		if (!$h_o) $h_o = $w_o / ($w_i / $h_i);
		if (!$w_o) $w_o = $h_o / ($h_i / $w_i);
		$img_o = imagecreatetruecolor($w_o, $h_o); // Создаём дескриптор для выходного изображения
		
		imagealphablending($img_o, false);
		imagesavealpha($img_o, true);
		
		imagecopyresampled($img_o, $img_i, 0, 0, 0, 0, $w_o, $h_o, $w_i, $h_i); // Переносим изображение из исходного в выходное, масштабируя его
		$func = 'image'.$ext; // Получаем функция для сохранения результата
			return $func($img_o, $image); // Сохраняем изображение в тот же файл, что и исходное, возвращая результат этой операции
	}
		
	
	
	
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
	
	


}



