<?php

declare(strict_types=1);

namespace DevCraft\Modules\dle_faker\Services;

use DevCraft\Core\Support\DataManager;
use RuntimeException;

/**
 * Хранение файлов библиотеки и вложений шаблонов на диске.
 */
final class StaticFileStorage {

	private const IMAGE_EXT = ['gif', 'jpg', 'jpeg', 'png', 'webp', 'bmp', 'avif'];

	private const FILE_EXT = ['pdf', 'zip', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'csv', 'mp3', 'mp4', 'webm', 'ogg'];

	public function libraryRoot(): string {
		return rtrim(ROOT_DIR, '/') . '/uploads/dle_faker/static';
	}

	public function templatesRoot(): string {
		return rtrim(ROOT_DIR, '/') . '/uploads/dle_faker/templates';
	}

	public function kindSubdir(string $kind): string {
		return $kind === 'image' ? 'images' : 'files';
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

	public function normalizeKind(string $kind): string {
		return $kind === 'image' ? 'image' : 'file';
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

		$ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
		$allowed = $kind === 'image' ? self::IMAGE_EXT : array_merge(self::IMAGE_EXT, self::FILE_EXT);

		if($ext === '' || !in_array($ext, $allowed, true)) {
			throw new RuntimeException(__('Недопустимое расширение файла'));
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
