<?php

declare(strict_types=1);

namespace DevCraft\Modules\dle_faker\Pages;

use DevCraft\Modules\dle_faker\DleFakerIdentity;

use DLEPlugins;
use DevCraft\Core\Application;
use DevCraft\Core\Support\DataManager;
use DevCraft\Core\Support\DleDataService;
use DevCraft\Types\FilterSchema;
use DevCraft\Core\Abstracts\AbstractPage;
use DevCraft\Core\Admin\FilterFormService;
use DevCraft\Modules\dle_faker\Models\FakerStaticFile;
use DevCraft\Modules\dle_faker\Models\FakerTemplate;
use DevCraft\Modules\dle_faker\Repositories\FakerStaticFileRepository;
use DevCraft\Modules\dle_faker\Repositories\FakerTemplateRepository;
use DevCraft\Modules\dle_faker\Services\ConfigNormalizer;
use DevCraft\Modules\dle_faker\Services\XfieldFormService;

/**
 * Страница списка шаблонов генерации.
 */
final class TemplatesPage extends AbstractPage {

	public function handle(): array {
		$mode = trim((string) ($_GET['mode'] ?? ''));

		if($mode === 'create' || $mode === 'edit') {
			return $this->formPage($mode);
		}

		return $this->listPage();
	}

	/**
	 * @return array{view: string, data: array<string, mixed>}
	 */
	private function listPage(): array {
		$this->addBreadcrumb(__('Шаблоны'));
		$filterService = new FilterFormService();
		$query         = $filterService->parseRequestQuery();
		$schema        = $this->loadFilterSchema();
		$order         = FilterFormService::normalizeOrder((string) ($query['order'] ?? $schema->defaultOrder), $schema);
		$sort          = strtoupper((string) ($query['sort'] ?? 'DESC'));
		$criteria      = [];

		if(($query['name'] ?? '') !== '') {
			$criteria[] = ['column' => 'name', 'op' => 'like', 'value' => (string) $query['name']];
		}

		if(($query['active'] ?? '') !== '') {
			$criteria[] = ['column' => 'active', 'op' => '=', 'value' => (string) $query['active'] === '1'];
		}

		/** @var FakerTemplateRepository $repository */
		$repository = Application::instance()->database()->repository(FakerTemplate::class);
		$result     = $repository->findFiltered(
			$criteria,
			max(1, (int) ($query['page'] ?? 1)),
			50,
			$order,
			$sort,
			$schema->sortColumnKeys(),
			$schema->defaultOrder,
		);

		return [
			'view' => 'dle_faker/templates_list.twig',
			'data' => [
				'page_title' => __('Шаблоны'),
				'templates'  => $result['items'],
				'total'      => $result['total'],
				'query'      => $query,
			],
		];
	}

	/**
	 * @return array{view: string, data: array<string, mixed>}
	 */
	private function formPage(string $mode): array {
		$this->addBreadcrumb(__('Шаблоны'), '?mod=dle_faker&action=templates');
		$templateId = (int) ($_GET['id'] ?? 0);
		$template   = NULL;
		$values     = [
			'id'                => 0,
			'name'              => '',
			'active_template'   => true,
			'autor'             => 'random',
			'title'             => '',
			'category'          => 'random',
			'category_ids'      => ['random'],
			'categories_count'  => 1,
			'date_from'         => '',
			'date_to'           => '',
			'short_story'       => '',
			'full_story'        => '',
			'allow_main'        => 'random',
			'approve'           => 'random',
			'fixed'             => 'random',
			'allow_comm'        => 'random',
			'allow_rate'        => 'random',
			'disable_index'     => 'random',
			'disable_search'    => 'random',
			'allow_rss'         => 'random',
			'allow_rss_dzen'    => 'random',
			'xfields'           => [],
		];

		/** @var FakerTemplateRepository $repository */
		$repository = Application::instance()->database()->repository(FakerTemplate::class);

		if($mode === 'edit' && $templateId > 0) {
			$template = $repository->findOneById($templateId);

			if($template !== NULL) {
				$decoded = json_decode($template->template, true);
				$values  = is_array($decoded) ? array_merge($values, $decoded) : $values;
				$values['id']              = $template->id();
				$values['name']            = $template->name;
				$values['active_template'] = $template->active;
			}
		}

		$values['category_ids'] = $this->categoryIdsFromValue($values['category'] ?? 'random');

		$title   = $mode === 'edit' ? __('Редактирование шаблона') : __('Создание шаблона');
		$this->addBreadcrumb($title);

		/** @var FakerStaticFileRepository $staticRepo */
		$staticRepo = Application::instance()->database()->repository(FakerStaticFile::class);
		$images = array_map(static fn(FakerStaticFile $f): array => [
			'id'            => $f->id(),
			'original_name' => $f->original_name,
		], $staticRepo->findByKind('image'));
		$files = array_map(static fn(FakerStaticFile $f): array => [
			'id'            => $f->id(),
			'original_name' => $f->original_name,
		], $staticRepo->findByKind('file'));
		$audios = array_map(static fn(FakerStaticFile $f): array => [
			'id'            => $f->id(),
			'original_name' => $f->original_name,
		], $staticRepo->findByKind('audio'));
		$videos = array_map(static fn(FakerStaticFile $f): array => [
			'id'            => $f->id(),
			'original_name' => $f->original_name,
		], $staticRepo->findByKind('video'));

		$xfieldFields = (new XfieldFormService())->buildFields(
			DleDataService::postXfields(),
			is_array($values['xfields'] ?? null) ? $values['xfields'] : [],
			true,
			$images,
			$files,
			$audios,
			$videos,
		);

		$config = (new ConfigNormalizer())->normalize(DataManager::getConfig(DleFakerIdentity::code()));

		return [
			'view' => 'dle_faker/templates_form.twig',
			'data' => [
				'page_title' => $title,
				'template'   => $template,
				'values'     => $values,
				'users'      => $this->userOptions(),
				'categories' => DleDataService::categories(),
				'xfield_fields' => $xfieldFields,
				'xfields_display_mode' => $config['xfields_display_mode'],
				'flag_field_labels' => $this->flagFieldLabels(),
				'yes_no_random_options' => [
					'random' => __('Случайно'),
					'1'      => __('Да'),
					'0'      => __('Нет'),
				],
			],
		];
	}

