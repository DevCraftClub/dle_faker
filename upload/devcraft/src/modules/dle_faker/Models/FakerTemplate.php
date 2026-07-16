<?php

declare(strict_types=1);

namespace DevCraft\Modules\dle_faker\Models;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Table\Index;
use DevCraft\Modules\dle_faker\Repositories\FakerTemplateRepository;

/**
 * Шаблон генерации, совместимый с legacy-таблицей `faker_templates`.
 */
#[Entity(role: 'faker_template', repository: FakerTemplateRepository::class, table: 'faker_templates')]
#[Index(columns: ['name'], unique: true)]
final class FakerTemplate {

	#[Column(type: 'bigPrimary')]
	public int $id;

	#[Column(type: 'string')]
	public string $name = '';

	#[Column(type: 'longText')]
	public string $template = '{}';

	#[Column(type: 'boolean', default: true)]
	public bool $active = true;

	public function getColumnVal(string $name): mixed {
		return match ($name) {
			'id'       => $this->id,
			'name'     => $this->name,
			'template' => $this->template,
			'active'   => $this->active,
			default    => NULL,
		};
	}

}
