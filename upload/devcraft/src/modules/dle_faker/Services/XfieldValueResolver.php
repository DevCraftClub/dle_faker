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
	public function resolve(array $configured, array $schema, array $config, bool $allowTemplateUpload = true): array {
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
			$value = $this->resolveOne($configured[$name], $type, $config, $allowTemplateUpload);

			if($value !== '') {
				$result[$name] = $value;
			}
		}

		return $result;
	}

	private function resolveOne(mixed $configured, string $type, array $config, bool $allowTemplateUpload): string {
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
			'static'          => $this->resolveStatic($configured, $type),
			'template_upload' => $allowTemplateUpload ? $this->resolveTemplateAsset($configured, $type) : '',
			default           => $this->parser->parseNewsValue((string) ($configured['value'] ?? ''), $config),
		};
	}

	/**
	 * @param array<string, mixed> $configured
	 */
	private function resolveStatic(array $configured, string $fieldType): string {
		$database = Application::instance()->database();
		/** @var FakerStaticFileRepository $repo */
		$repo = $database->repository(FakerStaticFile::class);
		$kind = in_array($fieldType, ['image', 'imagegalery'], true) ? 'image' : 'file';

		if(filter_var($configured['random'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
			$pool = $repo->findByKind($kind);

			if($pool === []) {
				throw new RuntimeException(__('Библиотека статичных файлов пуста для выбранного типа'));
			}

			$file = $pool[array_rand($pool)];
		} else {
			$id = (int) ($configured['static_id'] ?? 0);
			$file = $id > 0 ? $repo->findOneById($id) : null;

			if($file === NULL) {
				throw new RuntimeException(__('Выбранный статичный файл не найден'));
			}
		}

		return $this->publisher->publishStatic($file, $fieldType);
	}

	/**
	 * @param array<string, mixed> $configured
	 */
	private function resolveTemplateAsset(array $configured, string $fieldType): string {
		$id = (int) ($configured['asset_id'] ?? 0);

		if($id <= 0) {
			throw new RuntimeException(__('Не указано вложение шаблона'));
		}

		$database = Application::instance()->database();
		/** @var FakerTemplateAssetRepository $repo */
		$repo  = $database->repository(FakerTemplateAsset::class);
		$asset = $repo->findOneById($id);

		if($asset === NULL) {
			throw new RuntimeException(__('Вложение шаблона не найдено'));
		}

		return $this->publisher->publishTemplateAsset($asset, $fieldType);
	}

}
