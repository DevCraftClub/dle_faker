<?php

declare(strict_types=1);

use DevCraft\Types\AdminLink;
use DevCraft\Modules\dle_faker\Pages\TagsPage;
use DevCraft\Modules\dle_faker\Pages\DashboardPage;
use DevCraft\Modules\dle_faker\Pages\SettingsPage;
use DevCraft\Modules\dle_faker\Pages\TemplatesPage;
use DevCraft\Modules\dle_faker\Pages\ChangelogPage;
use DevCraft\Modules\dle_faker\Pages\StaticFilesPage;
use DevCraft\Modules\dle_faker\Pages\GeneratorNewsPage;
use DevCraft\Modules\dle_faker\Pages\GeneratorUsersPage;
use DevCraft\Modules\dle_faker\Pages\GeneratorCategoriesPage;
use DevCraft\Modules\dle_faker\Ajax\SettingsHandler;
use DevCraft\Modules\dle_faker\Ajax\CreateTemplateHandler;
use DevCraft\Modules\dle_faker\Ajax\DeleteTemplateHandler;
use DevCraft\Modules\dle_faker\Ajax\GeneratePostsHandler;
use DevCraft\Modules\dle_faker\Ajax\GenerateUsersHandler;
use DevCraft\Modules\dle_faker\Ajax\GenerateCategoriesHandler;
use DevCraft\Modules\dle_faker\Ajax\ToggleTemplateHandler;
use DevCraft\Modules\dle_faker\Ajax\UploadStaticFileHandler;
use DevCraft\Modules\dle_faker\Ajax\DeleteStaticFileHandler;
use DevCraft\Modules\dle_faker\Ajax\UploadTemplateAssetHandler;
use DevCraft\Modules\dle_faker\Ajax\SaveUserXfieldsHandler;

/**
 * Манифест модуля DLE Faker.
 *
 * Гидрируется в `ModuleManifest` через `ModuleManifest::fromManifest()` — сам
 * файл возвращает массив в форме, ожидаемой этим методом.
 *
 * @return array{
 *     mod: string,
 *     code?: string,
 *     composer_required?: list<array<string, mixed>>,
 *     meta?: array<string, mixed>,
 *     menu?: list<AdminLink>,
 *     ajax?: array{controller?: string, methods?: array<string, class-string>},
 *     changelog?: array<int, array<string, mixed>>,
 *     assets?: array<string, list<string>>,
 * }
 */
return [
	'mod'               => 'dle_faker',
	'code'              => 'dle_faker',
	'composer_required' => [
		['name' => 'fakerphp/faker', 'minVersion' => '^1.23', 'hardRequired' => true],
	],
	'meta'              => [
		'name'        => 'DLE Faker',
		'version'     => '200.1.4',
		'description' => __('Генерация тестовых пользователей, новостей и шаблонов для DLE'),
		'icon'        => 'mif-magic-wand',
		'docsLink'    => 'https://readme.devcraft.club/dev/dle_faker/install/',
		'siteLink'    => 'https://devcraft.club/downloads/dle-faker.29/',
		'siteId'      => 29,
	],
	'menu'              => [
		AdminLink::page(__('Главная'), 'dashboard', DashboardPage::class, 'mif-home', 'dle_faker'),
		new AdminLink(
			name    : __('Генератор'),
			link    : '?mod=dle_faker&action=generator',
			type    : 'dropdown',
			extra   : 'mif-magic-wand',
			children: [
				AdminLink::page(__('Пользователи'), 'generator-users', GeneratorUsersPage::class, 'mif-users', 'dle_faker'),
				AdminLink::page(__('Новости'), 'generator-news', GeneratorNewsPage::class, 'mif-file-text', 'dle_faker'),
				AdminLink::page(__('Категории'), 'generator-categories', GeneratorCategoriesPage::class, 'mif-folder', 'dle_faker'),
			],
			action  : 'generator',
		),
		new AdminLink(
			name    : __('Файлы'),
			link    : '?mod=dle_faker&action=static-images',
			type    : 'dropdown',
			extra   : 'mif-images',
			children: [
				AdminLink::page(__('Изображения'), 'static-images', StaticFilesPage::class, 'mif-image', 'dle_faker'),
				AdminLink::page(__('Файлы'), 'static-files', StaticFilesPage::class, 'mif-file-text', 'dle_faker'),
				AdminLink::page(__('Аудио'), 'static-audio', StaticFilesPage::class, 'mif-file-music', 'dle_faker'),
				AdminLink::page(__('Видео'), 'static-video', StaticFilesPage::class, 'mif-file-video', 'dle_faker'),
			],
			action  : 'files',
		),
		AdminLink::page(__('Шаблоны'), 'templates', TemplatesPage::class, 'mif-files-empty', 'dle_faker'),
		AdminLink::page(__('Теги'), 'tags', TagsPage::class, 'mif-price-tags', 'dle_faker'),
		AdminLink::page(__('Настройки'), 'settings', SettingsPage::class, 'mif-cog', 'dle_faker'),
		AdminLink::page(__('Журнал изменений'), 'changelog', ChangelogPage::class, 'mif-library', 'dle_faker'),
	],
	'ajax'              => [
		'controller' => 'admin',
		'methods'    => [
			'settings'              => SettingsHandler::class,
			'create_template'       => CreateTemplateHandler::class,
			'delete_template'       => DeleteTemplateHandler::class,
			'toggle_template'       => ToggleTemplateHandler::class,
			'generate_users'        => GenerateUsersHandler::class,
			'generate_posts'        => GeneratePostsHandler::class,
			'generate_categories'   => GenerateCategoriesHandler::class,
			'upload_static_file'    => UploadStaticFileHandler::class,
			'delete_static_file'    => DeleteStaticFileHandler::class,
			'upload_template_asset' => UploadTemplateAssetHandler::class,
			'save_user_xfields'     => SaveUserXfieldsHandler::class,
		],
	],
	'changelog'         => require DLEPlugins::Check(__DIR__ . '/changelog.data.php'),
	'assets'            => [
		'js' => ['dle_faker.js'],
	],
];
