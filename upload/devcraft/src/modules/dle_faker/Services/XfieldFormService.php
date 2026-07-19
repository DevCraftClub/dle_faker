<?php

declare(strict_types=1);

namespace DevCraft\Modules\dle_faker\Services;

/**
 * Готовит метаданные контролов доп. полей по схеме DLE.
 */
final class XfieldFormService {

	private const MEDIA_TYPES = ['image', 'imagegalery', 'video', 'audio', 'file'];

	/**
	 * @param array<string, array<string, mixed>> $schema
	 * @param array<string, mixed>                $values
	 * @param array<int, array<string, mixed>>    $staticImages
	 * @param array<int, array<string, mixed>>    $staticFiles
	 * @param array<int, array<string, mixed>>    $staticAudios
	 * @param array<int, array<string, mixed>>    $staticVideos
	 *
	 * @return list<array<string, mixed>>
	 */
	public function buildFields(
		array $schema,
		array $values = [],
		bool $allowTemplateUpload = true,
		array $staticImages = [],
		array $staticFiles = [],
		array $staticAudios = [],
		array $staticVideos = [],
	): array {
		$fields = [];

		foreach($schema as $name => $info) {
			if(!is_array($info)) {
				continue;
			}

			$name = (string) ($info['name'] ?? $name);
			$type = (string) ($info['type'] ?? 'text');
			$raw  = $values[$name] ?? '';

			$fields[] = [
				'name'                  => $name,
				'label'                 => trim((string) ($info['description'] ?? '')) ?: $name,
				'type'                  => $type,
				'required'              => !(int) ($info['not_required'] ?? 1),
				'is_media'              => in_array($type, self::MEDIA_TYPES, true),
				'allow_template_upload' => $allowTemplateUpload && in_array($type, self::MEDIA_TYPES, true),
				'options'               => $this->selectOptions($info),
				'value'                 => $this->normalizeValue($raw, $type),
				'max_images'            => (int) ($info['max_images'] ?? 0),
				'max_files'             => (int) ($info['max_files'] ?? 0),
				'static_images'         => $staticImages,
				'static_files'          => $staticFiles,
				'static_audios'         => $staticAudios,
				'static_videos'         => $staticVideos,
			];
		}

		return $fields;
	}

	/**
	 * @param array<string, mixed>                $input
	 * @param array<string, array<string, mixed>> $schema
	 *
	 * @return array{values: array<string, mixed>, errors: array<string, string>}
	 */
	public function normalizeIncoming(array $input, array $schema, bool $allowTemplateUpload = true): array {
		$values = [];
		$errors = [];

		foreach($schema as $name => $info) {
			if(!is_array($info)) {
				continue;
			}

			$name     = (string) ($info['name'] ?? $name);
			$type     = (string) ($info['type'] ?? 'text');
			$required = !(int) ($info['not_required'] ?? 1);
			$raw      = $input[$name] ?? null;
			$normalized = $this->normalizeValue($raw, $type);

			if(is_array($normalized) && ($normalized['source'] ?? '') === 'template_upload' && !$allowTemplateUpload) {
				$normalized = ['source' => 'faker', 'value' => ''];
			}

			$empty = $this->isEmpty($normalized);

			if($required && $empty) {
				$errors[$name] = __('Поле обязательно');
				continue;
			}

			// Media-конфиг сохраняем всегда (даже без выбранного файла), чтобы source не сбрасывался в faker.
			if(in_array($type, self::MEDIA_TYPES, true) && is_array($normalized)) {
				$values[$name] = $normalized;
				continue;
			}

			if(!$empty) {
				$values[$name] = $normalized;
			}
		}

		return ['values' => $values, 'errors' => $errors];
	}

	/**
	 * @return array<string, string>
	 */
	private function selectOptions(array $info): array {
		$raw = (string) ($info['default'] ?? '');

		if($raw === '' && isset($info['options']) && is_array($info['options'])) {
			$out = [];

			foreach($info['options'] as $key => $label) {
				$out[(string) $key] = (string) $label;
			}

			return $out;
		}

		$out = [];

		foreach(preg_split('/\r\n|\r|\n/', $raw) ?: [] as $line) {
			$line = trim($line);

			if($line === '') {
				continue;
			}

			if(str_contains($line, '|')) {
				[$value, $label] = array_pad(explode('|', $line, 2), 2, '');
				$out[trim($value)] = trim($label) !== '' ? trim($label) : trim($value);
			} else {
				$out[$line] = $line;
			}
		}

		return $out;
	}

	private function normalizeValue(mixed $raw, string $type): mixed {
		if(in_array($type, self::MEDIA_TYPES, true)) {
			if(is_array($raw)) {
				$source = (string) ($raw['source'] ?? '');

				if($source === '' || !in_array($source, ['static', 'template_upload', 'faker'], true)) {
					if((int) ($raw['asset_id'] ?? 0) > 0) {
						$source = 'template_upload';
					} elseif((int) ($raw['static_id'] ?? 0) > 0 || filter_var($raw['random'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
						$source = 'static';
					} else {
						$source = 'faker';
					}
				}

				return [
					'source'    => $source,
					'static_id' => (int) ($raw['static_id'] ?? 0),
					'random'    => filter_var($raw['random'] ?? false, FILTER_VALIDATE_BOOLEAN),
					'count'     => max(1, (int) ($raw['count'] ?? 1)),
					'asset_id'  => (int) ($raw['asset_id'] ?? 0),
					'value'     => trim((string) ($raw['value'] ?? '')),
				];
			}

			$value = trim((string) $raw);

			return $value === '' ? ['source' => 'faker', 'value' => ''] : $value;
		}

		if($type === 'yesorno') {
			$value = trim((string) $raw);

			return in_array($value, ['on', 'off', 'random', '1', '0'], true) ? $value : 'random';
		}

		return is_array($raw) ? trim((string) ($raw['value'] ?? '')) : trim((string) $raw);
	}

	private function isEmpty(mixed $value): bool {
		if(is_array($value)) {
			$source = (string) ($value['source'] ?? 'faker');

			return match ($source) {
				'static'          => !(bool) ($value['random'] ?? false) && (int) ($value['static_id'] ?? 0) <= 0,
				'template_upload' => (int) ($value['asset_id'] ?? 0) <= 0,
				default           => trim((string) ($value['value'] ?? '')) === '',
			};
		}

		return trim((string) $value) === '';
	}

}
