<?php

declare(strict_types=1);

use DevCraft\Core\Enums\FormLayout;
use DevCraft\Form\FormSchemaBuilder;
use DevCraft\Modules\dle_faker\Services\FakerLocaleOptions;

return FormSchemaBuilder::create('dle_faker')
                        ->layout(FormLayout::TABS)
                        ->section(__('Основные'))
	                        ->select('language', __('Локаль Faker'))
		                        ->description(__('Выберите локаль, которая будет использоваться при генерации данных.'))
		                        ->options(FakerLocaleOptions::all())
		                        ->default(FakerLocaleOptions::resolve(null))
	                        ->multi('users', __('Пользователи'))
		                        ->description(__('Один или несколько авторов, от имени которых разрешено создавать новости.'))
		                        ->options([])
		                        ->default([])
	                        ->multi('categories', __('Категории'))
		                        ->description(__('Одна или несколько категорий, в которые можно публиковать сгенерированные новости.'))
		                        ->options([])
		                        ->default([])
	                        ->select('xfields_display_mode', __('Отображать доп. поля'))
		                        ->description(__('Как показывать дополнительные поля в форме шаблона и генераторе пользователей.'))
		                        ->options([
			                        'show_all' => __('Показывать все'),
			                        'single'   => __('Скрыть все, открывать только одно поле'),
			                        'multiple' => __('Скрыть все, можно несколько отображать'),
		                        ])
		                        ->default('show_all')
                        ->build();
