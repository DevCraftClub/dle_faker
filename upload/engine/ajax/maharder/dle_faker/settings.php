<?php

global $is_logged, $dle_login_hash, $parsedData, $MHDB, $method;

if (!$is_logged) {
	exit('error');
}

if ('' == $_REQUEST['user_hash'] or $_REQUEST['user_hash'] != $dle_login_hash) {
	exit('error');
}

try {
	$configFile = MH_CONFIG . '/dle_faker.json';

	$filteredData = filter_var_array($parsedData, [
		'language'         => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
		'users'            => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
		'categories'       => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
		'categories_count' => FILTER_VALIDATE_INT,
	]);

	if (empty($filteredData['language']) || $filteredData['language'] === 'site') $filteredData['language'] = MhTranslation::getLocale();

	if(empty($filteredData['users'])) {
		echo (new ErrorResponseAjax())->setData([__('Нужно указать хотя бы одного пользователя!')])
									  ->setMeta(['users'])
									  ->send();
		exit;
	}

	if(empty($filteredData['categories'])) {
		echo (new ErrorResponseAjax())->setData([__('Нужно указать хотя бы одну категорию!')])
									  ->setMeta(['categories'])
									  ->send();
		exit;
	}

	if(!$filteredData['categories_count'] || $filteredData['categories_count'] === 0) $filteredData['categories_count'] = 1;

	file_put_contents($configFile, json_encode($filteredData, JSON_UNESCAPED_UNICODE));
	clear_cache();

	echo (new SuccessResponseAjax())->setData([__('Настройки сохранены')])->send();
} catch (Exception $e) {
	echo (new ErrorResponseAjax())->setData([__('Ошибка в сохранении настроек'), $e->getMessage()])->send();
}

exit;