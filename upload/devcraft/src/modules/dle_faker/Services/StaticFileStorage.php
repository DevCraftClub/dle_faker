<?php

declare(strict_types=1);

namespace DevCraft\Modules\dle_faker\Services;

use DevCraft\Core\Support\DataManager;
use RuntimeException;

/**
 * Хранение файлов библиотеки и вложений шаблонов на диске.
 */
final class StaticFileStorage {

	/** Расширения изображений как в DLE UploadFile::$allowed_extensions. */
	private const DLE_IMAGE_EXT = ['gif', 'jpg', 'jpeg', 'png', 'webp', 'bmp', 'avif', 'heic'];

	/** Расширения video xfield как в engine/ajax/upload.php. */
	private const DLE_VIDEO_EXT = ['mp4', 'm4v', 'm4a', 'mov', 'webm', 'm3u8', 'mkv'];

	/** Расширения audio xfield как в engine/ajax/upload.php. */
	private const DLE_AUDIO_EXT = ['mp3', 'flac', 'aac', 'ogg'];

	private const KIND_SUBDIRS = [
		'image' => 'images',
		'file'  => 'files',
		'audio' => 'audios',
		'video' => 'videos',
	];

	public function libraryRoot(): string {
		return rtrim(ROOT_DIR, '/') . '/uploads/dle_faker/static';
	}

	public function templatesRoot(): string {
		return rtrim(ROOT_DIR, '/') . '/uploads/dle_faker/templates';
	}

	public function kindSubdir(string $kind): string {
		$kind = $this->normalizeKind($kind);

		return self::KIND_SUBDIRS[$kind] ?? 'files';
	}

	/**
	 * @return list<string>
	 */
	public function allowedImageExtensions(): array {
		return self::DLE_IMAGE_EXT;
	}

	/**
	 * @return list<string>
	 */
	public function allowedVideoExtensions(): array {
		return self::DLE_VIDEO_EXT;
	}

	/**
	 * @return list<string>
	 */
	public function allowedAudioExtensions(): array {
		return self::DLE_AUDIO_EXT;
	}

	/**
	 * Расширения файлов из настроек группы текущего пользователя DLE.
	 *
	 * @return list<string>
	 */
	public function allowedFileExtensions(): array {
		global $member_id, $user_group;

		$groupId = (int) ($member_id['user_group'] ?? 0);
		$raw     = '';

		if($groupId > 0 && isset($user_group[$groupId]) && is_array($user_group[$groupId])) {
			$raw = (string) ($user_group[$groupId]['files_type'] ?? '');
		}

		$parts = preg_split('/\s*,\s*/', strtolower(str_replace('.', '', $raw)), -1, PREG_SPLIT_NO_EMPTY) ?: [];

		return array_values(array_unique(array_filter($parts, static fn(string $ext): bool => $ext !== '')));
	}

	/**
	 * @return list<string>
	 */
	public function allowedExtensionsForKind(string $kind): array {
		$kind = $this->normalizeKind($kind);

		return match($kind) {
			'image' => $this->allowedImageExtensions(),
			'video' => $this->allowedVideoExtensions(),
			'audio' => $this->allowedAudioExtensions(),
			default => array_values(array_unique(array_merge(
				$this->allowedImageExtensions(),
				$this->allowedFileExtensions(),
			))),
		};
	}

	/**
	 * @param array{name?: string, type?: string, tmp_name?: string, error?: int, size?: int} $file
	 *
	 * @return array{stored_name: string, original_name: string, mime: string, size_bytes: int, absolute_path: string}
	 */
	public function storeLibraryUpload(string $kind, array $file): array {
		$kind = $this->normalizeKind($kind);
		$dir  = $this->libraryRoot() . '/' . $this->kindSubdir($kind);
		$this->ensureDir($dir);

		return $this->storeUpload($kind, $file, $dir);
	}

	/**
	 * @param array{name?: string, type?: string, tmp_name?: string, error?: int, size?: int} $file
	 *
	 * @return array{stored_name: string, original_name: string, mime: string, size_bytes: int, absolute_path: string}
	 */
	public function storeTemplateUpload(int $templateId, string $kind, array $file): array {
		$kind = $this->normalizeKind($kind);
		$dir  = $this->templatesRoot() . '/' . max(0, $templateId);
		$this->ensureDir($dir);

		return $this->storeUpload($kind, $file, $dir);
	}