	/**
	 * Подписи флагов новости: «описание (id)» из adminpanel.lng.
	 *
	 * @return array<string, string>
	 */
	private function flagFieldLabels(): array {
		global $lang;

		$map = [
			'allow_main'      => 'addnews_main',
			'approve'         => 'addnews_mod',
			'fixed'           => 'addnews_fix',
			'allow_comm'      => 'addnews_comm',
			'allow_rate'      => 'addnews_allow_rate',
			'disable_index'   => 'add_disable_index',
			'disable_search'  => 'cat_d_search',
			'allow_rss'       => 'allow_rss_news',
			'allow_rss_dzen'  => 'allow_rss_dzen',
		];

		$fallback = [
			'allow_main'      => __('Публиковать на главной'),
			'approve'         => __('Опубликовать новость на сайте'),
			'fixed'           => __('Зафиксировать новость'),
			'allow_comm'      => __('Разрешить комментарии'),
			'allow_rate'      => __('Разрешить рейтинг статьи'),
			'disable_index'   => __('Запретить индексацию для поисковиков'),
			'disable_search'  => __('Исключить из поиска по сайту'),
			'allow_rss'       => __('Опубликовать новость в RSS потоке'),
			'allow_rss_dzen'  => __('Использовать в Яндекс Дзен'),
		];

		$out = [];

		foreach($map as $field => $langKey) {
			$descr = is_array($lang) && isset($lang[$langKey]) && (string) $lang[$langKey] !== ''
				? (string) $lang[$langKey]
				: ($fallback[$field] ?? $field);
			$out[$field] = $descr . ' (' . $field . ')';
		}

		return $out;
	}

	private function loadFilterSchema(): FilterSchema {
		/** @var array<string, mixed> $raw */
		$raw = require DLEPlugins::Check(DEVCRAFT_MODULES . '/dle_faker/Filter/filter.schema.php');

		return FilterSchema::fromArray($raw);
	}

	/**
	 * @return array<string, string>
	 */
	private function userOptions(): array {
		$options = [];

		foreach(DleDataService::users() as $row) {
			$id   = (string) ((int) ($row['user_id'] ?? 0));
			$name = trim((string) ($row['name'] ?? ''));

			if($id === '0' || $name === '') {
				continue;
			}

			$options[$id] = $name;
		}

		return $options;
	}

	/**
	 * @return list<string>
	 */
	private function categoryIdsFromValue(mixed $category): array {
		$raw = trim((string) $category);

		if($raw === '' || $raw === 'random') {
			return ['random'];
		}

		$ids = [];

		foreach(preg_split('/[\s,]+/', $raw, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $part) {
			$part = trim((string) $part);

			if($part === 'random') {
				return ['random'];
			}

			if($part !== '') {
				$ids[] = $part;
			}
		}

		return $ids !== [] ? $ids : ['random'];
	}

}
