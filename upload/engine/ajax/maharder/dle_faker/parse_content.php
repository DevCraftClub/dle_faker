<?php

global $fakerConfig;

use Faker\Factory;
use JetBrains\PhpStorm\ExpectedValues;

if (!defined('DATALIFEENGINE')) {
	header('HTTP/1.1 403 Forbidden');
	header('Location: ../../../../');
	exit('Hacking attempt!');
}

$lang  = $fakerConfig['lang'] ?? MhTranslation::getLocale();
$faker = Factory::create($lang);
const DEFAULT_NEWS_FORMAT = 'Y-m-d H:m:s';

/**
 * Извлекает и заменяет шаблоны формата `{{ randomNumber nums=X strict=Y }}`
 * на сгенерированные случайные числа, используя глобальную зависимость `$faker`.
 *
 * Шаблоны имеют следующий формат:
 * - `nums=X` - задает количество цифр в генерируемом числе.
 * - `strict=Y` (необязательный) - указывает, следует ли строго соблюдать количество цифр.
 *
 * @param string $text Текст, содержащий шаблоны для замены.
 *
 * @return string Текст с замененными шаблонами на случайные числа.
 *
 * @global \Faker\Generator $faker Глобальный объект генератора случайных данных.
 *
 * @see \Faker\Generator::randomNumber() Для генерации случайных чисел.
 * @see \Faker\Extension\Extension::numberBetween() Для генерации чисел в диапазоне.
 */
function extractNumsAndStrict(string $text): string {
	global $faker;
	// Регулярное выражение для поиска
	$pattern = '/{{\srandomNumber\s+nums=(\d+)(?:\s+strict=(true|false))?\s}}/';

	// Поиск всех совпадений
	preg_match_all($pattern, $text, $matches, PREG_SET_ORDER);

	for ($i = 0; $i < $matches[0]; $i++) {
		$original = $matches[0][$i];
		$num      = filter_var($matches[1][$i], FILTER_VALIDATE_INT);
		$strict   = null;
		if (isset($matches[2]) && !empty($matches[2][$i])) {
			$strict = $matches[2][$i];
		}

		$text = str_replace($original, $faker->randomNumber($num, $strict), $text);

	}

	return $text;

}

/**
 * Извлекает и заменяет случайные цифры в строке согласно заданному шаблону.
 *
 * Функция ищет совпадения с шаблоном вида `{{ randomDigit not=X }}`, где `X` —
 * запрещённое число. Найденные шаблоны заменяются случайными числами, сгенерированными
 * методом `randomDigitNot`, которые не равны указанному запрещённому числу.
 *
 * @param string $text Входной текст, содержащий шаблоны для замены.
 *
 * @global \Faker\Generator $faker Глобальный объект генератора случайных данных.
 * @return string Текст с заменёнными шаблонами случайных цифр.
 *
 * @see \Faker\Generator::randomDigitNot()
 * @throws \RuntimeException Если регулярное выражение возвращает некорректные данные.
 */
function extractRandomDigit(string $text): string {
	// Регулярное выражение для поиска
	global $faker;
	$pattern = '/{{\srandomDigit\s+not=(\d+)\s}}?/';
	// Поиск всех совпадений
	preg_match_all($pattern, $text, $matches, PREG_SET_ORDER);
	for ($i = 0; $i < $matches[0]; $i++) {
		$original = $matches[0][$i];
		$not      = filter_var($matches[1][$i], FILTER_VALIDATE_INT);
		$text = str_replace($original, $faker->randomDigitNot($not), $text);
	}
	return $text;
}

/**
 * Извлекает шаблонные параметры randomFloat из текста, генерирует случайное значение
 * и заменяет шаблон на сгенерированное число.
 *
 * @param string $text Содержит текст с шаблонами вида {{ randomFloat ... }}.
 * @global \Faker\Generator $faker Глобальный объект Faker для генерации случайных данных.
 * @return string Текст с заменёнными шаблонами randomFloat на случайные числа.
 * @see \Faker\Generator::randomFloat()
 */
