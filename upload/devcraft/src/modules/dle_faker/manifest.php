<?php

declare(strict_types=1);

use DevCraft\Modules\dle_faker\DleFakerIdentity;

use DevCraft\Types\AdminLink;
use DevCraft\Types\ModuleManifest;
use DevCraft\Builders\ComposerTypeBuilder;
use DevCraft\Builders\ModuleAssetsBuilder;
use DevCraft\Builders\ModuleManifestBuilder;
use DevCraft\Builders\ModuleAjaxConfigBuilder;
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
 * Манифест модуля DLE Faker (fluent ModuleManifestBuilder).
 *
 * @package    DevCraft
 * @since      200.1.4
 * @subpackage Modules.dle_faker
 *
 * @return ModuleManifest
 */
return ModuleManifestBuilder::create()
	->mod(DleFakerIdentity::mod())
	->code(DleFakerIdentity::code())
	->name('DLE Faker')
	->version('200.1.5')
	->description(__('Генерация тестовых пользователей, новостей и шаблонов для DLE'))
	->icon('mif-magic-wand')
	->docsLink('https://readme.devcraft.club/dev/dle_faker/install/')
	->siteLink('https://devcraft.club/downloads/dle-faker.29/')
	->siteId(29)
	->menu([
		AdminLink::page(__('Главная'), 'dashboard', DashboardPage::class, 'mif-home', DleFakerIdentity::mod()),
		new AdminLink(
			name    : __('Генератор'),
			link    : '?mod=' . DleFakerIdentity::mod() . '&action=generator',
			type    : 'dropdown',
			extra   : 'mif-magic-wand',
			children: [
				AdminLink::page(__('Пользователи'), 'generator-users', GeneratorUsersPage::class, 'mif-users', DleFakerIdentity::mod()),
				AdminLink::page(__('Новости'), 'generator-news', GeneratorNewsPage::class, 'mif-file-text', DleFakerIdentity::mod()),
				AdminLink::page(__('Категории'), 'generator-categories', GeneratorCategoriesPage::class, 'mif-folder', DleFakerIdentity::mod()),
			],
			action  : 'generator',
		),
		new AdminLink(
			name    : __('Файлы'),
			link    : '?mod=' . DleFakerIdentity::mod() . '&action=static-images',
			type    : 'dropdown',
			extra   : 'mif-images',
			children: [
				AdminLink::page(__('Изображения'), 'static-images', StaticFilesPage::class, 'mif-image', DleFakerIdentity::mod()),
				AdminLink::page(__('Файлы'), 'static-files', StaticFilesPage::class, 'mif-file-text', DleFakerIdentity::mod()),
				AdminLink::page(__('Аудио'), 'static-audio', StaticFilesPage::class, 'mif-file-music', DleFakerIdentity::mod()),
				AdminLink::page(__('Видео'), 'static-video', StaticFilesPage::class, 'mif-file-video', DleFakerIdentity::mod()),
			],
			action  : 'files',
		),
		AdminLink::page(__('Шаблоны'), 'templates', TemplatesPage::class, 'mif-files-empty', DleFakerIdentity::mod()),
		AdminLink::page(__('Теги'), 'tags', TagsPage::class, 'mif-price-tags', DleFakerIdentity::mod()),
		AdminLink::page(__('Настройки'), 'settings', SettingsPage::class, 'mif-cog', DleFakerIdentity::mod()),
		AdminLink::page(__('Журнал изменений'), 'changelog', ChangelogPage::class, 'mif-library', DleFakerIdentity::mod()),
	])
	->ajax(
		ModuleAjaxConfigBuilder::create('admin')
			->methods([
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
			])
	)
	->composerRequired([
		ComposerTypeBuilder::create('fakerphp/faker')->minVersion('^1.23')->hardRequired()->build(),
	])
	->changelog(require DLEPlugins::Check(__DIR__ . '/changelog.data.php'))
	->assets(ModuleAssetsBuilder::create()->js('dle_faker.js'))
	->build(__DIR__);
