<?php

declare(strict_types=1);

namespace DevCraft\Modules\dle_faker\Pages;

use DevCraft\Core\Application;
use DevCraft\Core\Abstracts\AbstractPage;

/**
 * Главная страница модуля DLE Faker.
 */
final class DashboardPage extends AbstractPage {

	public function handle(): array {
		$registry  = Application::instance()->registry();
		$plugin    = $registry->forMod('dle_faker');
		$meta      = $plugin?->meta() ?? [];
		$context   = $this->adminContext();
		$changelog = $plugin?->changelog() ?? [];
		$latest    = isset($changelog[0]) ? $changelog[0]->toArray() : NULL;
		$menu      = [];

		foreach($context->menu() as $link) {
			if($link->type !== 'link' || $link->action === NULL || $link->action === 'dashboard') {
				continue;
			}

			$menu[] = [
				'name' => $link->name,
				'link' => $link->link,
				'icon' => $link->extra,
			];
		}

		return [
			'view' => 'dle_faker/dashboard.twig',
			'data' => [
				'page_title' => (string) ($meta['name'] ?? 'DLE Faker'),
				'dashboard'  => [
					'app'              => [
						'name'        => (string) ($meta['name'] ?? 'DLE Faker'),
						'version'     => (string) ($meta['version'] ?? '0.0.0'),
						'description' => (string) ($meta['description'] ?? ''),
						'icon'        => (string) ($meta['icon'] ?? ''),
						'docs_link'   => (string) ($meta['docsLink'] ?? ''),
						'site_link'   => (string) ($meta['siteLink'] ?? ''),
						'code'        => (string) ($meta['module_code'] ?? 'dle_faker'),
					],
					'author'           => $context->author()->toArray(),
					'lic_link'         => $context->licLink(),
					'menu'             => $menu,
					'changelog_latest' => $latest,
					'changelog_url'    => '?mod=dle_faker&action=changelog',
				],
			],
		];
	}

}