function extractRandomFloatParams(string $text): string {
	// Регулярное выражение для поиска randomFloat с необязательными параметрами
	global $faker;
	$pattern = '/{{\s*randomFloat(?:\s+(float|min|max)=(\d+))?(?:\s+(float|min|max)=(\d+))?(?:\s+(float|min|max)=(\d+))?\s*}}/';

	// Поиск всех совпадений
	preg_match_all($pattern, $text, $matches, PREG_SET_ORDER);

	for ($i = 0; $i < $matches; $i++) {
		for ($j = 1; $j < $matches[0]; $j++) {
			$original = $matches[0][$i];
			if ($matches[$j][$i] == 'float') $float = filter_var($matches[$j + 1][$i], FILTER_VALIDATE_INT) ?: null;
			if ($matches[$j][$i] == 'min') $min = filter_var($matches[$j + 1][$i], FILTER_VALIDATE_INT) ?: 0;
			if ($matches[$j][$i] == 'max') $max = filter_var($matches[$j + 1][$i], FILTER_VALIDATE_INT) ?: null;

			$text = str_replace($original, $faker->randomFloat($float, $min, $max), $text);
		}
	}

	return $text;
}

/**
 * Заменяет шаблоны с использованием конструкции {{ numberBetween }} в указанном тексте
 * случайными числами в заданном диапазоне.
 *
 * Регулярное выражение ищет шаблон вида {{ numberBetween min=X max=Y }},
 * где параметры min и max являются необязательными.
 * Если параметр min не указан, используется значение PHP_INT_MIN,
 * если max не указан - используется значение 2147483647.
 *
 * При каждом совпадении выполняется генерация случайного числа с использованием
 * функции numberBetween глобального объекта $faker.
 *
 * @param string $text Текст, содержащий шаблоны для замены.
 *
 * @global object $faker Объект для генерации случайных данных, предоставляющий метод numberBetween().
 *
 * @return string Текст с замененными шаблонами на случайные числа.
 */
function extractNubmerBetween(string $text): string {
	global $faker;
	$pattern = '/{{\s*numberBetween(?:\s+(min|max)=(\d+?))?(?:\s+(min|max)=(\d+?))?\s*}}/';
	preg_match_all($pattern, $text, $matches, PREG_SET_ORDER);
	for ($i = 0; $i < $matches; $i++) {
		for ($j = 1; $j < $matches[0]; $j++) {
			$original = $matches[0][$i];
			if ($matches[$j][$i] == 'min') $min = filter_var($matches[$j + 1][$i], FILTER_VALIDATE_INT) ?: PHP_INT_MIN;
			if ($matches[$j][$i] == 'max') $max = filter_var($matches[$j + 1][$i], FILTER_VALIDATE_INT) ?: 2147483647;
			$text = str_replace($original, $faker->numberBetween($min, $max), $text);
		}
	}
	return $text;
}

/**
 * Извлекает параметры из шаблона с использованием синтаксиса {{ randomElements }} и
 * заменяет их на созданные значения, используя глобальный объект $faker.
 *
 * @param string $text Текст, содержащий шаблоны для замены.
 *
 * @global \Faker\Generator $faker Используется для генерации случайных элементов.
 *
 * @return string Возвращает текст с заменёнными шаблонами {{ randomElements }} на их значения.
 *
 * @see randomElements() Функция для получения случайных элементов из массива.
 */
function extractRandomElementsParams(string $text): string {
	// Регулярное выражение для поиска randomElements с необязательными параметрами
	global $faker;
	$pattern = '/{{\s*randomElements(?:\s+(items=\[.*?\]|count=\d+|connector=\'[^\']*\'|connector=\S+))*\s*}}/';

	// Поиск всех совпадений
	preg_match_all($pattern, $text, $matches, PREG_SET_ORDER);

	foreach ($matches[0] as $original) {
		$items     = [];
		$count     = 1;
		$connector = '_';

		if (preg_match('/items=\[(.*?)\]/', $matches[0][0], $itemsMatch)) {
			// Преобразуем items в массив
			$items = array_filter(array_map('trim', explode(',', $itemsMatch[1])));
		}

		if (preg_match('/count=(\d+)/', $matches[0][0], $countMatch)) {
			$count = filter_var($countMatch[1], FILTER_VALIDATE_INT);
		}

		if (preg_match('/connector=\'[^\']*\'|connector=\S+/', $matches[0][0], $connectorMatch)) {
			$connector = $connectorMatch[1];
		}

		$count = min(count($items), $count);

		$text = str_replace($original, implode($connector, $faker->randomElements($items, $count, false)), $text);
	}

	return $text;
}

