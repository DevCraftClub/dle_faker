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

	/**
	 * @param array<string, mixed> $fieldSchema Схема xfield (is_public и т.п.)
	 */
	public function publishStatic(FakerStaticFile $file, string $fieldType, array $fieldSchema = [], ?int $newsId = null): string {
		$source = $this->storage->libraryAbsolutePath($file->kind, $file->stored_name);

		if(!is_file($source)) {
			throw new RuntimeException(__('Файл библиотеки не найден на диске'));
		}

		return $this->publishPath($source, $file->original_name, $fieldType, $fieldSchema, $newsId);
	}

	/**
	 * @param array<string, mixed> $fieldSchema
	 */
	public function publishTemplateAsset(FakerTemplateAsset $asset, string $fieldType, array $fieldSchema = [], ?int $newsId = null): string {
		$source = $this->storage->templateAbsolutePath($asset->template_id, $asset->stored_name);

		if(!is_file($source)) {
			throw new RuntimeException(__('Вложение шаблона не найдено на диске'));
		}

		return $this->publishPath($source, $asset->original_name, $fieldType, $fieldSchema, $newsId);
	}

	/**
	 * @param array<string, mixed> $fieldSchema
	 */
	private function publishPath(string $source, string $originalName, string $fieldType, array $fieldSchema, ?int $newsId): string {
		if($fieldType === 'file' && $this->isPrivateFile($fieldSchema)) {
			return $this->publishPrivateAttachment($source, $originalName, $newsId);
		}

		$diskDir = match ($fieldType) {
			'file', 'video', 'audio' => 'public_files/',
			default                  => 'posts/',
		};
		$prefix    = date('Y-m') . '/';
		$targetDir = rtrim(ROOT_DIR, '/') . '/uploads/' . $diskDir . $prefix;

		if(!is_dir($targetDir) && !mkdir($targetDir, 0777, true) && !is_dir($targetDir)) {
			throw new RuntimeException(__('Не удалось создать каталог публикации DLE'));
		}

		$ext      = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
		$basename = bin2hex(random_bytes(8)) . ($ext !== '' ? '.' . $ext : '');
		$target   = $targetDir . $basename;

		if(!copy($source, $target)) {
			throw new RuntimeException(__('Не удалось скопировать файл в uploads DLE'));
		}

		$relative = $prefix . $basename;
		$size     = (int) filesize($target);
		$url      = $this->httpHomeUrl() . 'uploads/' . $diskDir . $relative;

		if(in_array($fieldType, ['video', 'audio'], true)) {
			$fileId = $this->registerPublicFile($originalName, $relative, $size, $newsId);

			return $url . '|' . $fileId . '|' . $size;
		}

		if($fieldType === 'file') {
			$this->registerPublicFile($originalName, $relative, $size, $newsId);

			return $url;
		}

		return $relative;
	}

	/**
	 * @param array<string, mixed> $fieldSchema
	 */
	private function isPrivateFile(array $fieldSchema): bool {
		$raw = $fieldSchema['is_public'] ?? 1;

		return $raw === 0 || $raw === '0';
	}

	/**
	 * Регистрирует публичный файл в PREFIX_files (как FileUploader DLE).
	 */
	private function registerPublicFile(string $originalName, string $onserver, int $size, ?int $newsId): int {
		global $db, $member_id;

		$oname  = $db->safesql($originalName);
		$name   = $db->safesql($onserver);
		$author = $db->safesql((string) ($member_id['name'] ?? 'admin'));
		$date   = time();
		$nid    = $newsId !== null && $newsId > 0 ? $newsId : 0;

		$result = $db->query(
			"INSERT INTO " . PREFIX . "_files (news_id, name, onserver, author, date, size, is_public) VALUES('{$nid}', '{$oname}', '{$name}', '{$author}', '{$date}', '{$size}', '1')",
			false,
		);

		if($result === false) {
			$last = end($db->query_errors_list);
			throw new RuntimeException((string) ($last['error'] ?? __('Не удалось зарегистрировать файл DLE')));
		}

		$fileId = (int) $db->insert_id();

		if($fileId <= 0) {
			throw new RuntimeException(__('Не удалось зарегистрировать файл DLE'));
		}

		return $fileId;
	}

	private function publishPrivateAttachment(string $source, string $originalName, ?int $newsId): string {
		global $db, $member_id;

		if($newsId === null || $newsId <= 0) {
			throw new RuntimeException(__('Для приватного файла нужен ID новости'));
		}

		$prefix    = date('Y-m') . '/';
		$targetDir = rtrim(ROOT_DIR, '/') . '/uploads/files/' . $prefix;

		if(!is_dir($targetDir) && !mkdir($targetDir, 0777, true) && !is_dir($targetDir)) {
			throw new RuntimeException(__('Не удалось создать каталог публикации DLE'));
		}

		$ext      = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
		$basename = bin2hex(random_bytes(8)) . ($ext !== '' ? '.' . $ext : '');
		$target   = $targetDir . $basename;

		if(!copy($source, $target)) {
			throw new RuntimeException(__('Не удалось скопировать файл в uploads DLE'));
		}

		$size   = (int) filesize($target);
		$oname  = $db->safesql($originalName);
		$name   = $db->safesql($prefix . $basename);
		$author = $db->safesql((string) ($member_id['name'] ?? 'admin'));
		$date   = time();

		$result = $db->query(
			"INSERT INTO " . PREFIX . "_files (news_id, name, onserver, author, date, size, is_public) VALUES('{$newsId}', '{$oname}', '{$name}', '{$author}', '{$date}', '{$size}', '0')",
			false,
		);

		if($result === false) {
			$last = end($db->query_errors_list);
			throw new RuntimeException((string) ($last['error'] ?? __('Не удалось зарегистрировать вложение DLE')));
		}

		$fileId = (int) $db->insert_id();

		if($fileId <= 0) {
			throw new RuntimeException(__('Не удалось зарегистрировать вложение DLE'));
		}

		return '[attachment=' . $fileId . ':' . $originalName . ']';
	}

	private function httpHomeUrl(): string {
		global $config;

		return rtrim((string) ($config['http_home_url'] ?? '/'), '/') . '/';
	}

}
