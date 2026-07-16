<?php

declare(strict_types=1);

use DevCraft\Types\AdminLink;
use DevCraft\Modules\dle_faker\Pages\TagsPage;
use DevCraft\Modules\dle_faker\Pages\DashboardPage;
use DevCraft\Modules\dle_faker\Pages\SettingsPage;
use DevCraft\Modules\dle_faker\Pages\TemplatesPage;
use DevCraft\Modules\dle_faker\Pages\GeneratorPage;
use DevCraft\Modules\dle_faker\Pages\ChangelogPage;
use DevCraft\Modules\dle_faker\Pages\StaticFilesPage;
use DevCraft\Modules\dle_faker\Pages\GeneratorNewsPage;
use DevCraft\Modules\dle_faker\Pages\GeneratorUsersPage;
use DevCraft\Modules\dle_faker\Ajax\SettingsHandler;
use DevCraft\Modules\dle_faker\Ajax\CreateTemplateHandler;
use DevCraft\Modules\dle_faker\Ajax\DeleteTemplateHandler;
use DevCraft\Modules\dle_faker\Ajax\GeneratePostsHandler;
use DevCraft\Modules\dle_faker\Ajax\GenerateUsersHandler;
use DevCraft\Modules\dle_faker\Ajax\ToggleTemplateHandler;
use DevCraft\Modules\dle_faker\Ajax\UploadStaticFileHandler;
use DevCraft\Modules\dle_faker\Ajax\DeleteStaticFileHandler;
use DevCraft\Modules\dle_faker\Ajax\UploadTemplateAssetHandler;
use DevCraft\Modules\dle_faker\Ajax\SaveUserXfieldsHandler;

/**
 * Манифест модуля DLE Faker.
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
		'docsLink'    => 'https://readme.devcraft.club/',
		'siteLink'    => 'https://devcraft.club/',
		'siteId'      => 29,
		'author'      => [
			'name'     => 'Maxim Harder',
			'contacts' => [
				['name' => __('E-Mail'), 'link' => 'mailto:dev@devcraft.club'],
				['name' => __('Telegram'), 'link' => 'https://t.me/MaHarder'],
			],
		],
	],
	'menu'              => [
		AdminLink::page(__('Главная'), 'dashboard', DashboardPage::class, 'mif-home', 'dle_faker'),
		new AdminLink(
			name    : __('Генератор'),
			link    : '?mod=dle_faker&action=generator',
			type    : 'dropdown',
			extra   : 'mif-magic-wand',
			action  : 'generator',
			children: [
				AdminLink::page(__('Генератор пользователей'), 'generator-users', GeneratorUsersPage::class, 'mif-users', 'dle_faker'),
				AdminLink::page(__('Генератор новостей'), 'generator-news', GeneratorNewsPage::class, 'mif-file-text', 'dle_faker'),
				AdminLink::page(__('Шаблоны'), 'templates', TemplatesPage::class, 'mif-files-empty', 'dle_faker'),
				AdminLink::page(__('Статичные файлы'), 'static-files', StaticFilesPage::class, 'mif-images', 'dle_faker'),
			],
		),
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