/**
 * Заменяет паттерн вида {{ words max=N }} в тексте на случайные слова.
 *
 * @param string $text Текст, содержащий паттерны для замены.
 * @return string Текст с заменёнными паттернами.
 *
 * @global \Faker\Generator $faker Генератор случайных данных Faker.
 * @see \Faker\Provider\Base::words()
 */
function createWords($text): string {
	global $faker;
	$pattern = '/{{\s*words(?:\s+max=(\d+))?\s*}}/';

	preg_match_all($pattern, $text, $matches, PREG_SET_ORDER);

	// Обработка результатов
	foreach ($matches as $match) {
		$original = $match[0];
		$max      = null;

		// Извлекаем max, если он указан
		if (isset($match[1]) && is_numeric($match[1])) {
			$max = filter_var($match[1], FILTER_VALIDATE_INT) ?: 3;
		}

		$max = $max ?? 3;

		$text = str_replace($original, implode(', ', $faker->words($max)), $text);
	}

	return $text;
}

/**
 * Генерирует текст с заменой всех вхождений шаблона {{ sentences max=N }}
 * на сгенерированные предложения с использованием глобальной переменной $faker.
 *
 * Ищет совпадения с шаблоном {{ sentences max=N }}, где N указывает максимальное количество предложений,
 * затем заменяет их сгенерированными предложениями с помощью метода sentences из $faker.
 * Если параметр max отсутствует или указан некорректно, используется значение по умолчанию — 3.
 *
 * @global \Faker\Generator $faker Объект Faker для генерации предложений.
 * @param string $text Входной текст, содержащий шаблоны для замены.
 * @return string Текст с замененными шаблонами предложений.
 * @see \Faker\Generator::sentences() Метод для генерации массива предложений.
 */
function generateSentences($text): string {
	global $faker;
	$pattern = '/{{\s*sentences(?:\s+max=(\d+))?\s*}}/';

	preg_match_all($pattern, $text, $matches, PREG_SET_ORDER);

	// Обработка результатов
	foreach ($matches as $match) {
		$original = $match[0];
		$max      = null;

		// Извлекаем max, если он указан
		if (isset($match[1]) && is_numeric($match[1])) {
			$max = filter_var($match[1], FILTER_VALIDATE_INT) ?: 3;
		}

		$text = str_replace($original, $faker->sentences($max, true), $text);
	}

	return $text;
}

/**
 * Генерирует текст с заменой шаблонов в виде {{ paragraph max=N }} на случайные абзацы текста.
 *
 * Шаблоны в тексте, которые соответствуют указанному формату, будут заменены на
 * случайно сгенерированные абзацы текста, где количество абзацев указывается
 * через параметр `max`. По умолчанию используется 3 абзаца.
 *
 * @global \Faker\Generator $faker Генератор случайных данных, используемый для создания абзацев.
 * @param string $text Входной текст, содержащий шаблоны для замены.
 * @return string Текст с заменёнными шаблонами на случайный текст.
 * @see \Faker\Generator::paragraphs()
 */
function generateParagraph($text): string {
	global $faker;
	$pattern = '/{{\s*paragraph\s+max=(\d+)?\s*}}/';

	preg_match_all($pattern, $text, $matches, PREG_SET_ORDER);

	// Обработка результатов
	foreach ($matches as $match) {
		$original = $match[0];
		$max      = null;

		// Извлекаем max, если он указан
		if (isset($match[1]) && is_numeric($match[1])) {
			$max = filter_var($match[1], FILTER_VALIDATE_INT) ?: 3;
		}

		$max = $max ?? 3;

		$text = str_replace($original, $faker->paragraphs($max, true), $text);
	}

	return $text;
}

/**
 * Генерирует текст, заменяя шаблонные маркеры {{ text max=число }} случайным текстом.
 *
 * Маркеры в тексте имеют формат {{ text max=число }}. Если параметр `max` указан, он
 * задаёт максимальную длину сгенерированного текста. Если `max` не указан, используется
 * значение по умолчанию - 200 символов.
 *
 * @param string $text Текст, содержащий шаблонные маркеры для замены.
 *
 * @global object $faker Экземпляр Faker, используемый для генерации случайного текста.
 *
 * @return string Текст с заменёнными шаблонными маркерами.
 *
 * @see text() Используемая для генерации текста функция Faker.
 */
