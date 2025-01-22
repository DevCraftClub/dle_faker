<?php

global $GET_DATA, $filterKeys, $repo, $mhConfig, $twigFilter, $mh;

use Spiral\Pagination\Paginator;

foreach ($filterKeys as $key => $filter) {
	$GET_DATA[$key] = isset($_GET[$key]) ? DataManager::sanitizeArrayInput(
		$_GET[$key],
		[FILTER_SANITIZE_FULL_SPECIAL_CHARS]
	) : null;
}

$whereClause = null;

$filters = [];
if ($GET_DATA['filter_name']) $filters[] = [
	'name' => ['like' => $GET_DATA['filter_name']]
];
if ($GET_DATA['filter_active']) $filters[] = [
	'active' => $GET_DATA['filter_active'] == 'yes'
];

if (count($filters)) $whereClause['@and'] = $filters;
$fakerTemplates = $repo->select()->where($whereClause);

$cur_page       = $GET_DATA['page'] ?? 1;
$total_pages    = (int)@ceil($fakerTemplates->count() / $mhConfig['list_count']);
$start          = isset($GET_DATA['page']) ? (((int)$cur_page - 1) * $mhConfig['list_count']) : 0;
$order          = $GET_DATA['order'] ?? 'id';
$sort           = TwigFilter::getSort($GET_DATA['sort'] ?? 'DESC');
$fakerTemplates = $fakerTemplates->orderBy($order, $sort);
$paginator      = new Paginator($mhConfig['list_count']);
$paginator->withPage($cur_page)->paginate($fakerTemplates);

$modVars = [
	'title'       => __('dle_faker', 'Шаблоны'),
	'templates'   => $fakerTemplates->fetchAll(),
	'total_pages' => $total_pages,
	'page'        => $cur_page,
	'order'       => $order,
	'sort'        => $sort,
	'filters'     => array_merge(
		$twigFilter->createFilter('name', 'text', __('dle_faker', 'Название')),
		$twigFilter->createFilter('active', 'select', __('dle_faker', 'Активные'), choices: ['' => __('mhadmin', 'Все'), 'no' => __('mhadmin', 'Нет'), 'yes' => __('mhadmin', 'Да')]),
	)
];

if ($cur_page > 1) {
	$mh->setBreadcrumb(new BreadCrumb(__('mhadmin', 'Страница %page%', ['%page%' => $cur_page]), THIS_SELF . '?' . http_build_query($GET_DATA)));

}

$htmlTemplate = 'dle_faker/templates_all.html';