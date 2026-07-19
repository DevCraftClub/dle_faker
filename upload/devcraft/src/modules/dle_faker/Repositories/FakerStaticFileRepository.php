<?php

declare(strict_types=1);

namespace DevCraft\Modules\dle_faker\Repositories;

use DevCraft\Core\Abstracts\AbstractRepository;
use DevCraft\Modules\dle_faker\Models\FakerStaticFile;

/**
 * Репозиторий статичных файлов библиотеки.
 */
final class FakerStaticFileRepository extends AbstractRepository {

	/**
	 * @return FakerStaticFile[]
	 */
	public function findByKind(string $kind): array {
		/** @var FakerStaticFile[] $items */
		$items = $this->select()->where('kind', $kind)->orderBy('id', 'DESC')->fetchAll();

		return $items;
	}

	public function findOneById(int $id): ?FakerStaticFile {
		/** @var FakerStaticFile|null $entity */
		$entity = $this->select()->where('id', $id)->fetchOne();

		return $entity;
	}

}