function generateText($text): string {
	global $faker;
	$pattern = '/{{\s*text(?:\s+max=(\d+))?\s*}}/';

	preg_match_all($pattern, $text, $matches, PREG_SET_ORDER);

	// Обработка результатов
	foreach ($matches as $match) {
		$original = $match[0];
		$max      = null;

		// Извлекаем max, если он указан
		if (isset($match[1]) && is_numeric($match[1])) {
			$max = filter_var($match[1], FILTER_VALIDATE_INT) ?: 200;
		}

		$max = $max ?? 200;

		$text = str_replace($original, $faker->text($max), $text);
	}

	return $text;
}

/**
 * Генерирует строку с заменой шаблонов даты и времени на соответствующие значения.
 *
 * Шаблоны даты и времени в строке задаются в виде:
 * {{ datetime format="формат" }}
 *
 * Пример шаблона:
 * {{ datetime format="Y-m-d H:i:s" }}
 *
 * Если формат даты не указан, используется значение константы DEFAULT_NEWS_FORMAT.
 *
 * @param string            $text  Строка, содержащая текст с шаблонами для замены.
 *
 * @return string Строка, где все шаблоны даты и времени заменены на сгенерированные значения.
 * @throws DateInvalidTimeZoneException
 * @global \Faker\Generator $faker Генератор данных Faker, используемый для генерации даты и времени.
 * @see dateTime() Метод генерации даты и времени Faker.
 * @see getTimezone() Функция определения временной зоны.
 *
 */
