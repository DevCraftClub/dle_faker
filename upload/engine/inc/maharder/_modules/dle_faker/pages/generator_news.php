<?php

global $GET_DATA, $MHDB, $mh;

$repo = $MHDB->repository(FakerTemplate::class);
$templates = [];

foreach ($repo->getActive() as $template) {
	$templates[$template->id] = $template->name;
}

$modVars = [
	'title'       => __('dle_faker', 'Генератор новостей'),
	'templates'   => $templates
];

$mh->setBreadcrumb(new BreadCrumb($modVars['title'], THIS_SELF . '?' . http_build_query($GET_DATA)));


$htmlTemplate = 'dle_faker/generator_posts.html';