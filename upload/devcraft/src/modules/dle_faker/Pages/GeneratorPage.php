<?php

declare(strict_types=1);

namespace DevCraft\Modules\dle_faker\Pages;

use DevCraft\Core\Abstracts\AbstractPage;

/**
 * Страница выбора генератора.
 */
final class GeneratorPage extends AbstractPage {

	public function handle(): array {
		$this->addBreadcrumb(__('Генераторы'));

		return [
			'view' => 'dle_faker/generator_index.twig',
			'data' => [
				'page_title' => __('Доступные генераторы'),
			],
		];
	}

}
