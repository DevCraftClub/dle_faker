<?php

declare(strict_types=1);

namespace DevCraft\Modules\dle_faker\Services;

use DevCraft\Modules\dle_faker\Models\FakerStaticFile;
use DevCraft\Modules\dle_faker\Models\FakerTemplateAsset;
use RuntimeException;

/**
 * Копирует файл библиотеки/вложения в дерево uploads DLE для xfields.
 */
final class StaticFilePublisher {

	public function __construct(
		private readonly StaticFileStorage $storage = new StaticFileStorage(),
	) {}

	public function publishStatic(FakerStaticFile $file, string $fieldType): string {
		$source = $this->storage->libraryAbsolutePath($file->kind, $file->stored_name);

		if(!is_file($source)) {
			throw new RuntimeException(__('Файл библиотеки не найден на диске'));
		}

		return $this->publishPath($source, $file->original_name, $fieldType);
	}

	public function publishTemplateAsset(FakerTemplateAsset $asset, string $fieldType): string {
		$source = $this->storage->templateAbsolutePath($asset->template_id, $asset->stored_name);

		if(!is_file($source)) {
			throw new RuntimeException(__('Вложение шаблона не найдено на диске'));
		}

		return $this->publishPath($source, $asset->original_name, $fieldType);
	}

	private function publishPath(string $source, string $originalName, string $fieldType): string {
		[, $diskDir] = $this->targetDirs($fieldType);
		$prefix      = date('Y-m') . '/';
		$targetDir   = rtrim(ROOT_DIR, '/') . '/uploads/' . $diskDir . $prefix;

		if(!is_dir($targetDir) && !mkdir($targetDir, 0777, true) && !is_dir($targetDir)) {
			throw new RuntimeException(__('Не удалось создать каталог публикации DLE'));
		}

		$ext      = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
		$basename = bin2hex(random_bytes(8)) . ($ext !== '' ? '.' . $ext : '');
		$target   = $targetDir . $basename;

		if(!copy($source, $target)) {
			throw new RuntimeException(__('Не удалось скопировать файл в uploads DLE'));
		}

		return $prefix . $basename;
	}

	/**
	 * @return array{0: string, 1: string}
	 */
	private function targetDirs(string $fieldType): array {
		return match ($fieldType) {
			'file', 'video', 'audio' => ['public_files', 'public_files/'],
			default                  => ['posts', 'posts/'],
		};
	}

}
