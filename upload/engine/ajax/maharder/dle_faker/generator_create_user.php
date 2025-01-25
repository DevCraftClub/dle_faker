<?php
global $is_logged, $dle_login_hash, $parsedData, $MHDB, $mh_admin, $db, $config, $fakerConfig;


if (!$is_logged) {
	exit('error');
}

if ('' == $_REQUEST['user_hash'] or $_REQUEST['user_hash'] != $dle_login_hash) {
	exit('error');
}

require_once DLEPlugins::Check(__DIR__ . '/parse_content.php');

$filter = [
	'name'      => FILTER_SANITIZE_FULL_SPECIAL_CHARS | FILTER_FLAG_EMPTY_STRING_NULL,
	'full_name' => FILTER_SANITIZE_FULL_SPECIAL_CHARS | FILTER_FLAG_EMPTY_STRING_NULL,
	'email'     => FILTER_SANITIZE_FULL_SPECIAL_CHARS | FILTER_FLAG_EMPTY_STRING_NULL,
	'password'  => FILTER_SANITIZE_FULL_SPECIAL_CHARS | FILTER_FLAG_EMPTY_STRING_NULL,
	'usergroup' => FILTER_SANITIZE_FULL_SPECIAL_CHARS | FILTER_FLAG_EMPTY_STRING_NULL,
];

$inputData            = filter_var_array($parsedData, $filter);

if (empty($inputData['name'])) {
	echo (new ErrorResponseAjax())->setData([__('dle_faker', 'Шаблон для псевдонима не может быть пустым')])
								  ->setMeta(['name'])
								  ->send();
	exit;
}

if (empty($inputData['email'])) {
	echo (new ErrorResponseAjax())->setData([__('dle_faker', 'Шаблон для электронной почты не может быть пустым')])
								  ->setMeta(['email'])
								  ->send();
	exit;
}

if (empty($inputData['password'])) {
	echo (new ErrorResponseAjax())->setData([__('dle_faker', 'Шаблон для пароля не может быть пустым')])
								  ->setMeta(['password'])
								  ->send();
	exit;
}

if(!$inputData['usergroup']) $inputData['usergroup'] = [4];
else $inputData['usergroup'] = explode(',', $inputData['usergroup']);

$usergroup = (int) getRandomValue($inputData['usergroup']);

try {
	if(!class_exists('DLE_API')) {
		require_once ENGINE_DIR . '/api/api.class.php';
	}
	$dle_api = new DLE_API();
	$dle_api->dle_config = $config;
	$dle_api->db = $db;

	$username = DataManager::toTranslit(parseUserValues($inputData['name']), false);
	$full_name = parseUserValues($inputData['full_name']);
	$email = explode('@',parseUserValues($inputData['email']));
	$email = DataManager::toTranslit($email[0], false) . '@' . $email[1];

	$create_user = $dle_api->external_register($username,$inputData['password'], $email, $usergroup);

	if ($create_user === 1) {
		$user = $dle_api->take_user_by_email($email, 'user_id, name, email, fullname');
		if ($user) {

			$db->query("UPDATE " . USERPREFIX . "_users SET fullname = '{$full_name}' WHERE user_id = {$user['user_id']}");

			echo (new SuccessResponseAjax())->setData(
				[
					'id' => (int) $user['user_id'],
					'username' => $user['name'],
					'full_name' => $full_name,
					'email' => $email,
					'user_group' => $mh_admin->getUserGroups()[$usergroup],
				]
			)->send();
		} else {
			echo (new ErrorResponseAjax(404))->setData([__('dle_faker', 'Пользователь не был найден!')])->send();
		}
	} elseif ($create_user === -1) {
		echo (new ErrorResponseAjax())->setData([__('dle_faker', 'Псевдоним пользователя (:uname) уже занят!', [':uname' => $username])])->send();
	} elseif ($create_user === -2) {
		echo (new ErrorResponseAjax())->setData([__('dle_faker', 'Электронная почта (:email) пользователя уже занята!', [':email' => $email])])->send();
	} elseif ($create_user === -3) {
		echo (new ErrorResponseAjax())->setData([__('dle_faker', 'Электронная почта (:email) не подлежит нужному формату!', [':email' => $email])])->send();
	} elseif ($create_user === -4) {
		echo (new ErrorResponseAjax())->setData([__('dle_faker', 'Установленной группы (:group) не существует!', [':group' => $usergroup])])->send();
	}

} catch (Exception $e) {
	echo (new ErrorResponseAjax())->setData([$e->getMessage()])->send();
	LogGenerator::generateLog(
		'DLE Faker',
		'ajax/generator_create_users',
		$e->getMessage()
	);
}
exit;