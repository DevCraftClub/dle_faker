<?php

global $GET_DATA, $MHDB, $mh, $breadcrumbs;

$modVars = [
	'title'       => __('dle_faker', 'Генератор пользователей'),
	'usergroups'   => $mh->getUserGroups()
];

$mh->setBreadcrumb(new BreadCrumb($modVars['title'], THIS_SELF . '?' . http_build_query($GET_DATA)));

$htmlTemplate = 'dle_faker/generator_users.html';