<?php

declare(strict_types=1);

namespace DevCraft\Modules\dle_faker\Models;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Table\Index;
use DevCraft\Core\Abstracts\AbstractEntity;
use DevCraft\Modules\dle_faker\Repositories\FakerStaticFileRepository;

/**
 * Файл общей библиотеки статичных ресурсов для генерации.
 *
 * Момент загрузки файла — унаследованное поле `createdAt` (колонка `created_at`,
 * совпадает с исходной legacy-колонкой), отдельного поля не заводим.
 */
#[Entity(role: 'faker_static_file', repository: FakerStaticFileRepository::class, table: 'faker_static_files')]
#[Index(columns: ['kind'])]
class FakerStaticFile extends AbstractEntity {

	#[Column(type: 'string', size: 16)]
	public string $kind = 'file';

	#[Column(type: 'string')]
	public string $original_name = '';

	#[Column(type: 'string')]
	public string $stored_name = '';

	#[Column(type: 'string', default: '', size: 128)]
	public string $mime = '';

	#[Column(type: 'integer', default: 0)]
	public int $size_bytes = 0;

	public function __construct() {
		$this->createdAt = new \DateTimeImmutable();
	}

	public function getColumnVal(string $name): mixed {
		return match ($name) {
			'id'            => $this->id(),
			'kind'          => $this->kind,
			'original_name' => $this->original_name,
			'stored_name'   => $this->stored_name,
			'mime'          => $this->mime,
			'size_bytes'    => $this->size_bytes,
			'created_at'    => $this->createdAt,
			default         => NULL,
		};
	}

}
