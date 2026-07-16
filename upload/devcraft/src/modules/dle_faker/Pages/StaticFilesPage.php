<?php

declare(strict_types=1);

namespace DevCraft\Modules\dle_faker\Pages;

use DateTimeImmutable;
use DevCraft\Core\Abstracts\AbstractPage;
use DevCraft\Core\Application;
use DevCraft\Modules\dle_faker\Models\FakerStaticFile;
use DevCraft\Modules\dle_faker\Repositories\FakerStaticFileRepository;

/**
 * Страница библиотеки статичных файлов.
 */
final class StaticFilesPage extends AbstractPage {

	public function handle(): array {
		$this->addBreadcrumb(__('Генераторы'), '?mod=dle_faker&action=generator');
		$this->addBreadcrumb(__('Статичные файлы'));

		/** @var FakerStaticFileRepository $repo */
		$repo = Application::instance()->database()->repository(FakerStaticFile::class);

		$images = array_map(static fn(FakerStaticFile $f): array => [
			'id'            => $f->id,
			'original_name' => $f->original_name,
			'mime'          => $f->mime,
			'size_bytes'    => $f->size_bytes,
			'created_at'    => $f->created_at instanceof DateTimeImmutable ? $f->created_at->format('Y-m-d H:i') : '',
		], $repo->findByKind('image'));

		$files = array_map(static fn(FakerStaticFile $f): array => [
			'id'            => $f->id,
			'original_name' => $f->original_name,
			'mime'          => $f->mime,
			'size_bytes'    => $f->size_bytes,
			'created_at'    => $f->created_at instanceof DateTimeImmutable ? $f->created_at->format('Y-m-d H:i') : '',
		], $repo->findByKind('file'));

		return [
			'view' => 'dle_faker/static_files.twig',
			'data' => [
				'page_title' => __('Статичные файлы'),
				'images'     => $images,
				'files'      => $files,
			],
		];
	}

}
