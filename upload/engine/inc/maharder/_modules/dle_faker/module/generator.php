<?php

global $MHDB, $mh;


$GET_DATA = filter_input_array(INPUT_GET);

$mh->setBreadcrumb(new BreadCrumb(__('Доступные генераторы'), $mh->getLinkUrl('generator')));

switch ($GET_DATA['action']) {

	case 'users':
		require_once DLEPlugins::Check(MH_MODULES . '/dle_faker/pages/generator_users.php');
		break;

	case 'news':
		require_once DLEPlugins::Check(MH_MODULES . '/dle_faker/pages/generator_news.php');
		break;

	default:
		require_once DLEPlugins::Check(MH_MODULES . '/dle_faker/pages/generator_all.php');
		break;
}