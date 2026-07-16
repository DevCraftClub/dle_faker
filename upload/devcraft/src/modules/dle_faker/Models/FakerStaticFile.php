<?php

declare(strict_types=1);

namespace DevCraft\Modules\dle_faker\Models;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Table\Index;
use DateTimeImmutable;
use DevCraft\Modules\dle_faker\Repositories\FakerStaticFileRepository;

/**
 * Файл общей библиотеки статичных ресурсов для генерации.
 */
#[Entity(role: 'faker_static_file', repository: FakerStaticFileRepository::class, table: 'faker_static_files')]
#[Index(columns: ['kind'])]
class FakerStaticFile {

	#[Column(type: 'bigPrimary')]
	public int $id;

	#[Column(type: 'string', size: 16)]
	public string $kind = 'file';

	#[Column(type: 'string')]
	public string $original_name = '';

	#[Column(type: 'string')]
	public string $stored_name = '';

	#[Column(type: 'string', size: 128, default: '')]
	public string $mime = '';

	#[Column(type: 'integer', default: 0)]
	public int $size_bytes = 0;

	#[Column(type: 'datetime')]
	public DateTimeImmutable $created_at;

	public function getColumnVal(string $name): mixed {
		return match ($name) {
			'id'            => $this->id,
			'kind'          => $this->kind,
			'original_name' => $this->original_name,
			'stored_name'   => $this->stored_name,
			'mime'          => $this->mime,
			'size_bytes'    => $this->size_bytes,
			'created_at'    => $this->created_at,
			default         => NULL,
		};
	}

}
