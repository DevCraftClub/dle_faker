<?php

declare(strict_types=1);

namespace DevCraft\Modules\dle_faker\Services;

use DcApi;
use DLEPlugins;
use ParseFilter;
use RuntimeException;
use Throwable;
use DevCraft\Core\Support\DleDataService;

/**
 * Генерирует новости DLE по шаблону DLE Faker.
 */
final class NewsGeneratorService {

	public function __construct(
		private readonly FakerContentParser $parser = new FakerContentParser(),
	) {}

	/**
	 * @param array<string, mixed> $template
	 * @param array<string, mixed> $moduleConfig Конфиг модуля dle_faker
	 *
	 * @return array<string, mixed>
	 */
	public function generate(array $template, array $moduleConfig): array {
		global $config, $_TIME, $_IP;

		if(!class_exists('ParseFilter')) {
			require_once DLEPlugins::Check(ENGINE_DIR . '/classes/parse.class.php');
		}

		$parse = new ParseFilter();

		$title = $parse->process(
			filter_var($this->parser->parseNewsValue((string) ($template['title'] ?? ''), $moduleConfig), FILTER_SANITIZE_FULL_SPECIAL_CHARS)
		);

		if($title === '') {
			throw new RuntimeException(__('Шаблон заголовка не может быть пустым'));
		}

		$autorRaw  = trim((string) ($template['autor'] ?? ''));
		$authorIds = $autorRaw === 'random'
			? array_map('intval', (array) ($moduleConfig['users'] ?? []))
			: array_filter(array_map('intval', explode(',', (string) $this->parser->parseNewsValue($autorRaw, $moduleConfig))));
		$authorIds = $authorIds !== [] ? $authorIds : array_map('intval', (array) ($moduleConfig['users'] ?? []));
		$authorId  = (int) ($this->parser->randomValue($authorIds, config: $moduleConfig) ?? 0);
		$author    = $this->loadUser($authorId);

		if($author === []) {
			throw new RuntimeException(__('Не удалось определить автора для создаваемой новости'));
		}

		$categoryRaw = trim((string) ($template['category'] ?? ''));
		$categories  = $categoryRaw === 'random'
			? array_map('intval', (array) ($moduleConfig['categories'] ?? []))
			: array_filter(array_map('intval', explode(',', (string) $this->parser->parseNewsValue($categoryRaw, $moduleConfig))));
		$categories  = $categories !== [] ? $categories : array_map('intval', (array) ($moduleConfig['categories'] ?? []));
		$count       = max(1, (int) ($template['categories_count'] ?? 1));
		$count       = min($count, max(1, count($categories)));
		$selected    = (array) $this->parser->randomValue($categories, $count, config: $moduleConfig);
		$selected    = array_values(array_filter(array_map('intval', $selected)));

		if($selected === []) {
			throw new RuntimeException(__('Не удалось определить категории для создаваемой новости'));
		}

		$dateFrom   = (string) ($template['date_from'] ?? 'now') ?: 'now';
		$dateTo     = (string) ($template['date_to'] ?? 'now') ?: 'now';
		$date       = $this->parser->randomDateBetween(
			$this->parser->parseNewsValue($dateFrom, $moduleConfig),
			$this->parser->parseNewsValue($dateTo, $moduleConfig),
		);
		$shortStory = $this->parser->parseNewsValue((string) ($template['short_story'] ?? ''), $moduleConfig);
		$fullStory  = $this->parser->parseNewsValue((string) ($template['full_story'] ?? ''), $moduleConfig);
		$fullStory  = $fullStory !== '' ? $fullStory : $shortStory;

		$schema   = DleDataService::postXfields();
		$altName  = totranslit(stripslashes($title), true, false, $config['translit_url'] ?? false);
		$altName  = $this->ensureUniqueAltName($altName);
		$stories  = $this->prepareStories($parse, $shortStory, $fullStory);
		$metatags = create_metatags(dle_strlen($fullStory) > 12 ? $fullStory : $shortStory);
		$catalogUrl = !empty($config['create_catalog']) ? dle_substr(htmlspecialchars(strip_tags(stripslashes($title)), ENT_QUOTES, 'UTF-8'), 0, 1) : '';

		$xfieldsConfigured = (array) ($template['xfields'] ?? []);
		$postId            = 0;
		$news              = DcApi::news()
			->withTitle($title)
			->withAutor((string) $author['name'])
			->withShortStory($stories['short'])
			->withFullStory($stories['full'])
			->withCategory($selected)
			->with('date', $date)
			->with('descr', (string) $metatags['description'])
			->with('keywords', (string) $metatags['keywords'])
			->with('metatitle', (string) $metatags['title'])
			->with('alt_name', $altName)
			->with('symbol', $catalogUrl)
			->with('allow_br', 1)
			->with('allow_comm', $this->boolFlag($template, 'allow_comm', $moduleConfig))
			->with('approve', $this->boolFlag($template, 'approve', $moduleConfig))
			->with('allow_main', $this->boolFlag($template, 'allow_main', $moduleConfig))
			->with('fixed', $this->boolFlag($template, 'fixed', $moduleConfig))
			->withExtras('user_id', (int) $author['user_id'])
			->withExtras('votes', 0)
			->withExtras('need_pass', 0)
			->withExtras('allow_rate', $this->boolFlag($template, 'allow_rate', $moduleConfig))
			->withExtras('disable_index', $this->boolFlag($template, 'disable_index', $moduleConfig))
			->withExtras('disable_search', $this->boolFlag($template, 'disable_search', $moduleConfig))
			->withExtras('allow_rss', $this->boolFlag($template, 'allow_rss', $moduleConfig))
			->withExtras('allow_rss_dzen', $this->boolFlag($template, 'allow_rss_dzen', $moduleConfig));

		try {
			// create() пишет post + post_extras + post_extras_cats одним Fluent-вызовом (prepared statements).
			$news->create();
			$postId = (int) $news->asArray()['id'];

			if($postId < 1) {
				throw new RuntimeException(__('Не удалось получить id созданной новости'));
			}

			$resolved      = (new XfieldValueResolver($this->parser))->resolve(
				$xfieldsConfigured,
				$schema,
				$moduleConfig,
				true,
				$postId,
			);
			$xfieldsString = (new XfieldValueEncoder())->encode($resolved, $schema);

			// Доп. поля резолвятся уже с id новости (загрузка файлов), поэтому — отдельным UPDATE.
			// save() здесь нельзя: он повторно создал бы дочерние post_extras / post_extras_cats.
			if($xfieldsString !== '') {
				dle_api_update_by_pk('post', $postId, ['xfields' => $xfieldsString]);
			}

			$this->queryOrFail("UPDATE " . USERPREFIX . "_users SET news_num=news_num+1 WHERE user_id='" . (int) $author['user_id'] . "'");

			DcApi::schema('admin_logs')
				->with('name', (string) $author['name'])
				->with('date', (int) $_TIME)
				->with('ip', (string) $_IP)
				->with('action', 1)
				->with('extras', $title)
				->create();
		} catch (Throwable $e) {
			// create() мог упасть уже после INSERT в post — id берём из сущности.
			$postId = $postId > 0 ? $postId : (int) ($news->asArray()['id'] ?? 0);

			if($postId > 0) {
				$this->rollbackPost($postId, (int) $author['user_id']);
			}

			throw $e;
		}

		clear_cache(['news_', 'tagscloud_', 'archives_', 'calendar_', 'topnews_', 'rss', 'stats']);

		return [
			'id'       => $postId,
			'name'     => $title,
			'category' => implode(', ', $selected),
			'date'     => $date,
		];
	}

