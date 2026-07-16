<?php

declare(strict_types=1);

namespace DevCraft\Modules\dle_faker\Services;

use DateTime;
use DateTimeZone;

/**
 * Парсер шаблонов Faker для генерации пользователей и новостей.
 */
final class FakerContentParser {

	private const DEFAULT_NEWS_FORMAT = 'Y-m-d H:i:s';

	/**
	 * @param array<string, mixed> $config
	 */
	public function parseUserValue(string $template, array $config = []): string {
		return $this->parse($template, 'user', $config);
	}

	/**
	 * @param array<string, mixed> $config
	 */
	public function parseNewsValue(string $template, array $config = []): string {
		return $this->parse($template, 'post', $config);
	}

	/**
	 * @param array<string, mixed> $config
	 */
	public function randomValue(array $values, int $count = 1, array|string|null $exclude = null, array $config = []): mixed {
		$faker = $this->faker($config);

		if($values === []) {
			return null;
		}

		if($exclude !== null) {
			$values = array_values(array_diff($values, (array) $exclude));
		}

		if($values === []) {
			return null;
		}

		$count = min(count($values), max(1, $count));

		if($count > 1) {
			$count = $faker->numberBetween(1, $count);
		}

		$keys = array_rand($values, min($count, count($values)));

		if(is_int($keys)) {
			return $values[$keys];
		}

		return array_values(array_intersect_key($values, array_flip($keys)));
	}

	public function randomDateBetween(string $start, ?string $end = null): string {
		$timezone = $this->timezone();
		$from     = new DateTime($start, $timezone);
		$to       = $end !== null && $end !== '' ? new DateTime($end, $timezone) : new DateTime('now', $timezone);

		if($from > $to) {
			[$from, $to] = [$to, $from];
		}

		if($from == $to) {
			return $from->format(self::DEFAULT_NEWS_FORMAT);
		}

		$randomTimestamp = mt_rand($from->getTimestamp(), $to->getTimestamp());

		return (new DateTime('@' . $randomTimestamp))
			->setTimezone($timezone)
			->format(self::DEFAULT_NEWS_FORMAT);
	}

	/**
	 * @param array<string, mixed> $config
	 */
	public function parseBoolValue(string $text, array $config = []): bool {
		$faker = $this->faker($config);

		return match ($text) {
			'on', '1', 'true' => true,
			'off', '0', 'false' => false,
			default => $faker->boolean(),
		};
	}

	/**
	 * @param array<string, mixed> $config
	 */
	private function parse(string $template, string $type, array $config): string {
		$faker = $this->faker($config);
		$value = strtr($template, [
			'&amp;amp;'  => '&amp;',
			'&amp;#124;' => '&#124;',
			'&amp;'      => '&',
			'&nbsp;'     => ' ',
		]);

		$value = preg_replace_callback('/\{\{\s*(.+?)\s*\}\}/u', function(array $matches) use ($faker, $type, $config) {
			return (string) $this->resolveToken(trim((string) $matches[1]), $faker, $type, $config);
		}, $value) ?? $value;

		return $value;
	}

	/**
	 * @param array<string, mixed> $config
	 */
	private function faker(array $config): object {
		$locale  = FakerLocaleOptions::resolve(
			(string) ($config['language'] ?? $config['lang'] ?? ''),
		);
		$factory = '\\Faker\\Factory';

		return $factory::create($locale);
	}

