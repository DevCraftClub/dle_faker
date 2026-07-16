<?php

declare(strict_types=1);

namespace DevCraft\Modules\dle_faker\Pages;

use DateTimeImmutable;
use DevCraft\Core\Abstracts\AbstractPage;
use DevCraft\Core\Application;
use DevCraft\Modules\dle_faker\Models\FakerStaticFile;
use DevCraft\Modules\dle_faker\Repositories\FakerStaticFileRepository;
use DevCraft\Modules\dle_faker\Services\StaticFileStorage;

/**
 * Страница библиотеки статичных файлов (один kind на action).
 */
final class StaticFilesPage extends AbstractPage {

	public function handle(): array {
		$storage = new StaticFileStorage();
		$action  = trim((string) ($_GET['action'] ?? 'static-files'));
		$kind    = $storage->kindFromAction($action);
		$titles  = [
			'image' => __('Изображения'),
			'file'  => __('Файлы'),
			'audio' => __('Аудио'),
			'video' => __('Видео'),
		];
		$title = $titles[$kind] ?? __('Файлы');

		$this->addBreadcrumb(__('Файлы'), '?mod=dle_faker&action=static-images');
		$this->addBreadcrumb($title);

		/** @var FakerStaticFileRepository $repo */
		$repo  = Application::instance()->database()->repository(FakerStaticFile::class);
		$items = array_map(static fn(FakerStaticFile $f): array => [
			'id'            => $f->id,
			'original_name' => $f->original_name,
			'mime'          => $f->mime,
			'size_bytes'    => $f->size_bytes,
			'created_at'    => $f->created_at instanceof DateTimeImmutable ? $f->created_at->format('Y-m-d H:i') : '',
		], $repo->findByKind($kind));

		$ext = $storage->allowedExtensionsForKind($kind);

		return [
			'view' => 'dle_faker/static_files.twig',
			'data' => [
				'page_title'   => $title,
				'kind'         => $kind,
				'items'        => $items,
				'allowed_ext'  => $ext,
				'accept'       => $this->acceptAttr($ext),
				'upload_label' => match($kind) {
					'image' => __('Загрузить изображение'),
					'audio' => __('Загрузить аудио'),
					'video' => __('Загрузить видео'),
					default => __('Загрузить файл'),
				},
				'empty_label'  => match($kind) {
					'image' => __('Пока нет изображений'),
					'audio' => __('Пока нет аудио'),
					'video' => __('Пока нет видео'),
					default => __('Пока нет файлов'),
				},
				'drop_title'   => match($kind) {
					'image' => __('Перетащите изображения сюда'),
					'audio' => __('Перетащите аудио сюда'),
					'video' => __('Перетащите видео сюда'),
					default => __('Перетащите файлы сюда'),
				},
			],
		];
	}

	/**
	 * @param list<string> $ext
	 */
	private function acceptAttr(array $ext): string {
		if($ext === []) {
			return '';
		}

		return implode(',', array_map(static fn(string $e): string => '.' . ltrim($e, '.'), $ext));
	}

}
