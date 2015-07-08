<?
namespace fw\classes;

class Moment {
	
	private $date;
	
	function __construct($date=null) {
		if($date == null) { $date = "NOW";}
		$this -> date = strtotime($date);
	}
	
	
	
	
	
	public static function set($date=null) {
		return (new Moment($date));
	}
}

/*


define("TODAY", date('Y-m-d'));		//текущая дата
define("NOW", date('Y-m-d H:i:s'));	//текущая дата + время
define("TIME", date('H:i:s'));		//текущее время
define("WEEK_START", week_start());	//дата первого дня этой недели
define("WEEK_END", week_end());		//дата последнего дня этой недели

	//текущая дата (до 10:00 вчера)
if(date('H') < 10) {
	define("TODAY_10", date('Y-m-d',strtotime('-1 day')));
} else {
	define("TODAY_10", date('Y-m-d'));
}

	//текущая дата (в четверг вчера)
if(date('w') == 4) {
	define("TODAY_24", date('Y-m-d',strtotime('-1 day')));
} else {
	define("TODAY_24", date('Y-m-d'));
}


	// добавить / убрать определенное кол-во дней из даты
function addDays($date,$plus,$format=null) {
	if($plus > 0) {$plus = '+ '.$plus.' days';} else {$plus = ''.$plus.' days';}
	if(!isset($format)) {$format = 'Y-m-d';}
	return date($format,strtotime($plus,strtotime($date)));
}

	// добавить / убрать определенное кол-во месяцев из даты
function addMonths($date,$plus,$format=null) {
	if($plus > 0) {$plus = '+ '.$plus.' month';} else {$plus = ''.$plus.' month';}
	if(!isset($format)) {$format = 'Y-m-d';}
	return date($format,strtotime($plus,strtotime($date)));
}


	//определяет дату конца недели
function week_end($date=null) {
	if(!isset($date)) {$date = date('Y-m-d');}
	if(date('w',strtotime($date)) == '5') {$x = date('Y-m-d',strtotime('+5 day',strtotime($date)));}
	if(date('w',strtotime($date)) == '6') {$x = date('Y-m-d',strtotime('+4 day',strtotime($date)));}
	if(date('w',strtotime($date)) == '0') {$x = date('Y-m-d',strtotime('+3 day',strtotime($date)));}
	if(date('w',strtotime($date)) == '1') {$x = date('Y-m-d',strtotime('+2 day',strtotime($date)));}
	if(date('w',strtotime($date)) == '2') {$x = date('Y-m-d',strtotime('+1 day',strtotime($date)));}
	if(date('w',strtotime($date)) == '3') {$x = date('Y-m-d',strtotime($date));}
	if(date('w',strtotime($date)) == '4') {$x = date('Y-m-d',strtotime('+6 day',strtotime($date)));}
	return $x;
}

	//определяет дату начала недели
function week_start($date=null) {
	if(!isset($date)) {$date = date('Y-m-d');}
	$date = week_end($date);
	$date = date('Y-m-d',strtotime('-6 day',strtotime($date)));
	return $date;
}


	//определяет дату конца месяца
function month_end($date=null) {
	if(!isset($date)) {$date = date('Y-m-d');}
	$x = date('Y-m-d',strtotime('last day of this month',strtotime($date)));
	return $x;
}


//определяет дату начала месяца
function month_start($date=null) {
	if(!isset($date)) {$date = date('Y-m-d');}
	$x = date('Y-m-d',strtotime('first day of this month',strtotime($date)));
	return $x;
}



	//возвращает массив с иконками
function iconList() {
	$glyphicon = [
		'asterisk' 	=> 'Звездочка',
		'plus'	 	=> 'Плюс',
		'euro' 	 	=> 'Евро',
		'minus' 		=> 'Минус',
		'cloud' 		=> 'Облако',
		'envelope' 	=> 'Письмо',
		'pencil' 		=> 'Карандаш',
		'glass' 		=> 'Бокал',
		'music' 		=> 'Музыка',
		'search' 		=> 'Поиск',
		'heart' 		=> 'Сердце',
		'star' 		=> 'Звезда',
		'star-empty' 	=> 'Пустая Звезда',
		'user' 		=> 'Человек',
		'film' 		=> 'Фильм',
		'th-large' 	=> 'Большие Блоки',
		'th' 		=> 'Блоки',
		'th-list' 	=> 'Список',
		'ok' 		=> 'Галочки',
		'remove' 		=> 'Крестик',
		'zoom-in' 	=> 'Приближение',
		'zoom-out' 	=> 'Отдаление',
		'off' 		=> 'Выключить',
		'signal' 		=> 'Сигнал',
		'cog' 		=> 'Шестеренка',
		'trash' 		=> 'Корзина',
		'home' 		=> 'Дом',
		'file' 		=> 'Файл',
		'time'		=> 'Часы',
		'road' 		=> 'Дорога',
		'download-alt' => 'Скачать в',
		'download' 	=> 'Скачать',
		'upload' 		=> 'Загрузить',
		'inbox' 		=> 'Входящие',
		'play-circle' 	=> 'Запустить',
		'repeat' 		=> 'Зациклить',
		'refresh' 	=> 'Обновить',
		'list-alt' 	=> 'Список',
		'lock' 		=> 'Замок',
		'flag' 		=> 'Флаг',
		'headphones' 	=> 'Наушники',
		'volume-off' 	=> 'Без Звука',
		'volume-down' 	=> 'Слабый звук',
		'volume-up' 	=> 'Сильный Звук',
		'qrcode' 		=> 'QR Код',
		'barcode' 	=> 'Штрихкод',
		'tag' 		=> 'Тэг',
		'tags' 		=> 'Тэги',
		'book' 		=> 'Книга',
		'bookmark' 	=> 'Закладка',
		'print' 		=> 'Принтер',
		'camera' 		=> 'Камера',
		'font' 		=> 'Шрифт',
		'bold' 		=> 'Жирный Шрифт',
		'italic' 		=> 'Наклонный Шрифт',
		'text-height' 	=> 'Высота Строки',
		'text-width' 	=> 'Длина строки',
		'align-left' 	=> 'Текст Слева',
		'align-center' => 'Текст по Центру',
		'align-right' 	=> 'Текст Справа',
		'align-justify'=> 'Текст по Ширине',
		'list' 		=> 'Список',
		'indent-left' 	=> 'Список Слева',
		'indent-right' => 'Список Справа',
		'facetime-video'=> 'Видеокамера',
		'picture' 	=> 'Изображение',
		'map-marker' 	=> 'Местоположение',
		'adjust' 		=> 'Половина Круга',
		'tint' 		=> 'Капля',
		'edit' 		=> 'Редактировать',
		'share' 		=> 'Разместить',
		'check' 		=> 'Выполнено',
		'move' 		=> 'Курсор в Стороны',
		'step-backward'=> 'Перемотка к предудущему',
		'fast-backward'=> 'Перемотка к предудущему 2',
		'backward' 	=> 'Перемотка назад',
		'play' 		=> 'Воспроизвести',
		'pause' 		=> 'Пауза',
		'stop' 		=> 'Стоп',
		'forward' 	=> 'Перемотка вперед',
		'fast-forward' => 'Перемотка к следующему 2',
		'step-forward' => 'Перемотка к следующему',
		'eject' 		=> 'Извлечь',
		'chevron-left' => 'Шеврон налево',
		'chevron-right'=> 'Шеврон направо',
		'plus-sign' 	=> 'Плюс в круге',
		'minus-sign' 	=> 'Минус в круге',
		'remove-sign' 	=> 'Крестик в круге',
		'ok-sign' 	=> 'Галочка в круге',
		'question-sign'=> 'Вопрос в круге',
		'info-sign' 	=> 'Инфо в круге',
		'screenshot' 	=> 'Цель',
		'remove-circle'=> 'Крестик в окружности',
		'ok-circle' 	=> 'Галочка в окружности',
		'ban-circle' 	=> 'Перечеркнутая окружность',
		'arrow-left' 	=> 'Стрелка налево',
		'arrow-right' 	=> 'Стрелка направо',
		'arrow-up' 	=> 'Стрелка вверх',
		'arrow-down' 	=> 'Стрелка вниз',
		'share-alt' 	=> 'Перейти в',
		'resize-full' 	=> 'Расширить',
		'resize-small' => 'Сжать',
		'exclamation-sign'=> 'Восклицательный в круге',
		'gift' 		=> 'Подарок',
		'leaf' 		=> 'Листик',
		'fire' 		=> 'Огонь',
		'eye-open' 	=> 'Глаз',
		'eye-close' 	=> 'Перечеркнутый глаз',
		'warning-sign' => 'Восклицательный в триугольнике',
		'plane' 		=> 'Самолет',
		'calendar' 	=> 'Календарь',
		'random' 		=> 'Перемешать',
		'comment' 	=> 'Коментарий',
		'magnet' 		=> 'Магнит',
		'chevron-up' 	=> 'Шиврон вверх',
		'chevron-down' => 'Шиврон вниз',
		'retweet' 	=> 'Зациклить',
		'shopping-cart'=> 'Корзина',
		'folder-close' => 'Папка',
		'folder-open' 	=> 'Открытая папка',
		'resize-vertical'=> 'Вверх вниз',
		'resize-horizontal'=> 'Вправо влево',
		'hdd' 		=> 'Жесткий диск',
		'bullhorn' 	=> 'Орало',
		'bell' 		=> 'Колокол',
		'certificate' 	=> 'Солнце',
		'thumbs-up' 	=> 'Лайк',
		'thumbs-down' 	=> 'Дизлайк',
		'hand-right' 	=> 'Палец вправо',
		'hand-left' 	=> 'Палец влево',
		'hand-up' 	=> 'Палец вверх',
		'hand-down' 	=> 'Палец вниз',
		'circle-arrow-right' => 'Стрелка в круге вправо',
		'circle-arrow-left' => 'Стрелка в круге влево',
		'circle-arrow-up' => 'Стрелка в круге вверх',
		'circle-arrow-down' => 'Стрелка в круге вниз',
		'globe' 		=> 'Планета',
		'wrench' 		=> 'Гаечный ключ',
		'tasks' 		=> 'Задачи',
		'filter' 		=> 'Воронка',
		'briefcase' 	=> 'Чемодан',
		'fullscreen' 	=> 'На весь экран',
		'dashboard' 	=> 'Спидометр',
		'paperclip' 	=> 'Скрепка',
		'heart-empty' 	=> 'Обведенное сердце',
		'link' 		=> 'Ссылка',
		'phone' 		=> 'Телефон',
		'pushpin' 	=> 'Канцелярская кнопка',
		'usd' 		=> 'Доллар',
		'gbp' 		=> 'Франк',
		'sort' 		=> 'Сортировка',
		'sort-by-alphabet' => 'Сортировка a-z',
		'sort-by-alphabet-alt' => 'Сортировка z-a',
		'sort-by-order' => 'Сортировка 1-9',
		'sort-by-order-alt' => 'Сортировка 9-1',
		'sort-by-attributes' => 'Сортировка по убыванию',
		'sort-by-attributes-alt' => 'Сортировка по возрастанию',
		'unchecked' 	=> 'Без галочки',
		'expand' 		=> 'Вправо в квадрате',
		'collapse-down'=> 'Вниз в квадрате',
		'collapse-up' 	=> 'Вверх в квадрате',
		'log-in' 		=> 'Войти',
		'flash' 		=> 'Молния',
		'log-out' 	=> 'Выйти',
		'new-window' 	=> 'В новом окне',
		'record' 		=> 'Идет запись',
		'save' 		=> 'Сохранить',
		'open' 		=> 'Открыть',
		'saved' 		=> 'Сохранено',
		'import' 		=> 'Импортировать',
		'export' 		=> 'Экспортировать',
		'send' 		=> 'Отправить',
		'floppy-disk' 	=> 'Дискета',
		'floppy-saved' => 'Сохранено на дискете',
		'floppy-remove'=> 'Удалено с дискеты',
		'floppy-save' 	=> 'С дискеты',
		'floppy-open' 	=> 'На дискету',
		'credit-card' 	=> 'Кредитная карта',
		'transfer' 	=> 'Трансфер',
		'cutlery' 	=> 'Вилка и нож',
		'header' 		=> 'Заголовок',
		'compressed' 	=> 'Сжать',
		'earphone' 	=> 'Трубка',
		'phone-alt' 	=> 'Старый телефон',
		'tower' 		=> 'Башня',
		'stats' 		=> 'Статистика',
		'sd-video' 	=> 'SD Видео',
		'hd-video' 	=> 'HD Видео',
		'subtitles' 	=> 'Субтитры',
		'sound-stereo' => 'Звук Стерео',
		'sound-dolby' 	=> 'Звук Dolby',
		'sound-5-1' 	=> 'Звук 5.1',
		'sound-6-1' 	=> 'Звук 6.1',
		'sound-7-1' 	=> 'Звук 7.1',
		'copyright-mark' => 'Автоские права',
		'registration-mark' => 'Знак регистрации',
		'cloud-download' => 'Скачать с облака',
		'cloud-upload' => 'Закачать на облако',
		'tree-conifer' => 'Елка',
		'tree-deciduous' => 'Дерево',
		
	];

	$array = [];
	
	foreach($glyphicon as $icon => $rus) {
		$array[$icon] = [
			'val' 		=> $rus,
			'data-icon'  	=> 'glyphicon glyphicon-'.$icon, 
		];
		
	}
	return $array;
	
}




	//функция создает относительный урл
function src($link) {
	$url = URL;
	$x = substr_count($url,'/');
	$x--;
	$dir = '';
	while($x != 0) {
		$dir.='../';
		$x--;
	}
	
	return $dir.$link;
}




	//функция ищет в строке
function find($where,$what) {
	if(strpos($where, $what) !== false) {
		return true;
	} else {
		return false;
	}
}



	//запускает скрипт загрузки изображений на сервер
function imgUpload($path,$size,$script=null) {
	
	require_once(HOME.'/modules/uploadImage/index.php');
	
	if(isset($script)) {
		echo '<script>';
		echo $script;
		echo '</script>';
	}
	
}








$KEY_FOR_SORT_ARRAY = '';



function sort_array($array,$key,$type='desc') {
	
	global $KEY_FOR_SORT_ARRAY;
	
	$KEY_FOR_SORT_ARRAY = $key;
	
	
	
	
	uasort($array, 'for_sort_array_'.$type);
	
	
	$array = array_values($array);
	
	return $array;
}



function for_sort_array_asc($a, $b) {
	
	global $KEY_FOR_SORT_ARRAY;
		
	if($a[$KEY_FOR_SORT_ARRAY] == $b[$KEY_FOR_SORT_ARRAY]){
		return 0;
	}
	if($a[$KEY_FOR_SORT_ARRAY] < $b[$KEY_FOR_SORT_ARRAY]){
		return -1;
	}
	return 1;
}

function for_sort_array_desc($a, $b) {
	
	global $KEY_FOR_SORT_ARRAY;
	
	if($a[$KEY_FOR_SORT_ARRAY] == $b[$KEY_FOR_SORT_ARRAY]){
		return 0;
	}
	if($a[$KEY_FOR_SORT_ARRAY] > $b[$KEY_FOR_SORT_ARRAY]){
		return -1;
	}
	return 1;
}

*/
?>
