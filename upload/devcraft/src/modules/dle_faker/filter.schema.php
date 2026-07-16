<?php

declare(strict_types=1);

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
