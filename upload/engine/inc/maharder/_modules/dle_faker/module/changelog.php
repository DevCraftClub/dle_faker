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
	'180.1.2' => [
		__('[FIX] Исправлена итерация в templates_create.html'),
		__('[FIX] Исправлены языковые функции'),
	],
	'173.1.1' => [
		__('[UPDATE] В файле generator_create_post.php логика вставки данных в базу данных обновлена. Она будет исполняться только в случае, если $xfSearchWords не пуст'),
		__('[UPDATE] В init.php управление зависимостями упрощено с целью сосредоточения на регистрации отсутствуюших зависимостей'),
		__('[UPDATE] HTML-шаблон изменен для улучшения обработки переменных в цикле templates_create.html.'),
		__('[FIX] Исправлена обработка данных при генерации новостей'),
		__('[FIX] Исправлен скрипт установки'),
	],
	'173.1.0' => [
		__('Основной релиз'),
	],
];

$modVars = [
	'title' => __('История изменений'),
	'logs' => $logs,
];

// Настройка хлебных крошек
// Крошки это массив с массивами, которые содержат информацию о ссылке (url) и её названии (name)
// Крошки добавляются в каждом файле модуля с исключением самого главного
$mh->setBreadcrumb(new BreadCrumb($modVars['title'], $mh->getLinkUrl('changelog')));

$htmlTemplate = 'admin/changelog.html';