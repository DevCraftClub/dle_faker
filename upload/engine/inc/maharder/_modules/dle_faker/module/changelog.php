<?php
//===============================================================
// Файл: changelog.php                                          =
// Путь: engine/inc/maharder/admin/modules/admin/changelog.php  =
// Последнее изменение: 2024-03-14 15:42:27                     =
// ==============================================================
// Автор: Maxim Harder <dev@devcraft.club> © 2024               =
// Сайт: https://devcraft.club                                  =
// Телеграм: http://t.me/MaHarder                               =
// ==============================================================
// Менять на свой страх и риск!                                 =
// Код распространяется по лицензии MIT                         =
//===============================================================

global $mh;


$logs = [
	'173.1.0' => [
		__('Основной релиз'),
	],
];

$modVars = [
	'title' => __('История изменений'),
	'module_icon' => 'fad fa-robot',
	'logs' => $logs,
];

// Настройка хлебных крошек
// Крошки это массив с массивами, которые содержат информацию о ссылке (url) и её названии (name)
// Крошки добавляются в каждом файле модуля с исключением самого главного
$mh->setBreadcrumb(new BreadCrumb($modVars['title'], $mh->getLinkUrl('changelog')));

$htmlTemplate = 'admin/changelog.html';