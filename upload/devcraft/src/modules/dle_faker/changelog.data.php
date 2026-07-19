<?php

declare(strict_types=1);

/**
 * Журнал изменений dle_faker.
 *
 * Гидрируется в `Changelog[]` через `Changelog::listFromManifest()` /
 * `ModuleManifest::fromManifest()` — сам файл возвращает массив массивов
 * (`items` — legacy-строки `[TYPE] текст`, `changes` — карта тип → список строк).
 *
 * @return array<int, array{version: string, date?: string, items?: list<string>, changes?: array<string, list<string>>}>
 */
return [
	[
		'version' => '200.1.4',
		'date'    => '2026-07-16',
		'changes' => [
			'added'   => [
				__('Каркас сателлитного модуля DLE Faker для DevCraft Admin и DLE 20.0.'),
				__('Страницы панели управления: главная, генератор, теги, настройки и журнал изменений.'),
				__('Поддержка конфигурации `dle_faker.json`, локалей XLIFF и таблицы `faker_templates`.'),
				__('Type-aware доп. поля новостей и пользователей, библиотека статичных файлов, вложения шаблонов.'),
				__('Теги `static_image` / `static_file` и запись xfields в формате DLE 20.'),
			],
			'changed' => [
				__('Навигация перегруппирована: шаблоны перенесены внутрь раздела «Генератор».'),
				__("Точка входа переведена на `runAdmin(moduleDir: 'dle_faker')` и централизованный `devcraft/ajax.php`."),
				__('Удалены leftover-пути MHAdmin (`engine/inc/maharder`, `engine/ajax/maharder`) и мёртвая страница индекса генератора.'),
				__('В шаблонах новостей оставлены только `date_from` / `date_to`; поле `allow_rss_turbo` убрано (нет в DLE 20).'),
				__('Multipart-загрузка вынесена в ядро DevCraft (`DevCraftAjax.postMultipart`, `UploadedFile`).'),
				__('В шаблоне доступно поле «Категорий на новость»; для imagegalery/video/audio/file — случайный плейлист/галерея с clamp по пулу и схеме xfield.'),
				__('Настройка «Категорий на новость» убрана из settings — только в шаблоне.'),
			],
			'fixed'   => [
				__('Совместимость схемы настроек с полями `multi` и нормализацией legacy-строк/массивов.'),
				__('Отображение журнала изменений и регистрация обязательных метаданных модуля.'),
				__('Генерация новостей: INSERT в `post_extras` выровнен под схему DLE 20 (`allow_rss` / `allow_rss_dzen` / geo-поля).'),
				__('Ошибки SQL при генерации новостей возвращаются как JSON, а не HTML MySQL Fatal Error (HTTP 503).'),
				__('Вложения шаблона переносятся на диск при сохранении (`templates/{id}/`); форматы video/audio/file соответствуют DLE 20.'),
				__('Пустые media-xfields не валят генерацию; video/audio регистрируются в `_files` с реальным id.'),
				__('Источник media (библиотека / файл шаблона) больше не сбрасывается в «Текст / теги» при сохранении.'),
				__('Откат частично созданной новости при ошибке публикации xfields.'),
			],
		],
	],
	[
		'version' => '180.1.3',
		'items'   => [
			'[CHANGED] Последний legacy-релиз MHAdmin перед миграцией в DevCraft.',
		],
	],
	[
		'version' => '180.1.0',
		'items'   => [
			'[ADDED] Первичная публикация DLE Faker для MHAdmin.',
		],
	],
];
