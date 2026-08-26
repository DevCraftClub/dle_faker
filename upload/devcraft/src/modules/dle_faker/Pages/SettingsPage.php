<?php

declare(strict_types=1);

namespace DevCraft\Modules\dle_faker\Pages;

use DevCraft\Core\Application;
use DevCraft\Core\Abstracts\AbstractPage;
use DevCraft\Core\Interfaces\SettingsPageInterface;
use DevCraft\Core\Support\DleDataService;

/**
 * Страница настроек модуля DLE Faker.
 */
final class SettingsPage extends AbstractPage implements SettingsPageInterface {

	public function handle(): array {
		$this->addBreadcrumb(__('Настройки'));

		return [
			'view' => 'pages/settings.twig',
			'data' => [
				'page_title' => __('Настройки'),
			],
		];
	}

	public function supplementFormData(): array {
		$users   = [];

		foreach(DleDataService::users() as $row) {
			$id    = (string) ((int) ($row['user_id'] ?? 0));
			$name  = trim((string) ($row['name'] ?? ''));
			$email = trim((string) ($row['email'] ?? ''));

			if($id === '0' || $name === '') {
				continue;
			}

			$users[$id] = $email !== '' ? $name . ' <' . $email . '>' : $name;
		}

		return [
			'users'      => $users,
			'categories' => array_map(
				static fn(string $name): string => $name,
				DleDataService::categories(),
			),
		];
	}

}
