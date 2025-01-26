<?php

global $is_logged, $dle_login_hash, $parsedData, $MHDB, $method;

if (!$is_logged) {
	exit('error');
}

if ('' == $_REQUEST['user_hash'] or $_REQUEST['user_hash'] != $dle_login_hash) {
	exit('error');
}

$id = filter_var($parsedData['id'], FILTER_VALIDATE_INT);

if (!$id) {
	echo (new ErrorResponseAjax())->setData([__('dle_faker', 'Некорректные данные: :data.', [':data' => 'id'])])->send();
	exit;
}

$template = $MHDB->get(FakerTemplate::class, $id);

if (!$template) {
	echo (new ErrorResponseAjax(404))->setData([__(\'$2\')])->send();
	exit;
}

try {
	$template->active = $method == 'activate_template';
	$MHDB->update($template);
	echo (new SuccessResponseAjax(201))->setData([$method == 'activate_template' ? __(\'$2\') : __(\'$2\')])->send();
} catch (Exception $e) {
	echo (new ErrorResponseAjax())->setData([$e->getMessage()])->send();
	LogGenerator::generateLog(
		'DLE Faker',
		'ajax/templates_change_status',
		$e->getMessage()
	);
}
exit;