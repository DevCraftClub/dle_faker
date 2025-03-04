<?php

	//===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===
	// Mod: DLE Faker
	// File: main.php
	// Path: /home/wrw-dev/Dev/Projects/dle171/engine/ajax/maharder/dle_faker/master.php
	// ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  =
	// Author: Maxim Harder <dev@devcraft.club> (c) 2025
	// Website: https://devcraft.club
	// Telegram: http://t.me/MaHarder
	// ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  =
	// Do not change anything!
	//===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===

	if (!defined('DATALIFEENGINE')) {
		header('HTTP/1.1 403 Forbidden');
		header('Location: ../../../../');
		exit('Hacking attempt!');
	}

	if (!$is_logged) {
		exit('error');
	}

	if ('' == $_REQUEST['user_hash'] or $_REQUEST['user_hash'] != $dle_login_hash) {
		exit('error');
	}

	$POST_DATA = filter_input_array(INPUT_POST);
	if (!$POST_DATA) {
		exit();
	}

	$method = $POST_DATA['method'];
	if (!$method) {
		exit();
	}
	parse_str($POST_DATA['data'], $parsedData);
	$fakerConfig = DataManager::getConfig('dle_faker');


	switch ($method) {
		case 'settings':
			require_once DLEPlugins::Check(__DIR__ . '/settings.php');
			break;

		case 'generate_users':
			require_once DLEPlugins::Check(__DIR__ . '/generator_create_user.php');
			break;

		case 'generate_posts':
			require_once DLEPlugins::Check(__DIR__ . '/generator_create_post.php');
			break;

		case 'create_template':
			require_once DLEPlugins::Check(__DIR__ . '/templates_create.php');
			break;

		case 'delete_template':
			require_once DLEPlugins::Check(__DIR__ . '/templates_delete.php');
			break;

		case 'deactivate_template':
		case 'activate_template':
			require_once DLEPlugins::Check(__DIR__ . '/templates_change_status.php');
			break;

	}
