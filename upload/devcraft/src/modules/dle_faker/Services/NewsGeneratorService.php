<?php

declare(strict_types=1);

namespace DevCraft\Modules\dle_faker\Services;

use DLEPlugins;
use ParseFilter;
use DevCraft\Core\Application;

/**
 * Генерирует новости DLE по шаблону DLE Faker.
 */
final class NewsGeneratorService {

	public function __construct(
		private readonly FakerContentParser $parser = new FakerContentParser(),
	) {}

	/**
	 * @param array<string, mixed> $template
	 * @param array<string, mixed> $config
	 *
	 * @return array<string, mixed>
	 */
	public function generate(array $template, array $config): array {
		global $db, $config, $_TIME, $_IP;

		if(!class_exists('ParseFilter')) {
			require_once DLEPlugins::Check(ENGINE_DIR . '/classes/parse.class.php');
		}

		$parse = new ParseFilter();

		$title = $parse->process(
			filter_var($this->parser->parseNewsValue((string) ($template['title'] ?? ''), $config), FILTER_SANITIZE_FULL_SPECIAL_CHARS)
		);

		if($title === '') {
			throw new \RuntimeException(__('Шаблон заголовка не может быть пустым'));
		}

		$authorIds = array_filter(array_map('intval', explode(',', (string) $this->parser->parseNewsValue((string) ($template['autor'] ?? ''), $config))));
		$authorIds = $authorIds !== [] ? $authorIds : array_map('intval', (array) ($config['users'] ?? []));
		$authorId  = (int) ($this->parser->randomValue($authorIds, config: $config) ?? 0);
		$author    = $this->loadUser($authorId);

		if($author === []) {
			throw new \RuntimeException(__('Не удалось определить автора для создаваемой новости'));
		}

		$categories = array_filter(array_map('intval', explode(',', (string) $this->parser->parseNewsValue((string) ($template['category'] ?? ''), $config))));
		$categories = $categories !== [] ? $categories : array_map('intval', (array) ($config['categories'] ?? []));
		$count      = max(1, (int) ($config['categories_count'] ?? 1));
		$selected   = (array) $this->parser->randomValue($categories, $count, config: $config);
		$selected   = array_values(array_filter(array_map('intval', $selected)));

		if($selected === []) {
			throw new \RuntimeException(__('Не удалось определить категории для создаваемой новости'));
		}

		$dateFrom   = (string) ($template['date_from_alt'] ?? $template['date_from'] ?? 'now');
		$dateTo     = (string) ($template['date_to_alt'] ?? $template['date_to'] ?? 'now');
		$date       = $this->parser->randomDateBetween(
			$this->parser->parseNewsValue($dateFrom, $config),
			$this->parser->parseNewsValue($dateTo, $config),
		);
		$shortStory = $this->parser->parseNewsValue((string) ($template['short_story'] ?? ''), $config);
		$fullStory  = $this->parser->parseNewsValue((string) ($template['full_story'] ?? ''), $config);
		$fullStory  = $fullStory !== '' ? $fullStory : $shortStory;

		$xfields = [];
		$schema = Application::instance()->dleData()->postXfields();
		$resolved = (new XfieldValueResolver($this->parser))->resolve(
			(array) ($template['xfields'] ?? []),
			$schema,
			$config,
			true,
		);
		$xfieldsString = (new XfieldValueEncoder())->encode($resolved, $schema);

		$altName = totranslit(stripslashes($title), true, false, $config['translit_url']);
		$altName = $this->ensureUniqueAltName($altName);
		$stories = $this->prepareStories($parse, $shortStory, $fullStory);
		$metatags = create_metatags(dle_strlen($fullStory) > 12 ? $fullStory : $shortStory);
		$categoryString = $db->safesql(implode(',', $selected));
		$catalogUrl     = $config['create_catalog'] ? $db->safesql(dle_substr(htmlspecialchars(strip_tags(stripslashes($title)), ENT_QUOTES, 'UTF-8'), 0, 1)) : '';

		$db->query(
			"INSERT INTO " . PREFIX . "_post (date, autor, short_story, full_story, xfields, title, descr, keywords, category, alt_name, allow_comm, approve, allow_main, fixed, allow_br, symbol, tags, metatitle) values ('{$date}', '{$db->safesql((string) $author['name'])}', '{$stories['short']}', '{$stories['full']}', '{$db->safesql($xfieldsString)}', '{$db->safesql($title)}', '{$db->safesql((string) $metatags['description'])}', '{$db->safesql((string) $metatags['keywords'])}', '{$categoryString}', '{$db->safesql($altName)}', '" . (int) $this->parser->parseBoolValue((string) ($template['allow_comm'] ?? 'random'), $config) . "', '" . (int) $this->parser->parseBoolValue((string) ($template['approve'] ?? 'random'), $config) . "', '" . (int) $this->parser->parseBoolValue((string) ($template['allow_main'] ?? 'random'), $config) . "', '" . (int) $this->parser->parseBoolValue((string) ($template['fixed'] ?? 'random'), $config) . "', '1', '{$catalogUrl}', '', '{$db->safesql((string) $metatags['title'])}')"
		);

		$postId = (int) $db->insert_id();

		$db->query(
			"INSERT INTO " . PREFIX . "_post_extras (news_id, allow_rate, votes, disable_index, related_ids, access, user_id, disable_search, need_pass, allow_rss, allow_rss_turbo, allow_rss_dzen) VALUES('{$postId}', '" . (int) $this->parser->parseBoolValue((string) ($template['allow_rate'] ?? 'random'), $config) . "', 0, '" . (int) $this->parser->parseBoolValue((string) ($template['disable_index'] ?? 'random'), $config) . "', '', '', '" . (int) $author['user_id'] . "', '" . (int) $this->parser->parseBoolValue((string) ($template['disable_search'] ?? 'random'), $config) . "', '0', '" . (int) $this->parser->parseBoolValue((string) ($template['allow_rss'] ?? 'random'), $config) . "', '" . (int) $this->parser->parseBoolValue((string) ($template['allow_rss_turbo'] ?? 'random'), $config) . "', '" . (int) $this->parser->parseBoolValue((string) ($template['allow_rss_dzen'] ?? 'random'), $config) . "')"
		);

		$catsIds = [];
		foreach($selected as $categoryId) {
			$catsIds[] = '(' . $postId . ', ' . (int) $categoryId . ')';
		}

		$db->query("INSERT INTO " . PREFIX . "_post_extras_cats (news_id, cat_id) VALUES " . implode(', ', $catsIds));
		$db->query("UPDATE " . USERPREFIX . "_users SET news_num=news_num+1 WHERE user_id='" . (int) $author['user_id'] . "'");
		$db->query("INSERT INTO " . USERPREFIX . "_admin_logs (name, date, ip, action, extras) values ('" . $db->safesql((string) $author['name']) . "', '{$_TIME}', '{$_IP}', '1', '" . $db->safesql($title) . "')");

		clear_cache(['news_', 'tagscloud_', 'archives_', 'calendar_', 'topnews_', 'rss', 'stats']);

		return [
			'id'       => $postId,
			'name'     => $title,
			'category' => implode(', ', $selected),
			'date'     => $date,
		];
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
