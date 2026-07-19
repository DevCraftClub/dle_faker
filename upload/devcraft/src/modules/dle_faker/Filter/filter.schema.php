<?php

declare(strict_types=1);

/**
 * Схема фильтрации и сортировки страницы шаблонов dle_faker.
 *
 * Гидрируется в `FilterSchema` через `FilterSchema::fromArray()` — сам файл
 * возвращает массив в форме, ожидаемой этим методом.
 *
 * @return array{
 *     sort: array{default: string, columns: array<string, string>},
 *     sections: list<array{title: string, fields: list<array{id: string, type: string, label: string, options?: array<string, string>, metro?: array<string, mixed>}>}>,
 * }
 */
return [
	'sort'     => [
		'default' => 'id',
		'columns' => [
			'id'     => '#',
			'name'   => __('Название'),
			'active' => __('Активен'),
		],
	],
	'sections' => [
		[
			'title'  => __('Фильтр'),
			'fields' => [
				[
					'id'    => 'name',
					'type'  => 'text',
					'label' => __('Название'),
					'metro' => ['db_column' => 'name'],
				],
				[
					'id'      => 'active',
					'type'    => 'multi',
					'label'   => __('Активен'),
					'options' => [
						'1' => __('Да'),
						'0' => __('Нет'),
					],
					'metro'   => ['db_column' => 'active'],
				],
			],
		],
	],
];
