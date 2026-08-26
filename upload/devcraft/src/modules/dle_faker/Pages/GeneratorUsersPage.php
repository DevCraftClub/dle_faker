<?php

declare(strict_types=1);

namespace DevCraft\Modules\dle_faker\Pages;

use DevCraft\Modules\dle_faker\DleFakerIdentity;

use DevCraft\Core\Application;
use DevCraft\Core\Abstracts\AbstractPage;
use DevCraft\Core\Support\DataManager;
use DevCraft\Modules\dle_faker\Models\FakerStaticFile;
use DevCraft\Modules\dle_faker\Repositories\FakerStaticFileRepository;
use DevCraft\Modules\dle_faker\Services\ConfigNormalizer;
use DevCraft\Modules\dle_faker\Services\XfieldFormService;
use DevCraft\Core\Support\DleDataService;

/**
 * Страница генерации пользователей.
 */
final class GeneratorUsersPage extends AbstractPage {

	public function handle(): array {
		$this->addBreadcrumb(__('Генератор'), '?mod=dle_faker&action=generator-users');
		$this->addBreadcrumb(__('Пользователи'));

		$config = (new ConfigNormalizer())->normalize(DataManager::getConfig(DleFakerIdentity::code()));
		$schema  = DleDataService::userXfields();

		/** @var FakerStaticFileRepository $staticRepo */
		$staticRepo = Application::instance()->database()->repository(FakerStaticFile::class);
		$images     = array_map(static fn(FakerStaticFile $f): array => [
			'id'            => $f->id(),
			'original_name' => $f->original_name,
		], $staticRepo->findByKind('image'));
		$files = array_map(static fn(FakerStaticFile $f): array => [
			'id'            => $f->id(),
			'original_name' => $f->original_name,
		], $staticRepo->findByKind('file'));
		$audios = array_map(static fn(FakerStaticFile $f): array => [
			'id'            => $f->id(),
			'original_name' => $f->original_name,
		], $staticRepo->findByKind('audio'));
		$videos = array_map(static fn(FakerStaticFile $f): array => [
			'id'            => $f->id(),
			'original_name' => $f->original_name,
		], $staticRepo->findByKind('video'));

		$xfieldFields = $schema === [] ? [] : (new XfieldFormService())->buildFields(
			$schema,
			is_array($config['user_xfields'] ?? null) ? $config['user_xfields'] : [],
			false,
			$images,
			$files,
			$audios,
			$videos,
		);

		return [
			'view' => 'dle_faker/generator_users.twig',
			'data' => [
				'page_title'    => __('Пользователи'),
				'groups'        => DleDataService::groups(),
				'xfield_fields' => $xfieldFields,
				'xfields_display_mode' => $config['xfields_display_mode'],
			],
		];
	}

}
