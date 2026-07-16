<?php

declare(strict_types=1);

namespace DevCraft\Modules\dle_faker\Services;

/**
 * Нормализует legacy- и DevCraft-конфиг модуля в единый массив.
 */
final class ConfigNormalizer {

	/**
	 * @param array<string, mixed> $config
	 *
	 * @return array<string, mixed>
	 */
	public function normalize(array $config): array {
		$config['language']         = FakerLocaleOptions::resolve(
			isset($config['language']) ? (string) $config['language'] : null,
		);
		$config['users']            = $this->normalizeList($config['users'] ?? []);
		$config['categories']       = $this->normalizeList($config['categories'] ?? []);
		unset($config['categories_count']);
		$config['user_xfields']     = is_array($config['user_xfields'] ?? null) ? $config['user_xfields'] : [];

		return $config;
	}

	/**
	 * @param mixed $value
	 *
	 * @return list<string>
	 */
	private function normalizeList(mixed $value): array {
		if(is_string($value)) {
			$value = preg_split('/[\s,]+/', trim($value), -1, PREG_SPLIT_NO_EMPTY) ?: [];
		}

		if(!is_array($value)) {
			return [];
		}

		$result = [];

		foreach($value as $item) {
			$item = trim((string) $item);

			if($item !== '') {
				$result[] = $item;
			}
		}

		return array_values(array_unique($result));
	}

}