	public function libraryAbsolutePath(string $kind, string $storedName): string {
		return $this->libraryRoot() . '/' . $this->kindSubdir($this->normalizeKind($kind)) . '/' . basename($storedName);
	}

	public function templateAbsolutePath(int $templateId, string $storedName): string {
		return $this->templatesRoot() . '/' . max(0, $templateId) . '/' . basename($storedName);
	}

	public function deleteLibraryFile(string $kind, string $storedName): void {
		$path = $this->libraryAbsolutePath($kind, $storedName);

		if(is_file($path)) {
			@unlink($path);
		}
	}

	public function deleteTemplateFile(int $templateId, string $storedName): void {
		$path = $this->templateAbsolutePath($templateId, $storedName);

		if(is_file($path)) {
			@unlink($path);
		}
	}

	public function deleteTemplateDirectory(int $templateId): void {
		$dir = $this->templatesRoot() . '/' . max(0, $templateId);

		if(!is_dir($dir)) {
			return;
		}

		foreach(glob($dir . '/*') ?: [] as $file) {
			if(is_file($file)) {
				@unlink($file);
			}
		}

		@rmdir($dir);
	}

	/**
	 * Переносит файл вложения шаблона между каталогами templates/{from}/ → templates/{to}/.
	 */
	public function moveTemplateFile(int $fromTemplateId, int $toTemplateId, string $storedName): void {
		$from = $this->templateAbsolutePath($fromTemplateId, $storedName);
		$toId = max(0, $toTemplateId);
		$toDir = $this->templatesRoot() . '/' . $toId;
		$to   = $toDir . '/' . basename($storedName);

		if($from === $to || !is_file($from)) {
			return;
		}

		$this->ensureDir($toDir);

		if(!rename($from, $to) && !(@copy($from, $to) && @unlink($from))) {
			throw new RuntimeException(__('Не удалось перенести вложение шаблона'));
		}
	}

	public function normalizeKind(string $kind): string {
		$kind = trim($kind);

		return isset(self::KIND_SUBDIRS[$kind]) ? $kind : 'file';
	}

	/**
	 * Kind из action меню (static-images → image).
	 */
	public function kindFromAction(string $action): string {
		return match($action) {
			'static-images' => 'image',
			'static-audio'  => 'audio',
			'static-video'  => 'video',
			default         => 'file',
		};
	}

	/**
	 * @param array{name?: string, type?: string, tmp_name?: string, error?: int, size?: int} $file
	 *
	 * @return array{stored_name: string, original_name: string, mime: string, size_bytes: int, absolute_path: string}
	 */
	private function storeUpload(string $kind, array $file, string $dir): array {
		$error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);

		if($error !== UPLOAD_ERR_OK) {
			throw new RuntimeException(__('Ошибка загрузки файла'));
		}

		$original = basename((string) ($file['name'] ?? 'file'));
		$tmp      = (string) ($file['tmp_name'] ?? '');
		$size     = (int) ($file['size'] ?? 0);

		if($tmp === '' || !is_uploaded_file($tmp)) {
			throw new RuntimeException(__('Временный файл загрузки недоступен'));
		}

		$ext     = strtolower(pathinfo($original, PATHINFO_EXTENSION));
		$allowed = $this->allowedExtensionsForKind($kind);

		if($ext === '' || $allowed === [] || !in_array($ext, $allowed, true)) {
			$list = $allowed !== [] ? implode(', ', $allowed) : __('нет');

			throw new RuntimeException(__('Недопустимое расширение файла. Разрешено: {ext}', ['{ext}' => $list]));
		}

		$stored = bin2hex(random_bytes(8)) . '.' . $ext;
		$target = $dir . '/' . $stored;

		if(!move_uploaded_file($tmp, $target)) {
			throw new RuntimeException(__('Не удалось сохранить загруженный файл'));
		}

		$mime = (string) ($file['type'] ?? mime_content_type($target) ?: 'application/octet-stream');

		return [
			'stored_name'   => $stored,
			'original_name' => $original,
			'mime'          => $mime,
			'size_bytes'    => $size > 0 ? $size : (int) filesize($target),
			'absolute_path' => $target,
		];
	}

	private function ensureDir(string $dir): void {
		if(is_dir($dir)) {
			return;
		}

		if(!DataManager::createDir($dir) && !is_dir($dir)) {
			throw new RuntimeException(__('Не удалось создать каталог для файлов'));
		}
	}

}
