<?
namespace fw\classes;

class Cryptor {


	private static function xxor($str) {
		
		$passw = "4f5d6sf456asf65ads5f6";
		
		$salt = "Dn8*#2n!9j";
		$len = strlen($str);
		$gamma = '';
		$n = $len>100 ? 8 : 2;
		while( strlen($gamma)<$len ) {
			$gamma .= substr(pack('H*', sha1($passw.$gamma.$salt)), 0, $n);
		}
		return $str^$gamma;
	}
	
	
	public static function encrypt($str) {
		
		$one = []; $two = [];
		foreach(self::$replaces as $a => $b) {
			$one[] = $a; $two[] = $b;
		}
		
		return str_replace($one,$two, base64_encode(self::xxor($str)));
	}
	
	public static function decrypt($str) {
		
		$one = []; $two = [];
		foreach(self::$replaces as $a => $b) {
			$one[] = $a; $two[] = $b;
		}
		
		return self::xxor(base64_decode(str_replace($two, $one, $str)));
	}
	
	
	private static $replaces = [
		'/' => 'zzzzzzzzzz',
		'=' => 'yyyyyyyyyy',
		'+' => 'xxxxxxxxxx',
	];
	

}
?>