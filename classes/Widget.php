<?
namespace fw\classes;

use fw\classes\Assets;

class Widget {
	
	
	protected function assets() {
		
			//определяем папку текущего виджета
		$file = (new \ReflectionClass(get_called_class())) -> getFileName();
		$dir = dirname($file);
		
		$args = func_get_args();
		foreach($args as $arg) {
			if(strpos($arg, "/")) {
				$arg = $dir."/".$arg;
			}
			Assets::add($arg);
		}
		
	}
	
	
}
?>