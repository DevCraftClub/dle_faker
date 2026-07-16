<?php

declare(strict_types=1);

namespace DevCraft\Modules\dle_faker\Services;

use DevCraft\Core\Application;
use DevCraft\Modules\dle_faker\Models\FakerStaticFile;
use DevCraft\Modules\dle_faker\Models\FakerTemplateAsset;
use DevCraft\Modules\dle_faker\Repositories\FakerStaticFileRepository;
use DevCraft\Modules\dle_faker\Repositories\FakerTemplateAssetRepository;
use RuntimeException;

/**
 * Раскрывает настройки xfields шаблона/конфига в строковые значения для encode.
 */
final class XfieldValueResolver {

	public function __construct(
		private readonly FakerContentParser $parser = new FakerContentParser(),
		private readonly StaticFilePublisher $publisher = new StaticFilePublisher(),
	) {}

	/**
	 * @param array<string, mixed>                $configured
	 * @param array<string, array<string, mixed>> $schema
	 * @param array<string, mixed>                $config
	 *
	 * @return array<string, string>
	 */
	public function resolve(array $configured, array $schema, array $config, bool $allowTemplateUpload = true, ?int $newsId = null): array {
		$result = [];

		foreach($schema as $name => $info) {
			if(!is_array($info)) {
				continue;
			}

			$name = (string) ($info['name'] ?? $name);

			if(!array_key_exists($name, $configured)) {
				continue;
			}

			$type  = (string) ($info['type'] ?? 'text');
			$value = $this->resolveOne($configured[$name], $type, $info, $config, $allowTemplateUpload, $newsId);

			if($value !== '') {
				$result[$name] = $value;
			}
		}

		return $result;
	}

	/**
	 * Нужен ли news_id до публикации (приватный file).
	 *
	 * @param array<string, mixed>                $configured
	 * @param array<string, array<string, mixed>> $schema
	 */
	public function needsNewsId(array $configured, array $schema): bool {
		foreach($schema as $name => $info) {
			if(!is_array($info)) {
				continue;
			}

			$name = (string) ($info['name'] ?? $name);

			if(!array_key_exists($name, $configured)) {
				continue;
			}

			if((string) ($info['type'] ?? '') !== 'file') {
				continue;
			}

			$rawPublic = $info['is_public'] ?? 1;

			if($rawPublic === 0 || $rawPublic === '0') {
				return true;
			}
		}

		return false;
	}

	/**
	 * @param array<string, mixed> $info
	 * @param array<string, mixed> $config
	 */
	private function resolveOne(mixed $configured, string $type, array $info, array $config, bool $allowTemplateUpload, ?int $newsId): string {
		$mediaTypes = ['image', 'imagegalery', 'video', 'audio', 'file'];

		if(!in_array($type, $mediaTypes, true)) {
			if($type === 'yesorno') {
				$raw = trim((string) $configured);

				if($raw === 'random') {
					return (string) random_int(0, 1);
				}

				return in_array($raw, ['1', 'on'], true) ? '1' : (in_array($raw, ['0', 'off'], true) ? '0' : $this->parser->parseNewsValue($raw, $config));
			}

			return $this->parser->parseNewsValue((string) $configured, $config);
		}

		if(!is_array($configured)) {
			return $this->parser->parseNewsValue((string) $configured, $config);
		}

		$source = (string) ($configured['source'] ?? 'faker');

		return match ($source) {
			'static'          => $this->resolveStatic($configured, $type, $info, $newsId),
			'template_upload' => $allowTemplateUpload ? $this->resolveTemplateAsset($configured, $type, $info, $newsId) : '',
			default           => $this->parser->parseNewsValue((string) ($configured['value'] ?? ''), $config),
		};
	}

	/**
	 * @param array<string, mixed> $configured
	 * @param array<string, mixed> $info
	 */
	private function resolveStatic(array $configured, string $fieldType, array $info, ?int $newsId): string {
		$database = Application::instance()->database();
		/** @var FakerStaticFileRepository $repo */
		$repo = $database->repository(FakerStaticFile::class);
		$kind = match($fieldType) {
			'image', 'imagegalery' => 'image',
			'audio' => 'audio',
			'video' => 'video',
			default => 'file',
		};

		$files = $this->pickStaticFiles($repo, $kind, $configured, $fieldType, $info);
		$parts = [];

		foreach($files as $file) {
			$parts[] = $this->publisher->publishStatic($file, $fieldType, $info, $newsId);
		}

		return implode(',', $parts);
	}

	/**
	 * @param array<string, mixed> $configured
	 * @param array<string, mixed> $info
	 *
	 * @return list<FakerStaticFile>
	 */
	private function pickStaticFiles(FakerStaticFileRepository $repo, string $kind, array $configured, string $fieldType, array $info): array {
		if(!filter_var($configured['random'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
			$id = (int) ($configured['static_id'] ?? 0);

			if($id <= 0) {
				return [];
			}

			$file = $repo->findOneById($id);

			if($file === NULL) {
				throw new RuntimeException(__('Выбранный статичный файл не найден'));
			}

			return [$file];
		}

		$pool = $repo->findByKind($kind);

		if($pool === []) {
			throw new RuntimeException(__('Библиотека статичных файлов пуста для выбранного типа'));
		}

		$count = $this->clampCount((int) ($configured['count'] ?? 1), $fieldType, $info, count($pool));

		if($count === 1) {
			return [$pool[array_rand($pool)]];
		}

		$keys = array_rand($pool, $count);

		if(is_int($keys)) {
			return [$pool[$keys]];
		}

		$picked = [];

		foreach($keys as $key) {
			$picked[] = $pool[$key];
		}

		return $picked;
	}

	/**
	 * @param array<string, mixed> $info
	 */
	private function clampCount(int $requested, string $fieldType, array $info, int $poolSize): int {
		$requested = max(1, $requested);

		if($fieldType === 'image') {
			return 1;
		}

		if($fieldType === 'file') {
			$rawPublic = $info['is_public'] ?? 1;

			if($rawPublic === 0 || $rawPublic === '0') {
				return 1;
			}
		}

		if(!in_array($fieldType, ['imagegalery', 'video', 'audio', 'file'], true)) {
			return 1;
		}

		$schemaMax = match ($fieldType) {
			'imagegalery' => (int) ($info['max_images'] ?? 0),
			default       => (int) ($info['max_files'] ?? 0),
		};

		$n = min($requested, $poolSize);

		if($schemaMax > 0) {
			$n = min($n, $schemaMax);
		}

		return max(1, $n);
	}

	/**
	 * @param array<string, mixed> $configured
	 * @param array<string, mixed> $info
	 */
	private function resolveTemplateAsset(array $configured, string $fieldType, array $info, ?int $newsId): string {
		$id = (int) ($configured['asset_id'] ?? 0);

		if($id <= 0) {
			return '';
		}

		$database = Application::instance()->database();
		/** @var FakerTemplateAssetRepository $repo */
		$repo  = $database->repository(FakerTemplateAsset::class);
		$asset = $repo->findOneById($id);

		if($asset === NULL) {
			throw new RuntimeException(__('Вложение шаблона не найдено'));
		}

		return $this->publisher->publishTemplateAsset($asset, $fieldType, $info, $newsId);
	}

}
