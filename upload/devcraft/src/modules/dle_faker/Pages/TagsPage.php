<?php

declare(strict_types=1);

namespace DevCraft\Modules\dle_faker\Pages;

use DevCraft\Core\Abstracts\AbstractPage;
use DevCraft\Modules\dle_faker\Services\TagReferenceService;

/**
 * Страница справочника тегов генератора.
 */
final class TagsPage extends AbstractPage {

	public function handle(): array {
		$this->addBreadcrumb(__('Теги'));
		$tags = (new TagReferenceService())->all();

		return [
			'view' => 'dle_faker/tags.twig',
			'data' => [
				'page_title'  => __('Перечень тегов для генерации данных'),
				'user_tags'   => $tags['user_tags'],
				'post_tags'   => $tags['post_tags'],
				'helper_tags' => $tags['helper_tags'],
			],
		];
	}

}
