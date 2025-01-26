<?php
global $is_logged, $dle_login_hash, $parsedData, $MHDB, $mh_admin;

if (!$is_logged) {
	exit('error');
}

if ('' == $_REQUEST['user_hash'] or $_REQUEST['user_hash'] != $dle_login_hash) {
	exit('error');
}

$filter               = [
	'name'            => FILTER_SANITIZE_FULL_SPECIAL_CHARS | FILTER_FLAG_EMPTY_STRING_NULL,
	'active_template' => FILTER_VALIDATE_BOOL,
	'autor'           => FILTER_SANITIZE_FULL_SPECIAL_CHARS | FILTER_FLAG_EMPTY_STRING_NULL,
	'title'           => FILTER_SANITIZE_FULL_SPECIAL_CHARS | FILTER_FLAG_EMPTY_STRING_NULL,
	'category'        => FILTER_SANITIZE_FULL_SPECIAL_CHARS | FILTER_FLAG_EMPTY_STRING_NULL,
	'date_from'       => FILTER_SANITIZE_FULL_SPECIAL_CHARS | FILTER_FLAG_EMPTY_STRING_NULL,
	'date_from_alt'   => FILTER_SANITIZE_FULL_SPECIAL_CHARS | FILTER_FLAG_EMPTY_STRING_NULL,
	'date_to'         => FILTER_SANITIZE_FULL_SPECIAL_CHARS | FILTER_FLAG_EMPTY_STRING_NULL,
	'date_to_alt'     => FILTER_SANITIZE_FULL_SPECIAL_CHARS | FILTER_FLAG_EMPTY_STRING_NULL,
	'short_story'     => FILTER_SANITIZE_FULL_SPECIAL_CHARS | FILTER_FLAG_EMPTY_STRING_NULL,
	'full_story'      => FILTER_SANITIZE_FULL_SPECIAL_CHARS | FILTER_FLAG_EMPTY_STRING_NULL,
	'xfields'         => FILTER_REQUIRE_ARRAY,
	'allow_main'      => FILTER_SANITIZE_FULL_SPECIAL_CHARS | FILTER_FLAG_EMPTY_STRING_NULL,
	'approve'         => FILTER_SANITIZE_FULL_SPECIAL_CHARS | FILTER_FLAG_EMPTY_STRING_NULL,
	'fixed'           => FILTER_SANITIZE_FULL_SPECIAL_CHARS | FILTER_FLAG_EMPTY_STRING_NULL,
	'allow_comm'      => FILTER_SANITIZE_FULL_SPECIAL_CHARS | FILTER_FLAG_EMPTY_STRING_NULL,
	'allow_rate'      => FILTER_SANITIZE_FULL_SPECIAL_CHARS | FILTER_FLAG_EMPTY_STRING_NULL,
	'disable_index'   => FILTER_SANITIZE_FULL_SPECIAL_CHARS | FILTER_FLAG_EMPTY_STRING_NULL,
	'disable_search'  => FILTER_SANITIZE_FULL_SPECIAL_CHARS | FILTER_FLAG_EMPTY_STRING_NULL,
	'allow_rss'       => FILTER_SANITIZE_FULL_SPECIAL_CHARS | FILTER_FLAG_EMPTY_STRING_NULL,
	'allow_rss_turbo' => FILTER_SANITIZE_FULL_SPECIAL_CHARS | FILTER_FLAG_EMPTY_STRING_NULL,
	'allow_rss_dzen'  => FILTER_SANITIZE_FULL_SPECIAL_CHARS | FILTER_FLAG_EMPTY_STRING_NULL,
];
$inputData            = filter_var_array($parsedData, $filter);
$inputData['xfields'] = $inputData['xfields'] !== false ?: $parsedData['xfields'];

if (empty($inputData['name'])) {
	echo (new ErrorResponseAjax())
		->setData([__(\'$2\')])->setMeta(['name'])->send();
	exit;
}
if (empty($inputData['autor'])) {
	echo (new ErrorResponseAjax())
		->setData([__(\'$2\')])->setMeta(['autor'])->send();
	exit;
}
if (empty($inputData['title'])) {
	echo (new ErrorResponseAjax())
		->setData([__(\'$2\')])->setMeta(['title'])->send();
	exit;
}
if (empty($inputData['category'])) {
	echo (new ErrorResponseAjax())
		->setData([__(\'$2\')])->setMeta(['category'])->send();
	exit;
}
if (empty($inputData['short_story'])) {
	echo (new ErrorResponseAjax())
		->setData([__(\'$2\')])->setMeta(['short_story'])->send();
	exit;
}
if (empty($inputData['allow_main'])) $inputData['allow_main'] = 'random';
if (empty($inputData['approve'])) $inputData['approve'] = 'random';
if (empty($inputData['fixed'])) $inputData['fixed'] = 'random';
if (empty($inputData['allow_comm'])) $inputData['allow_comm'] = 'random';
if (empty($inputData['allow_rate'])) $inputData['allow_rate'] = 'random';
if (empty($inputData['disable_index'])) $inputData['disable_index'] = 'random';
if (empty($inputData['disable_search'])) $inputData['disable_search'] = 'random';
if (empty($inputData['allow_rss'])) $inputData['allow_rss'] = 'random';
if (empty($inputData['allow_rss_turbo'])) $inputData['allow_rss_turbo'] = 'random';
if (empty($inputData['allow_rss_dzen'])) $inputData['allow_rss_dzen'] = 'random';

$templateData = array_diff_key($inputData, array_flip(['name', 'active_template']));

try {
	$id = filter_var($parsedData['id'], FILTER_VALIDATE_INT);
	if ($id) {
		$template = $MHDB->get(FakerTemplate::class, $id);
	} else {
		$template = new FakerTemplate();
	}

	$template->name     = $inputData['name'];
	$template->active   = (bool)$inputData['active_template'];
	$template->template = json_encode($templateData, JSON_UNESCAPED_UNICODE);

	if ($id) {
		$MHDB->update($template);
		echo (new SuccessResponseAjax(201))
			->setData([__(\'$2\')])->setRedirect(
				$mh_admin->getDleUrl() . '?mod=dle_faker&sites=template'
			)->send();
	} else {
		$MHDB->create($template);
		echo (new SuccessResponseAjax())
			->setData([__(\'$2\')])->setRedirect(
				$mh_admin->getDleUrl() . '?mod=dle_faker&sites=template'
			)->send();
	}

} catch (Exception $e) {
	echo (new ErrorResponseAjax())->setData([$e->getMessage()])->send();
	LogGenerator::generateLog(
		'DLE Faker',
		'ajax/templates_create',
		$e->getMessage()
	);
}
exit;