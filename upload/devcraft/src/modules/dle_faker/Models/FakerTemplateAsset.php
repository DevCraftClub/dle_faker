<?php

declare(strict_types=1);

namespace DevCraft\Modules\dle_faker\Models;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Table\Index;
use DevCraft\Modules\dle_faker\Repositories\FakerTemplateAssetRepository;

/**
 * Вложение, привязанное к шаблону генерации новостей.
 */
#[Entity(role: 'faker_template_asset', repository: FakerTemplateAssetRepository::class, table: 'faker_template_assets')]
#[Index(columns: ['template_id'])]
final class FakerTemplateAsset {

	#[Column(type: 'bigPrimary')]
	public int $id;

	#[Column(type: 'integer', default: 0)]
	public int $template_id = 0;

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

	public function getColumnVal(string $name): mixed {
		return match ($name) {
			'id'            => $this->id,
			'template_id'   => $this->template_id,
			'kind'          => $this->kind,
			'original_name' => $this->original_name,
			'stored_name'   => $this->stored_name,
			'mime'          => $this->mime,
			'size_bytes'    => $this->size_bytes,
			default         => NULL,
		};
	}

}
