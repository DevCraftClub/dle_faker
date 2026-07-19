<?php

declare(strict_types=1);

namespace DevCraft\Modules\dle_faker\Pages;

use DevCraft\Core\Abstracts\AbstractPage;
use DevCraft\Core\Application;
use DevCraft\Modules\dle_faker\Services\CategoryGeneratorService;

/**
 * Страница генерации категорий DLE.
 */
final class GeneratorCategoriesPage extends AbstractPage {

	public function handle(): array {
		$this->addBreadcrumb(__('Генератор'), '?mod=dle_faker&action=generator-users');
		$this->addBreadcrumb(__('Категории'));

		$parents = CategoryGeneratorService::buildParentTreeOptions(
			Application::instance()->dleData()->categoriesFull(),
		);

		return [
			'view' => 'dle_faker/generator_categories.twig',
			'data' => [
				'page_title' => __('Категории'),
				'parents'    => $parents,
			],
		];
	}

}