	/**
	 * @param array<string, mixed> $config
	 */
	private function resolveToken(string $token, object $faker, string $type, array $config): string|int|float|bool {
		[$name, $params] = $this->parseToken($token);

		return match ($name) {
			'yesNo' => $faker->boolean(),
			'emoji' => $faker->emoji(),
			'randomDigit' => isset($params['not']) ? $faker->randomDigitNot((int) $params['not']) : $faker->randomDigit(),
			'randomFloat' => $faker->randomFloat(
				isset($params['float']) ? (int) $params['float'] : null,
				isset($params['min']) ? (int) $params['min'] : 0,
				isset($params['max']) ? (int) $params['max'] : null,
			),
			'randomLetter' => $faker->randomLetter(),
			'randomNumber' => $faker->randomNumber((int) ($params['nums'] ?? 0), filter_var($params['strict'] ?? false, FILTER_VALIDATE_BOOLEAN)),
			'numberBetween' => $faker->numberBetween((int) ($params['min'] ?? PHP_INT_MIN), (int) ($params['max'] ?? 2147483647)),
			'randomElements' => $this->resolveRandomElements($faker, $params),
			'word' => $faker->word(),
			'words' => implode(', ', $faker->words((int) ($params['max'] ?? 3))),
			'sentence' => $faker->sentence(),
			'sentences' => $faker->sentences((int) ($params['max'] ?? 3), true),
			'paragraph' => $faker->paragraph((int) ($params['max'] ?? 3)),
			'text' => $faker->text((int) ($params['max'] ?? 200)),
			'datetime' => $this->resolveDateTime($faker, $params),
			'userName' => $faker->userName(),
			'name' => $faker->name(),
			'firstName' => $faker->firstName(),
			'firstNameMale' => method_exists($faker, 'firstNameMale') ? $faker->firstNameMale() : $faker->firstName(),
			'firstNameFemale' => method_exists($faker, 'firstNameFemale') ? $faker->firstNameFemale() : $faker->firstName(),
			'lastName' => $faker->lastName(),
			'suffix' => '',
			'title' => method_exists($faker, 'title') ? $faker->title() : '',
			'titleMale' => method_exists($faker, 'titleMale') ? $faker->titleMale() : '',
			'titleFemale' => method_exists($faker, 'titleFemale') ? $faker->titleFemale() : '',
			'email' => $faker->safeEmail(),
			'random_user' => $type === 'post' ? (string) $this->randomValue((array) ($config['users'] ?? []), config: $config) : '',
			'random_category' => $type === 'post' ? (string) $this->randomValue((array) ($config['categories'] ?? []), config: $config) : '',
			'static_image' => $this->resolveStaticTag('image'),
			'static_file' => $this->resolveStaticTag('file'),
			'static_audio' => $this->resolveStaticTag('audio'),
			'static_video' => $this->resolveStaticTag('video'),
			default => '{{ ' . $token . ' }}',
		};
	}

	private function resolveStaticTag(string $kind): string {
		$resolver = new XfieldValueResolver($this);
		$fieldType = match($kind) {
			'image' => 'image',
			'audio' => 'audio',
			'video' => 'video',
			default => 'file',
		};
		$map = $resolver->resolve(
			['_tag' => ['source' => 'static', 'random' => true, 'count' => 1]],
			['_tag' => ['name' => '_tag', 'type' => $fieldType]],
			[],
			false,
		);

		return (string) ($map['_tag'] ?? '');
	}

	/**
	 * @return array{0: string, 1: array<string, string>}
	 */
	private function parseToken(string $token): array {
		preg_match_all('/([a-zA-Z_]+)=(".*?"|\'.*?\'|\\[.*?\\]|[^\\s]+)/u', $token, $matches, PREG_SET_ORDER);
		$params = [];

		foreach($matches as $match) {
			$params[$match[1]] = trim((string) $match[2], "\"'");
		}

		$name = explode(' ', $token)[0];

		return [$name, $params];
	}

	/**
	 * @param array<string, string> $params
	 */
	private function resolveRandomElements(object $faker, array $params): string {
		$items = isset($params['items']) ? array_values(array_filter(array_map('trim', explode(',', trim($params['items'], '[]'))))) : [];
		$count = isset($params['count']) ? (int) $params['count'] : 1;

		if($items === []) {
			return '';
		}

		$result = $faker->randomElements($items, max(1, $count), false);

		return implode((string) ($params['connector'] ?? '_'), $result);
	}

	/**
	 * @param array<string, string> $params
	 */
	private function resolveDateTime(object $faker, array $params): string {
		$format = (string) ($params['format'] ?? self::DEFAULT_NEWS_FORMAT);

		return $faker->dateTime()->format($format);
	}

	private function timezone(): DateTimeZone {
		global $config;

		return new DateTimeZone((string) ($config['date_adjust'] ?? 'UTC'));
	}

}
