<?php

declare(strict_types=1);

namespace DevCraft\Modules\dle_faker\Pages;

use DevCraft\Core\Abstracts\AbstractPage;

/**
 * Страница истории изменений модуля DLE Faker.
 */
final class ChangelogPage extends AbstractPage {

	public function handle(): array {
		$pageName = __('История изменений');
		$this->addBreadcrumb($pageName);

		return [
			'view' => 'pages/changelog.twig',
			'data' => [
				'page_title' => $pageName,
			],
		];
	}

}
