<?php

global $MHDB, $mh;

$filterKeys   = ['filter_name', 'filter_active' => FILTER_VALIDATE_BOOL];
$inputFilters = TwigFilter::getDefaultFilters($filterKeys);
$GET_DATA     = filter_input_array(INPUT_GET, $inputFilters);
$repo         = $MHDB->repository(FakerTemplate::class);
$fakerConfig  = DataManager::getConfig('dle_faker');
$mhConfig     = DataManager::getConfig('maharder');
$twigFilter   = new TwigFilter($repo);

$mh->setBreadcrumb(new BreadCrumb(__('dle_faker', 'Шаблоны'), THIS_SELF . '?' . http_build_query($GET_DATA)));


switch ($GET_DATA['action']) {

	case 'create':
	case 'edit':
		require_once DLEPlugins::Check(MH_MODULES . '/dle_faker/pages/templates_create.php');
		break;

	default:
		require_once DLEPlugins::Check(MH_MODULES . '/dle_faker/pages/templates_all.php');
		break;
}