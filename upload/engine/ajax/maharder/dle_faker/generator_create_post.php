<?php
global $is_logged, $dle_login_hash, $parsedData, $MHDB, $mh_admin, $db, $config, $fakerConfig, $lang, $_TIME, $_IP;

if (!$is_logged) {
	exit('error');
}

if ('' == $_REQUEST['user_hash'] or $_REQUEST['user_hash'] != $dle_login_hash) {
	exit('error');
}

require_once DLEPlugins::Check(__DIR__ . '/parse_content.php');

$filter = [
	'template' => FILTER_VALIDATE_INT,
	'count'    => FILTER_VALIDATE_INT
];

$inputData = filter_var_array($parsedData, $filter);

if (!$inputData['template']) {
	echo (new ErrorResponseAjax())
		->setData([__('Шаблон не может быть пустым')])->setMeta(['template'])->send();
	exit;
}
if (!$inputData['count']) $inputData['count'] = 1;

try {
	$templateData = $MHDB->get(FakerTemplate::class, $inputData['template']);

	if (!$templateData) {
		echo (new ErrorResponseAjax(404))->setData([__('Такого шаблона не существует')])->send();
		exit;
	}

	$template = json_decode($templateData->template, true);

	$parse = new ParseFilter();

	$title          = $parse->process(
		filter_var(parseNewsValues($template['title']), FILTER_SANITIZE_FULL_SPECIAL_CHARS)
	);
	$alt_name       = totranslit(stripslashes($title), true, false, $config['translit_url']);
	$author         = filter_var(parseNewsValues($template['autor']), FILTER_VALIDATE_INT);
	$category       = getRandomValue(
		explode(',', filter_var(parseNewsValues($template['category']), FILTER_SANITIZE_FULL_SPECIAL_CHARS)),
		(int)$fakerConfig['categories_count']
	);
	$categoryString = $db->safesql( implode(',', (array) $category) );

	$date_from = !empty($template['date_from_alt']) ? $template['date_from_alt'] : $template['date_from'];
	$date_to   = !empty($template['date_to_alt']) ? $template['date_to_alt'] : $template['date_to'];
	$date      = getRandomDateBetween(parseNewsValues($date_from), parseNewsValues($date_to));

	$short_story = $parse->process(
		filter_var(parseNewsValues($template['short_story']), FILTER_SANITIZE_FULL_SPECIAL_CHARS)
	);
	$full_story  = $parse->process(
		filter_var(parseNewsValues($template['full_story']), FILTER_SANITIZE_FULL_SPECIAL_CHARS)
	);
	if (empty($full_story)) $full_story = $short_story;



	$xfields = [];

	foreach ($template['xfields'] as $field => $value) {
		if (!empty($value)) $xfields[$field] = parseNewsValues($value);
	}
	$xfields_string = implode('||', array_map(fn($k, $v) => "{$k}|{$v}", array_keys($xfields), $xfields));

	$allow_main      = parseBoolValue($template['allow_main']);
	$approve         = parseBoolValue($template['approve']);
	$fixed           = parseBoolValue($template['fixed']);
	$allow_comm      = parseBoolValue($template['allow_comm']);
	$allow_rate      = parseBoolValue($template['allow_rate']);
	$disable_index   = parseBoolValue($template['disable_index']);
	$disable_search  = parseBoolValue($template['disable_search']);
	$allow_rss       = parseBoolValue($template['allow_rss']);
	$allow_rss_turbo = parseBoolValue($template['allow_rss_turbo']);
	$allow_rss_dzen  = parseBoolValue($template['allow_rss_dzen']);

	$title          = $db->safesql($title);
	$alt_name       = $db->safesql($alt_name);

	if ($config['allow_alt_url'] && !$config['seo_type']) {
		$altCounter = 1;
		$originalAltName = $alt_name;

		do {
			// Используем подготовленные запросы для повышения безопасности
			$stmt = $db->query("SELECT id FROM " . PREFIX . "_post WHERE alt_name = '" . $db->safesql($alt_name) . "'");

			$foundNews = $db->get_row($stmt);

			if ($foundNews) {
				$alt_name = $originalAltName . '_' . $altCounter; // Конкатенация только при необходимости
				$altCounter++;
			}
		} while ($foundNews);
	}

	if ($config['allow_admin_wysiwyg']) {
		$full_story  = $db->safesql($parse->BB_Parse($full_story));
		$short_story = $db->safesql($parse->BB_Parse($short_story));
	} else {
		$full_story  = $db->safesql($parse->BB_Parse($full_story, false));
		$short_story = $db->safesql($parse->BB_Parse($short_story, false));
	}

	if( dle_strlen($full_story) > 12 ) $metatags = create_metatags( $full_story );
	else $metatags = create_metatags( $short_story );

	$catalog_url = $config['create_catalog'] ? $db->safesql( dle_substr( htmlspecialchars( strip_tags( stripslashes( $title ) ), ENT_QUOTES, 'UTF-8' ), 0, 1 ) ) : '';

	$user = $mh_admin->getUser($author);
	$exclAuthor = [strval($author)];

	while (!$user) {
		$author         = filter_var(getRandomValue(explode(',', $template['autor']), exclude: $exclAuthor), FILTER_VALIDATE_INT);
		$user = $mh_admin->getUser($author);
		$exclAuthor[] = strval($author);
	}

	try {
		$db->query( "INSERT INTO " . PREFIX . "_post (date, autor, short_story, full_story, xfields, title, descr, keywords, category, alt_name, allow_comm, approve, allow_main, fixed, allow_br, symbol, tags, metatitle) values ('{$date}', '{$user['name']}', '{$short_story}', '{$full_story}', '{$xfields_string}', '{$title}', '{$metatags['description']}', '{$metatags['keywords']}', '{$categoryString}', '{$alt_name}', '{$allow_comm}', '{$approve}', '{$allow_main}', '{$fixed}', '1', '$catalog_url', '', '{$metatags['title']}')" );

		$postId = (int) $db->insert_id();

		$db->query( "INSERT INTO " . PREFIX . "_post_extras (news_id, allow_rate, votes, disable_index, related_ids, access, user_id, disable_search, need_pass, allow_rss, allow_rss_turbo, allow_rss_dzen) VALUES('{$postId}', '{$allow_rate}', 0, '{$disable_index}', '', '', '{$user['user_id']}', '{$disable_search}', '0', '{$allow_rss}', '{$allow_rss_turbo}', '{$allow_rss_dzen}')" );

		$catsIds = [];
		foreach ( (array)$category as $catId ) {
			$catId = (int) $catId;
			$catsIds[] = "({$postId}, {$catId})";
		}
		$catsIds = implode( ", ", (array) $catsIds );
		$db->query( "INSERT INTO " . PREFIX . "_post_extras_cats (news_id, cat_id) VALUES {$catsIds}" );

		$db->query( "UPDATE " . USERPREFIX . "_users SET news_num=news_num+1 WHERE user_id='{$user['user_id']}'" );
		$db->query( "INSERT INTO " . USERPREFIX . "_admin_logs (name, date, ip, action, extras) values ('".$db->safesql($user['name'])."', '{$_TIME}', '{$_IP}', '1', '{$title}')" );


		$xfSearch = [];
		foreach ($xfields as $n => $v) {
			$xf = $mh_admin->getXfieldInfo($n);
			if ((bool) $xf['is_link']) {
				$vs = explode(',', filter_var($v, FILTER_SANITIZE_FULL_SPECIAL_CHARS));
				foreach($vs as $x) $xfSearch[] = "({$postId}, '{$n}', '{trim($x)}')";
			}
		}
		$xfSearchWords = implode( ", ", $xfSearch );
		$db->query( "INSERT INTO " . PREFIX . "_xfsearch (news_id, tagname, tagvalue) VALUES {$xfSearchWords}" );


		clear_cache( array('news_', 'tagscloud_', 'archives_', 'calendar_', 'topnews_', 'rss', 'stats') );


		// Получаем список категорий
		$catData = $mh_admin->getCats();

		// Убедимся, что $category приведён к массиву, если это необходимо
		$categoryIds = (array)$category;

		// Собираем имена категорий по их ID, игнорируя отсутствующие
		$categoryNames = array_intersect_key($catData, array_flip($categoryIds));

		echo (new SuccessResponseAjax())->setData(
			[
				"id"       => $postId,
				"name"     => $title,
				"category" => implode(', ', $categoryNames),
				"date"     => $date
			]
		)->send();


	} catch (Exception $e) {
		echo (new ErrorResponseAjax())->setData([$e->getMessage()])->send();
		LogGenerator::generateLog(
			'DLE Faker',
			'ajax/generator_create_post/create_post',
			$e->getMessage()
		);
		exit;
	}


} catch (Exception $e) {
	echo (new ErrorResponseAjax())->setData([$e->getMessage()])->send();
	LogGenerator::generateLog(
		'DLE Faker',
		'ajax/generator_create_post/exception',
		$e->getMessage()
	);
} catch (Throwable $e) {
	echo (new ErrorResponseAjax())->setData([$e->getMessage()])->send();
	LogGenerator::generateLog(
		'DLE Faker',
		'ajax/generator_create_post/throwable',
		$e->getMessage()
	);
}
exit;