<?php

use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Column;

#[Entity(role: 'faker_template', repository: FakerTemplateRepository::class, table: 'faker_templates')]
class FakerTemplate extends BasisModel {
	#[Column(type: 'bigPrimary')]
	public int    $id;
	#[Column(type: 'string')]
	public string $name;
	#[Column(type: 'longText')]
	public string $template;
	#[Column(type: 'boolean', default: true)]
	public bool   $active;

	public function getColumnVal(string $name): mixed {
		return match ($name) {
			'name'     => $this->name,
			'template' => $this->template,
			'active'   => $this->active,
			default    => $this->id
		};
	}
}