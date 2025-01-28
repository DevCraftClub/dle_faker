<?php

//===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===
// Mod: DLE Faker
// File: dle_faker.php
// Path: engine/inc/dle_faker.php
// ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  =
// Author: Maxim Harder <dev@devcraft.club> (c) 2025
// Website: https://devcraft.club
// Telegram: http://t.me/MaHarder
// ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  =
// Do not change anything!
//===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===

global $breadcrumbs, $mh, $modVars, $mh_template, $htmlTemplate, $config, $links;

use Symfony\Bridge\Twig\Extension\TranslationExtension;

// заполняем важную и нужную информацию о модуле

$modInfo = [
	'module_name'        => 'DLE Faker',
	'module_version'     => '173.1.0',
	'module_description' => __('Генерирует случайные данные для наполнения сайта'),
	'module_code'        => 'dle_faker',
	'module_id'          => 29,
	'module_icon'        => 'engine/inc/maharder/_modules/dle_faker/assets/icon.png',
	'site_link'          => 'https://devcraft.club/downloads/dle-faker.29/',
	'docs_link'          => 'https://readme.devcraft.club/latest/dev/dle_faker/install/',
	'dle_config'         => $config,
	'crowdin_name'       => 'dle_faker',
	'crowdin_stat_id'    => '16830581-755469'
];

// Подключаем классы, функции и основные переменные
require_once DLEPlugins::Check(__DIR__ . '/maharder/admin/index.php');
require_once DLEPlugins::Check(MH_ROOT . '/_modules/dle_faker/utils/init.php');

$mh->setLink(
	new AdminLink(
		'template', __('Шаблоны'), '?mod=' . $modInfo['module_code'] . '&sites=template'
	),
	'template'
);
$mh->setLink(
	new AdminLink(
		'generator', __('Генератор'),  '?mod=' . $modInfo['module_code'] . '&sites=generator'
	),
	'generator'
);
$mh->setLink(
	new AdminLink(
		'tags', __('Теги'), '?mod=' . $modInfo['module_code'] . '&sites=tags'
	),
	'tags'
);

// Подключаем переменные модуля и его функционал
// Используем переменную sites для навигации в модуле
switch ($_GET['sites']) {
	// Главная страница
	default:
		require_once DLEPlugins::Check(MH_ROOT . '/_modules/' . $modInfo['module_code'] . '/module/main.php');
		break;

	case 'changelog':
		require_once DLEPlugins::Check(MH_ROOT . '/_modules/' . $modInfo['module_code'] . '/module/changelog.php');
		break;

	case 'template':
		require_once DLEPlugins::Check(MH_ROOT . '/_modules/' . $modInfo['module_code'] . '/module/templates.php');
		break;

	case 'generator':
		require_once DLEPlugins::Check(MH_ROOT . '/_modules/' . $modInfo['module_code'] . '/module/generator.php');
		break;

	case 'tags':
		require_once DLEPlugins::Check(MH_ROOT . '/_modules/' . $modInfo['module_code'] . '/module/tags.php');
		break;
}

$xtraVariable = [
	'breadcrumbs' => $mh->getBreadcrumb(),
	'settings'    => DataManager::getConfig($modInfo['module_code']),
	'links'		  => $mh->getVariables('menu')
];

$mh->setVars($modInfo);
$mh->setVars($xtraVariable);
$mh->setVars($modVars);

$mh_template->addExtension(new TranslationExtension(MhTranslation::getTranslator()));

// Загружаем шаблон
$template = $mh_template->load($htmlTemplate);

// Отображаем всё на сайте
// При помощи array_merge() можно объединить любое кол-во массивов
echo $template->render($mh->getVariables());
