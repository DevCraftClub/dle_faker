<?php

global $mh, $MHDB, $GET_DATA;

$id = filter_var($_GET['id'], FILTER_VALIDATE_INT);

$settings = [];
$title = __('Создание нового шаблона');

if ($id) {
	$template = $MHDB->get(FakerTemplate::class, $id);
	$settings = json_decode($template->template, true, 512, JSON_THROW_ON_ERROR);
	$settings['name'] = $template->name;
	$settings['active_template'] = $template->active;
	$title = __('Редактирование шаблона: :name', [':name' => $template->name]);
}

$modVars = [
	'title'    => $title,
	'xfields'  => $mh->loadXfields(),
	'settings' => $settings,
	'id'       => $id
];

$mh->setBreadcrumb(new BreadCrumb($modVars['title'], THIS_SELF . '?' . http_build_query($GET_DATA)));

$htmlTemplate = 'dle_faker/templates_create.html';