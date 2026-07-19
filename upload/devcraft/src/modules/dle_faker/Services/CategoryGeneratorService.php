<?php

declare(strict_types=1);

namespace DevCraft\Modules\dle_faker\Services;

use DevCraft\Core\Config\DevCraftConfig;
use DevCraft\Core\Support\DataManager;

/**
 * Создаёт категории DLE (в т.ч. подкатегории) по шаблону Faker.
 */
final class CategoryGeneratorService {

	public function __construct(
		private readonly FakerContentParser $parser = new FakerContentParser(),
	) {}

	/**
	 * @param array<string, mixed> $payload
	 * @param array<string, mixed> $moduleConfig
	 *
	 * @return array{id?: int, name: string, alt_name: string, parentid: int, skipped: bool}
	 */
	public function generate(array $payload, array $moduleConfig): array {
		global $db, $config;

		$nameTemplate = trim((string) ($payload['name'] ?? ''));
		$parentId     = max(0, (int) ($payload['parentid'] ?? 0));

		if($nameTemplate === '') {
			throw new \RuntimeException(__('Шаблон названия категории не может быть пустым'));
		}

		$name = trim(strip_tags($this->parser->parseNewsValue($nameTemplate, $moduleConfig)));
		$name = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');

		if($name === '') {
			throw new \RuntimeException(__('Не удалось сгенерировать название категории'));
		}

		if(mb_strlen($name) > 50) {
			$name = mb_substr($name, 0, 50);
		}

		$altRaw = trim((string) ($payload['alt_name'] ?? ''));
		$altName = $altRaw !== ''
			? totranslit(stripslashes($this->parser->parseNewsValue($altRaw, $moduleConfig)), true, false, $config['translit_url'] ?? false)
			: totranslit(stripslashes(html_entity_decode($name, ENT_QUOTES, 'UTF-8')), true, false, $config['translit_url'] ?? false);

		if($altName === '') {
			throw new \RuntimeException(__('Не удалось сгенерировать alt_name категории'));
		}

		$existingName = $db->super_query(
			"SELECT id FROM " . PREFIX . "_category WHERE name='" . $db->safesql($name) . "' AND parentid='{$parentId}' LIMIT 1"
		);

		if(is_array($existingName) && !empty($existingName['id'])) {
			return [
				'name'     => html_entity_decode($name, ENT_QUOTES, 'UTF-8'),
				'alt_name' => $altName,
				'parentid' => $parentId,
				'skipped'  => true,
			];
		}

		$existingAlt = $db->super_query(
			"SELECT id FROM " . PREFIX . "_category WHERE alt_name='" . $db->safesql($altName) . "' AND parentid='{$parentId}' LIMIT 1"
		);

		if(is_array($existingAlt) && !empty($existingAlt['id'])) {
			return [
				'name'     => html_entity_decode($name, ENT_QUOTES, 'UTF-8'),
				'alt_name' => $altName,
				'parentid' => $parentId,
				'skipped'  => true,
			];
		}

		$db->query(
			"INSERT INTO " . PREFIX . "_category (parentid, name, alt_name, icon, skin, descr, keywords, news_sort, news_msort, news_number, short_tpl, full_tpl, metatitle, show_sub, allow_rss, fulldescr, disable_search, disable_main, disable_rating, disable_comments, enable_dzen, rating_type, schema_org, disable_index) values ('{$parentId}', '" . $db->safesql($name) . "', '" . $db->safesql($altName) . "', '', '', '', '', '', '', '0', '', '', '', '0', '1', '', '0', '0', '0', '0', '0', '-1', '1', '0')"
		);

		$id = (int) $db->insert_id();

		@unlink(ENGINE_DIR . '/cache/system/category.json');
		clear_cache();

		$this->appendToSettings($id, $moduleConfig);

		return [
			'id'       => $id,
			'name'     => html_entity_decode($name, ENT_QUOTES, 'UTF-8'),
			'alt_name' => $altName,
			'parentid' => $parentId,
			'skipped'  => false,
		];
	}

	/**
	 * Дерево категорий для select родителя: id => подпись с отступами.
	 *
	 * @param list<array<string, mixed>> $rows
	 *
	 * @return array<int, string>
	 */
	public static function buildParentTreeOptions(array $rows): array {
		$byParent = [];

		foreach($rows as $row) {
			$parent = (int) ($row['parentid'] ?? 0);
			$id     = (int) ($row['id'] ?? 0);

			if($id <= 0) {
				continue;
			}

			$byParent[$parent][] = $row;
		}

		$options = [];
		self::walkTree($byParent, 0, 0, $options);

		return $options;
	}

	/**
	 * @param array<int, list<array<string, mixed>>> $byParent
	 * @param array<int, string>                     $options
	 */
	private static function walkTree(array $byParent, int $parentId, int $depth, array &$options): void {
		if(!isset($byParent[$parentId])) {
			return;
		}

		foreach($byParent[$parentId] as $row) {
			$id   = (int) $row['id'];
			$name = (string) ($row['name'] ?? '');
			$pad  = $depth > 0 ? str_repeat('— ', $depth) : '';
			$options[$id] = $pad . $name;
			self::walkTree($byParent, $id, $depth + 1, $options);
		}
	}

	/**
	 * @param array<string, mixed> $moduleConfig
	 */
	private function appendToSettings(int $categoryId, array $moduleConfig): void {
		if($categoryId <= 0) {
			return;
		}

		$normalizer = new ConfigNormalizer();
		$config     = $normalizer->normalize($moduleConfig);
		$cats       = array_map('strval', (array) ($config['categories'] ?? []));
		$id         = (string) $categoryId;

		if(!in_array($id, $cats, true)) {
			$cats[] = $id;
		}

		$config['categories'] = $cats;
		DataManager::saveConfig('dle_faker', $normalizer->normalize($config));
		DevCraftConfig::resetCache();
	}

}
