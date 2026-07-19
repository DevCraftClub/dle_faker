<?php

declare(strict_types=1);

namespace DevCraft\Modules\dle_faker\Pages;

use DevCraft\Core\Application;
use DevCraft\Core\Abstracts\AbstractPage;
use DevCraft\Modules\dle_faker\Models\FakerTemplate;
use DevCraft\Modules\dle_faker\Repositories\FakerTemplateRepository;

/**
 * Страница генерации новостей.
 */
final class GeneratorNewsPage extends AbstractPage {

	public function handle(): array {
		$this->addBreadcrumb(__('Генератор'), '?mod=dle_faker&action=generator-users');
		$this->addBreadcrumb(__('Новости'));
		/** @var FakerTemplateRepository $repository */
		$repository = Application::instance()->database()->repository(FakerTemplate::class);

		return [
			'view' => 'dle_faker/generator_news.twig',
			'data' => [
				'page_title' => __('Новости'),
				'templates'  => $repository->getActive(),
			],
		];
	}

}
