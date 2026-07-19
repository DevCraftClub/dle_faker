<?php

declare(strict_types=1);

namespace DevCraft\Modules\dle_faker\Services;

use DLEPlugins;
use ParseFilter;
use RuntimeException;
use DevCraft\Core\Application;
use Throwable;

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
		global $db, $config, $_TIME, $_IP;

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

		$schema  = Application::instance()->dleData()->postXfields();
		$altName = totranslit(stripslashes($title), true, false, $config['translit_url'] ?? false);
		$altName = $this->ensureUniqueAltName($altName);
		$stories = $this->prepareStories($parse, $shortStory, $fullStory);
		$metatags = create_metatags(dle_strlen($fullStory) > 12 ? $fullStory : $shortStory);
		$categoryString = $db->safesql(implode(',', $selected));
		$catalogUrl     = !empty($config['create_catalog']) ? $db->safesql(dle_substr(htmlspecialchars(strip_tags(stripslashes($title)), ENT_QUOTES, 'UTF-8'), 0, 1)) : '';

		$xfieldsConfigured = (array) ($template['xfields'] ?? []);
		$postId            = 0;

		try {
			$this->queryOrFail(
				"INSERT INTO " . PREFIX . "_post (date, autor, short_story, full_story, xfields, title, descr, keywords, category, alt_name, allow_comm, approve, allow_main, fixed, allow_br, symbol, tags, metatitle) values ('{$date}', '{$db->safesql((string) $author['name'])}', '{$stories['short']}', '{$stories['full']}', '', '{$db->safesql($title)}', '{$db->safesql((string) $metatags['description'])}', '{$db->safesql((string) $metatags['keywords'])}', '{$categoryString}', '{$db->safesql($altName)}', '" . (int) $this->parser->parseBoolValue((string) ($template['allow_comm'] ?? 'random'), $moduleConfig) . "', '" . (int) $this->parser->parseBoolValue((string) ($template['approve'] ?? 'random'), $moduleConfig) . "', '" . (int) $this->parser->parseBoolValue((string) ($template['allow_main'] ?? 'random'), $moduleConfig) . "', '" . (int) $this->parser->parseBoolValue((string) ($template['fixed'] ?? 'random'), $moduleConfig) . "', '1', '{$catalogUrl}', '', '{$db->safesql((string) $metatags['title'])}')"
			);

			$postId = (int) $db->insert_id();

			$resolved      = (new XfieldValueResolver($this->parser))->resolve(
				$xfieldsConfigured,
				$schema,
				$moduleConfig,
				true,
				$postId,
			);
			$xfieldsString = (new XfieldValueEncoder())->encode($resolved, $schema);

			if($xfieldsString !== '') {
				$this->queryOrFail(
					"UPDATE " . PREFIX . "_post SET xfields='{$db->safesql($xfieldsString)}' WHERE id='{$postId}'"
				);
			}

			$this->queryOrFail(
				"INSERT INTO " . PREFIX . "_post_extras (news_id, allow_rate, votes, disable_index, related_ids, access, user_id, disable_search, need_pass, allow_rss, allow_rss_dzen, allowed_country, not_allowed_country) VALUES('{$postId}', '" . (int) $this->parser->parseBoolValue((string) ($template['allow_rate'] ?? 'random'), $moduleConfig) . "', 0, '" . (int) $this->parser->parseBoolValue((string) ($template['disable_index'] ?? 'random'), $moduleConfig) . "', '', '', '" . (int) $author['user_id'] . "', '" . (int) $this->parser->parseBoolValue((string) ($template['disable_search'] ?? 'random'), $moduleConfig) . "', '0', '" . (int) $this->parser->parseBoolValue((string) ($template['allow_rss'] ?? 'random'), $moduleConfig) . "', '" . (int) $this->parser->parseBoolValue((string) ($template['allow_rss_dzen'] ?? 'random'), $moduleConfig) . "', '', '')"
			);

			$catsIds = [];
			foreach($selected as $categoryId) {
				$catsIds[] = '(' . $postId . ', ' . (int) $categoryId . ')';
			}

			$this->queryOrFail("INSERT INTO " . PREFIX . "_post_extras_cats (news_id, cat_id) VALUES " . implode(', ', $catsIds));
			$this->queryOrFail("UPDATE " . USERPREFIX . "_users SET news_num=news_num+1 WHERE user_id='" . (int) $author['user_id'] . "'");
			$this->queryOrFail("INSERT INTO " . USERPREFIX . "_admin_logs (name, date, ip, action, extras) values ('" . $db->safesql((string) $author['name']) . "', '{$_TIME}', '{$_IP}', '1', '" . $db->safesql($title) . "')");
		} catch (Throwable $e) {
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
		global $db;

		if($userId <= 0) {
			return [];
		}

		$row = $db->super_query("SELECT user_id, name FROM " . USERPREFIX . "_users WHERE user_id = '{$userId}'");

		return is_array($row) ? $row : [];
	}

	/**
	 * @return array{short: string, full: string}
	 */
	private function prepareStories(ParseFilter $parse, string $shortStory, string $fullStory): array {
		global $db, $config;

		if($config['allow_admin_wysiwyg']) {
			return [
				'short' => $db->safesql($parse->BB_Parse($shortStory)),
				'full'  => $db->safesql($parse->BB_Parse($fullStory)),
			];
		}

		return [
			'short' => $db->safesql($parse->BB_Parse($shortStory, false)),
			'full'  => $db->safesql($parse->BB_Parse($fullStory, false)),
		];
	}

	private function ensureUniqueAltName(string $altName): string {
		global $db, $config;

		if(!$config['allow_alt_url'] || $config['seo_type']) {
			return $altName;
		}

		$original = $altName;
		$counter  = 1;

		do {
			$found = $db->super_query("SELECT id FROM " . PREFIX . "_post WHERE alt_name = '" . $db->safesql($altName) . "'");

			if($found) {
				$altName = $original . '_' . $counter;
				$counter++;
			}
		} while($found);

		return $altName;
	}

}
