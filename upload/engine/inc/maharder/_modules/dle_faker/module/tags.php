<?php

global $mh;


$GET_DATA = filter_input_array(INPUT_GET);

$modVars = [
	'title' => __('dle_faker', 'Перечень тегов для генерации данных'),
	'user_tags' => include DLEPlugins::Check(MH_MODULES . '/dle_faker/utils/user_tags.php'),
	'post_tags' => include DLEPlugins::Check(MH_MODULES . '/dle_faker/utils/post_tags.php'),
	'helper_tags' => include DLEPlugins::Check(MH_MODULES . '/dle_faker/utils/helper_tags.php'),
];

$mh->setBreadcrumb(new BreadCrumb($modVars['title'], $mh->getLinkUrl('tags')));

$htmlTemplate = 'dle_faker/tags.html';
