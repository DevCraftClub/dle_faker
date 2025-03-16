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
	echo (new ErrorResponseAjax())->setData([__('Некорректные данные: :data.', [':data' => 'id'])])->send();
	exit;
}

try {
	$template = $MHDB->delete(FakerTemplate::class, $id);
	echo (new SuccessResponseAjax(201))->setData([$method == 'activate_template' ? __('Шаблон был включён') : __('Шаблон был выключён')])->send();
} catch (Exception $e) {
	echo (new ErrorResponseAjax())->setData([$e->getMessage()])->send();
}
exit;