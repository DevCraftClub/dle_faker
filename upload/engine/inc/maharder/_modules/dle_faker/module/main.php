<?php

global $mh;

$available_languages = include DLEPlugins::Check(MH_MODULES . '/dle_faker/utils/faker_lang.php');
$fakerConfig         = DataManager::getConfig('dle_faker');

$modVars = [
	'title'               => __('Настройки модуля'),
	'users'               => $mh->getUsers(),
	'categories'          => $mh->getCats(),
	'available_languages' => $available_languages,
	'settings'            => $fakerConfig
];

$htmlTemplate = 'dle_faker/main.html';