	/**
	 * Удаляет частично созданную новость после ошибки.
	 */
	private function rollbackPost(int $postId, int $userId): void {
		global $db;

		$db->query("DELETE FROM " . PREFIX . "_post WHERE id='{$postId}'", false);
		$db->query("DELETE FROM " . PREFIX . "_post_extras WHERE news_id='{$postId}'", false);
		$db->query("DELETE FROM " . PREFIX . "_post_extras_cats WHERE news_id='{$postId}'", false);
		$db->query("DELETE FROM " . PREFIX . "_files WHERE news_id='{$postId}'", false);
		$db->query("DELETE FROM " . PREFIX . "_images WHERE news_id='{$postId}'", false);

		if($userId > 0) {
			$db->query("UPDATE " . USERPREFIX . "_users SET news_num=GREATEST(news_num-1, 0) WHERE user_id='{$userId}'", false);
		}
	}

	/**
	 * Выполняет SQL без HTML display_error; при ошибке бросает исключение для JSON-ответа.
	 */
	private function queryOrFail(string $sql): void {
		global $db;

		if($db->query($sql, false) === false) {
			$last = end($db->query_errors_list);
			throw new RuntimeException((string) ($last['error'] ?? __('Ошибка SQL при генерации новости')));
		}
	}

	/**
	 * @return array<string, mixed>
	 */
	private function loadUser(int $userId): array {
		if($userId <= 0) {
			return [];
		}

		$row = DleDataService::user(id: $userId);

		return $row !== [] ? $row : [];
	}

	/**
	 * Значение bool-флага шаблона (`random` → случайное) как 0/1.
	 *
	 * @param array<string, mixed> $template
	 * @param array<string, mixed> $moduleConfig
	 */
	private function boolFlag(array $template, string $key, array $moduleConfig): int {
		return (int) $this->parser->parseBoolValue((string) ($template[$key] ?? 'random'), $moduleConfig);
	}

	/**
	 * Тексты новости без экранирования: запись идёт через SDK (prepared statements).
	 *
	 * @return array{short: string, full: string}
	 */
	private function prepareStories(ParseFilter $parse, string $shortStory, string $fullStory): array {
		global $config;

		$wysiwyg = (bool) ($config['allow_admin_wysiwyg'] ?? false);

		return [
			'short' => $parse->BB_Parse($shortStory, $wysiwyg),
			'full'  => $parse->BB_Parse($fullStory, $wysiwyg),
		];
	}

	private function ensureUniqueAltName(string $altName): string {
		global $config;

		if(!$config['allow_alt_url'] || $config['seo_type']) {
			return $altName;
		}

		$original = $altName;
		$counter  = 1;

		do {
			$found = DcApi::query('post')
				->select(['id'])
				->where('alt_name', $altName)
				->limit(1)
				->fetchAll();

			if($found !== []) {
				$altName = $original . '_' . $counter;
				$counter++;
			}
		} while($found !== []);

		return $altName;
	}

}
