<?php

declare(strict_types=1);

namespace DevCraft\Modules\dle_faker\Services;

use DLEPlugins;
use DLE_API;
use DevCraft\Core\Application;
use DevCraft\Core\Support\DataManager;
use DevCraft\Core\Support\DleDataService;

/**
 * Генерирует пользователей DLE по шаблонам DLE Faker.
 */
final class UserGeneratorService {

	public function __construct(
		private readonly FakerContentParser $parser = new FakerContentParser(),
	) {}

	/**
	 * @param array<string, mixed> $payload
	 * @param array<string, mixed> $config
	 *
	 * @return array<string, mixed>
	 */
	public function generate(array $payload, array $config): array {
		global $db, $_TIME;

		$nameTemplate = trim((string) ($payload['name'] ?? ''));
		$emailPattern = trim((string) ($payload['email'] ?? ''));
		$password     = (string) ($payload['password'] ?? '');

		if($nameTemplate === '' || $emailPattern === '' || $password === '') {
			throw new \RuntimeException(__('Не заполнены обязательные поля генерации пользователя'));
		}

		$groups = $payload['usergroup'] ?? [4];
		$groups = is_array($groups) ? $groups : array_filter(array_map('trim', explode(',', (string) $groups)));
		$group  = (int) ($this->parser->randomValue(array_values($groups), config: $config) ?? 4);

		if(!class_exists('DLE_API')) {
			require_once DLEPlugins::Check(ENGINE_DIR . '/api/api.class.php');
		}

		$dleApi             = new DLE_API();
		$dleApi->dle_config = $GLOBALS['config'];
		$dleApi->db         = $db;

		$username = DataManager::toTranslit($this->parser->parseUserValue($nameTemplate, $config), false);
		$fullName = $this->parser->parseUserValue((string) ($payload['full_name'] ?? ''), $config);
		$email    = $this->normalizeEmail($this->parser->parseUserValue($emailPattern, $config));

		$result = $dleApi->external_register($username, $password, $email, $group);

		return match ($result) {
			1       => $this->completeSuccess($dleApi, $email, $fullName, $_TIME, $group, $payload, $config),
			-1      => throw new \RuntimeException(__('Псевдоним пользователя уже занят: {username}', ['{username}' => $username])),
			-2      => throw new \RuntimeException(__('Электронная почта пользователя уже занята: {email}', ['{email}' => $email])),
			-3      => throw new \RuntimeException(__('Электронная почта имеет некорректный формат: {email}', ['{email}' => $email])),
			-4      => throw new \RuntimeException(__('Группа пользователей не существует: {group}', ['{group}' => (string) $group])),
			default => throw new \RuntimeException(__('Не удалось создать пользователя')),
		};
	}

	/**
	 * @param array<string, mixed> $payload
	 * @param array<string, mixed> $config
	 *
	 * @return array<string, mixed>
	 */
	private function completeSuccess(DLE_API $dleApi, string $email, string $fullName, int $time, int $group, array $payload, array $config): array {
		$user = $dleApi->take_user_by_email($email, 'user_id, name, email, fullname, user_group');

		if(!is_array($user) || $user === []) {
			throw new \RuntimeException(__('Пользователь был создан, но не найден для обновления'));
		}

		$set = [
			'fullname' => $fullName,
			'lastdate' => $time,
		];

		$userXfields = (array) ($payload['user_xfields'] ?? $config['user_xfields'] ?? []);
		$schema      = DleDataService::userXfields();

		if($schema !== [] && $userXfields !== []) {
			$resolved       = (new XfieldValueResolver($this->parser))->resolve($userXfields, $schema, $config, false);
			$set['xfields'] = (new XfieldValueEncoder())->encode($resolved, $schema);
		}

		// SDK: prepared statements + корректный USERPREFIX для users.
		dle_api_update_by_pk('users', (int) $user['user_id'], $set);

		return [
			'id'         => (int) $user['user_id'],
			'username'   => (string) $user['name'],
			'full_name'  => $fullName,
			'email'      => $email,
			'user_group' => $group,
		];
	}

	private function normalizeEmail(string $email): string {
		$parts = explode('@', $email, 2);

		if(count($parts) !== 2) {
			return $email;
		}

		return DataManager::toTranslit($parts[0], false) . '@' . $parts[1];
	}

}
