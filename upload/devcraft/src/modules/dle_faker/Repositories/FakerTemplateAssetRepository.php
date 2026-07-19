<?php

declare(strict_types=1);

namespace DevCraft\Modules\dle_faker\Repositories;

use DevCraft\Core\Abstracts\AbstractRepository;
use DevCraft\Modules\dle_faker\Models\FakerTemplateAsset;

/**
 * Репозиторий вложений шаблонов новостей.
 */
final class FakerTemplateAssetRepository extends AbstractRepository {

	/**
	 * @return FakerTemplateAsset[]
	 */
	public function findByTemplateId(int $templateId): array {
		/** @var FakerTemplateAsset[] $items */
		$items = $this->select()->where('template_id', $templateId)->orderBy('id', 'DESC')->fetchAll();

		return $items;
	}

	public function findOneById(int $id): ?FakerTemplateAsset {
		/** @var FakerTemplateAsset|null $entity */
		$entity = $this->select()->where('id', $id)->fetchOne();

		return $entity;
	}

}