function generateDateTime($text): string {
	global $faker;
	$pattern = '/{{\s*datetime(?:\s+format=["\']([^"\']+)["\'])?\s*}}/';

	preg_match_all($pattern, $text, $matches, PREG_SET_ORDER);

	// Обработка результатов
	foreach ($matches as $match) {
		$original = $match[0];
		$format   = null;

		// Извлекаем max, если он указан
		if (isset($match[1]) && is_numeric($match[1])) {
			$format = filter_var($match[1], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
		}

		$format = empty($format) ? DEFAULT_NEWS_FORMAT : $format;

		$text = str_replace($original, $faker->dateTime(timezone: getTimezone(false))->format($format), $text);
	}

	return $text;
}

/**
 * Возвращает случайное значение или значения из переданного массива с возможностью исключения определённых значений.
 *
 * @param array             $array   Массив, из которого будут выбраны случайные элементы.
 * @param int               $count   Количество случайных элементов, которые нужно выбрать. По умолчанию 1.
 * @param array|string|null $exclude Значение или массив значений, которые следует исключить из $array перед выбором
 *                                   случайных элементов.
 *
 * @return mixed Возвращает случайное значение или массив со случайными значениями. Если массив пуст или опустошён
 *               после исключений, возвращает null.
 *
 * @global \Faker\Generator $faker   Глобальный объект Faker, используемый для генерации случайных чисел.
 *
 * @see \Faker\Generator::numberBetween()
 * @see array_rand()
 * @see array_diff_key()
 * @see array_intersect_key()
 */
function getRandomValue(array $array, int $count = 1, array|string|null $exclude = null): mixed {
	// Быстрая проверка на пустоту массива
	global $faker;
	if (!$array) {
		return null;
	}

	// Приведение и исключение значений
	if ($exclude !== null) {
		$exclude = (array)$exclude;
		$array   = array_diff_key($array, array_flip($exclude));
	}

	// Проверка на пустоту после исключений
	if (!$array) {
		return null;
	}

	$count = min(count($array), $count);
	if ($count > 1) {
		$count = $faker->numberBetween(1, $count);
	}

	// Генерация случайных ключей и выбор значений
	$randomKeys = array_rand($array, min($count, count($array)));
	if (is_int($randomKeys)) {
		return $array[$randomKeys];
	}

	return array_intersect_key($array, array_flip($randomKeys));
}

/**
 * Возвращает объект временной зоны или строку с названием временной зоны, в зависимости от переданного параметра.
 *
 * @param bool   $parse  Если true, возвращает объект DateTimeZone; если false, возвращает строку с названием временной
 *                       зоны.
 *
 * @return DateTimeZone|string Объект временной зоны или строка с названием временной зоны.
 * @throws DateInvalidTimeZoneException
 * @global array $config Глобальная переменная конфигурации, содержащая ключ 'date_adjust' для установки временной
 *                       зоны.
 */
function getTimezone(bool $parse = true): DateTimeZone|string {
	global $config;
	return $parse ? new DateTimeZone($config['date_adjust']) : $config['date_adjust'];
}

/**
 * Генерирует случайную дату в пределах заданного диапазона.
 *
 * Если начальная и конечная даты идентичны, возвращает форматированную дату начала.
 * В случае, если конечная дата не указана, диапазон устанавливается от начальной даты до текущей.
 * Если начальная дата позже конечной, параметры меняются местами.
 *
 * @param string|DateTime      $start  Начальная дата в формате строки или объекте DateTime.
 * @param string|DateTime|null $end    Конечная дата в формате строки, объекте DateTime или null (по умолчанию текущая
 *                                     дата).
 *
 * @return string Случайная дата, отформатированная в формате DEFAULT_NEWS_FORMAT.
 *
 * @see DateTime Используем для работы с датами.
 * @see getTimezone Используется для определения временной зоны.
 * @global string              $config ['date_adjust'] Глобальная переменная, которая задаёт временную зону.
 */
function getRandomDateBetween($start, $end = null): string {

	if ($start == $end) return (new DateTime($start, getTimezone()))->format(DEFAULT_NEWS_FORMAT);

	$start = is_string($start) ? new DateTime($start, getTimezone()) : $start;
	$end   = is_string($end)
		? new DateTime($end, getTimezone())
		: ($end ?? new DateTime(
			timezone: getTimezone()
		));

	if ($start > $end) {
		[$start, $end] = [$end, $start];
	}

	$startTimestamp  = $start->getTimestamp();
	$endTimestamp    = $end->getTimestamp();
	$randomTimestamp = mt_rand($startTimestamp, $endTimestamp);

	return (new DateTime())->setTimestamp($randomTimestamp)->format(DEFAULT_NEWS_FORMAT);
}

/**
 * Преобразует строковое значение в логическое.
 *
 * Значение `'on'` будет преобразовано в `true`, `'off'` — в `false`.
 * Для всех остальных значений используется случайное логическое значение,
 * сгенерированное объектом `$faker`.
 *
 * @param string            $text  Входное строковое значение. Поддерживает значения: 'on', 'off', 'random'.
 *
 * @return bool Преобразованное логическое значение.
 *
 * @global \Faker\Generator $faker Ссылка на глобальный объект генератора случайных данных.
 *
 * @see \Faker\Generator::boolean() Для генерации случайного логического значения.
 */
function parseBoolValue(#[ExpectedValues(values: ['on', 'off', 'random'])] string $text): bool {
	global $faker;
	return match ($text) {
		'on'    => true,
		'off'   => false,
		default => $faker->boolean()
	};
}

if (!function_exists('create_metatags')) {
	/**
	 * Создает метатеги на основе текста и входящих параметров.
	 *
	 * Функция анализирует переданный текст, а также параметры запроса для
	 * формирования тэгов meta title, description и keywords. Для обработки текста
	 * используются внутренние функции очистки, а также анализ ключевых слов.
	 *
	 * @param string  $story  Текст, из которого будут извлечены метатеги.
	 * @param bool    $ajax   Используется ли режим AJAX, чтобы включить создание метатегов,
	 *                        даже если глобальные параметры этого не разрешают.
	 *
	 * @return array Ассоциативный массив, содержащий ключи:
	 *               - 'title'        — Заголовок meta title.
	 *               - 'description'  — Текст meta description.
	 *               - 'keywords'     — Строка ключевых слов meta keywords.
	 *
	 * @global object $db     Объект базы данных, используемый для безопасных операций SQL.
	 *
	 * @global array  $config Конфигурационные параметры приложения.
	 * @see create_metatags engine/inc/include/functions.inc.php
	 */
	function create_metatags($story, $ajax = false) {
		global $config, $db;

		$keyword_count = 20;
		$newarr        = [];
		$headers       = [];

		$bad_keywords_symbol = [",", ".", "/", "#", ":", "@", "~", "=", "-", "+", "*", "^", "%", "$", "?", "!"];
		$remove              = ['\t', '\n', '\r'];

		$story = explode("{PAGEBREAK}", $story);
		$story = $story[0];

		$story = str_replace($remove, ' ', $story);

		$_REQUEST['meta_title'] = isset($_REQUEST['meta_title']) ? trim(
			str_replace($remove, ' ', $_REQUEST['meta_title'])
		) : '';
		$_REQUEST['descr']      = isset($_REQUEST['descr']) ? trim(str_replace($remove, ' ', $_REQUEST['descr'])) : '';
		$_REQUEST['keywords']   = isset($_REQUEST['keywords']) ? trim(
			str_replace($remove, ' ', $_REQUEST['keywords'])
		) : '';

		if ($_REQUEST['meta_title']) {

			$headers['title'] = clear_content($_REQUEST['meta_title'], 300, false);

			$headers['title'] = $db->safesql($headers['title']);

		} else $headers['title'] = "";

		if ($_REQUEST['descr']) {

			$headers['description'] = clear_content($_REQUEST['descr'], 300, false);

			$headers['description'] = $db->safesql($headers['description']);

		} else if ($config['create_metatags'] or $ajax) {

			$headers['description'] = clear_content(stripslashes($story), 0, false);

			if (dle_strlen($headers['description']) > 300) {

				$headers['description'] = dle_substr($headers['description'], 0, 300);

				if (($temp_dmax = dle_strrpos($headers['description'], ' '))) $headers['description'] = dle_substr(
					$headers['description'],
					0,
					$temp_dmax
				);

			}

			$headers['description'] = $db->safesql($headers['description']);

		} else {

			$headers['description'] = '';

		}

		if ($_REQUEST['keywords']) {

			$arr    = explode(",", clear_content($_REQUEST['keywords'], 0, false));
			$newarr = [];

			foreach ($arr as $word) {
				$newarr[] = trim(str_replace($bad_keywords_symbol, '', $word));
			}

			$_REQUEST['keywords'] = implode(", ", $newarr);

			$headers['keywords'] = $db->safesql($_REQUEST['keywords']);

		} else if ($config['create_metatags'] or $ajax) {

			$story = clear_content(str_replace($bad_keywords_symbol, '', stripslashes($story)), 0, false);

			$arr = explode(" ", $story);

			foreach ($arr as $word) {
				$word = str_replace("&amp;", "&", $word);
				if (dle_strlen($word) > 4) $newarr[] = $word;
			}

			$arr = array_count_values($newarr);
			arsort($arr);

			$arr = array_keys($arr);

			$offset = 0;

			$arr = array_slice($arr, $offset, $keyword_count);

			$headers['keywords'] = $db->safesql(implode(", ", $arr));

		} else {

			$headers['keywords'] = '';

		}

		return $headers;
	}
}

/**
 * Обрабатывает и заменяет указанный тег в зависимости от заданного типа.
 *
 * Эта функция использует глобальные объекты `$faker` и `$fakerConfig` для генерации
 * данных на основе строковых шаблонов. Поддерживает как стандартные, так и специальные
 * шаблоны, а также шаблоны, зависящие от типа (`user` или `post`).
 *
 * @param string  $tag         Тег для обработки с потенциальными шаблонами.
 * @param string  $type        Тип данных, для которых производится обработка: 'user' или 'post'.
 *                             Поддерживаются строго два значения.
 *
 * @return mixed Возвращает обработанный тег с заменёнными шаблонами.
 *
 * @global object $faker       Экземпляр генератора данных для замены шаблонов.
 * @global array  $fakerConfig Конфигурация, содержащая дополнительные параметры для обработки некоторых тегов.
 *
 * @see strtr()                Для замены содержимого строки по массиву.
 * @see str_contains()         Для проверки наличия подстрок в теге.
 * @see extractNumsAndStrict() Для обработки шаблонов, начинающихся с {{ randomNumber.
 * @see extractRandomDigit()   Для обработки специфичных шаблонов с {{ randomDigit not.
 * @see extractRandomFloatParams() Для вычисления параметров {{ randomFloat.
 * @see extractRandomElementsParams() Для обработки параметров {{ randomElements.
 * @see extractNubmerBetween() Для обработки шаблонов {{ numberBetween.
 * @see getRandomValue()       Для обработки шаблона {{ random_user }}, специфичного для типа 'post'.
 */
function parseFaker(string $tag, #[ExpectedValues(values: ['user', 'post'])]
string                     $type): mixed {
	global $faker, $fakerConfig;

	// Оптимизация замены специальных символов с использованием массива
	$replacements = [
		'&amp;amp;'  => '&amp;',
		'&amp;#124;' => '&#124;',
		'&amp;'      => '&',
		'&nbsp;'     => ' ',
	];
	$tag          = strtr($tag, $replacements); // strtr быстрее, чем несколько вызовов str_replace

	// Универсальная обработка стандартных шаблонов
	$standardTemplates = [
		'{{ yesNo }}'         => $faker->boolean(),
		'{{ emoji }}'         => $faker->emoji(),
		'{{ randomDigit }}'   => $faker->randomDigit(),
		'{{ randomFloat }}'   => $faker->randomFloat(),
		'{{ randomLetter }}'  => $faker->randomLetter(),
		'{{ numberBetween }}' => $faker->numberBetween(),
	];
	$tag               = strtr($tag, $standardTemplates);

	// Проверки специфичных шаблонов с вызовом соответствующих функций
	$specialTags = [
		'{{ randomNumber'    => 'extractNumsAndStrict',
		'{{ randomDigit not' => 'extractRandomDigit',
		'{{ randomFloat'     => 'extractRandomFloatParams',
		'{{ randomElements'  => 'extractRandomElementsParams',
		'{{ numberBetween'   => 'extractNubmerBetween',
	];
	foreach ($specialTags as $needle => $function) {
		if (str_contains($tag, $needle)) {
			$tag = $function($tag);
		}
	}

	// Шаблоны, специфичные для типа 'user'
	if ($type === 'user') {
		$userTemplates = [
			'{{ userName }}'        => $faker->userName(),
			'{{ name }}'            => $faker->name(),
			'{{ firstName }}'       => $faker->firstName(),
			'{{ firstNameMale }}'   => $faker->firstNameMale(),
			'{{ firstNameFemale }}' => $faker->firstNameFemale(),
			'{{ lastName }}'        => $faker->lastName(),
			'{{ suffix }}'          => '',
			'{{ title }}'           => $faker->title(),
			'{{ titleMale }}'       => $faker->titleMale(),
			'{{ titleFemale }}'     => $faker->titleFemale(),
			'{{ email }}'           => $faker->safeEmailDomain(),
		];
		$tag           = strtr($tag, $userTemplates);
	} else if ($type === 'post') {
		// Шаблоны, специфичные для типа 'post'
		$postTemplates = [
			'{{ word }}'            => $faker->word(),
			'{{ sentence }}'        => $faker->sentence(),
			'{{ paragraph }}'       => $faker->paragraph(),
			'{{ text }}'            => $faker->text(),
			'{{ random_user }}'     => getRandomValue(explode(',', $fakerConfig['users'])),
			'{{ random_category }}' => $fakerConfig['categories'],
		];
		$tag           = strtr($tag, $postTemplates);

		// Специфичные шаблоны поста
		$postSpecialTags = [
			'{{ words'     => 'createWords',
			'{{ sentences' => 'generateSentences',
			'{{ paragraph' => 'generateParagraph',
			'{{ text'      => 'generateText',
			'{{ datetime'  => 'generateDateTime',
		];
		foreach ($postSpecialTags as $needle => $function) {
			if (str_contains($tag, $needle)) {
				$tag = $function($tag);
			}
		}
	}

	return $tag;
}

/**
 * Обрабатывает текстовый шаблон, используя специфицированные преобразования для шаблонов типа "user".
 *
 * @param string $tag Шаблон, который необходимо обработать.
 *
 * @return mixed Обработанный шаблон со значениями, соответствующими типу "user".
 *
 * @see parseFaker()
 */
function parseUserValues(string $tag) {
	return parseFaker($tag, 'user');
}

/**
 * Разбирает значения новостей, используя указанный тег.
 *
 * @param string $tag Тег, который будет использоваться для разбора значений.
 *
 * @return mixed Результат выполнения функции parseFaker.
 *
 * @see parseFaker()
 */
function parseNewsValues(string $tag) {
	return parseFaker($tag, 'post');
}