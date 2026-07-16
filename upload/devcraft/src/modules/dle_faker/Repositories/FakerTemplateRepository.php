<?php

declare(strict_types=1);

namespace DevCraft\Modules\dle_faker\Repositories;

use DevCraft\Core\Abstracts\AbstractRepository;
use DevCraft\Modules\dle_faker\Models\FakerTemplate;

/**
 * Репозиторий шаблонов генерации.
 */
final class FakerTemplateRepository extends AbstractRepository {

	/**
	 * @return FakerTemplate[]
	 */
	public function getActive(): array {
		/** @var FakerTemplate[] $items */
		$items = $this->select()->where('active', true)->orderBy('name')->fetchAll();

		return $items;
	}

	public function findOneById(int $id): ?FakerTemplate {
		/** @var FakerTemplate|null $entity */
		$entity = $this->select()->where('id', $id)->fetchOne();

		return $entity;
	}

}
