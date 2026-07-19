<?php

declare(strict_types=1);

namespace DevCraft\Modules\dle_faker\Services;

/**
 * Кодирует карту xfields в pipe-строку DLE 20.
 */
final class XfieldValueEncoder {

	/**
	 * @param array<string, string> $fields
	 * @param array<string, array<string, mixed>> $schema
	 */
	public function encode(array $fields, array $schema = []): string {
		$parts = [];

		foreach($fields as $name => $value) {
			$name  = (string) $name;
			$value = (string) $value;

			if($name === '' || $value === '') {
				continue;
			}

			$type = (string) ($schema[$name]['type'] ?? '');

			if($type === 'datetime') {
				$value = str_replace(':', '&#58;', $value);
			}

			$safeName  = str_replace('|', '&#124;', $name);
			$safeValue = str_replace(["\r", "\n", '|'], ['', '__NEWL__', '&#124;'], $value);
			$parts[]   = $safeName . '|' . $safeValue;
		}

		return implode('||', $parts);
	}

}
