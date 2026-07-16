<?php

declare(strict_types=1);

namespace DevCraft\Modules\dle_faker\Services;

/**
 * Возвращает набор доступных локалей Faker.
 */
final class FakerLocaleOptions {

	/**
	 * @return array<string, string>
	 */
	public static function all(): array {
		return [
			'site'    => __('Локаль сайта'),
			'ru_RU'   => 'ru_RU',
			'en_US'   => 'en_US',
			'uk_UA'   => 'uk_UA',
			'de_DE'   => 'de_DE',
			'fr_FR'   => 'fr_FR',
			'es_ES'   => 'es_ES',
			'it_IT'   => 'it_IT',
			'pl_PL'   => 'pl_PL',
			'pt_BR'   => 'pt_BR',
			'tr_TR'   => 'tr_TR',
			'zh_CN'   => 'zh_CN',
			'ja_JP'   => 'ja_JP',
		];
	}

}
