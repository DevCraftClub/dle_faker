<?php

global $mh;
$modVars = [
	'title' => __('dle_faker', 'Создание нового шаблона'),
	'xfields' => $mh->loadXfields()
];

$htmlTemplate = 'dle_faker/templates_create.html';