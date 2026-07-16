<?php

declare(strict_types=1);

namespace DevCraft\Modules\dle_faker\Ajax;

use DevCraft\Core\Config\DevCraftConfig;
use DevCraft\Core\Http\AjaxRequest;
use DevCraft\Core\Http\JsonResponse;
use DevCraft\Core\Interfaces\AjaxHandlerInterface;
use DevCraft\Core\Interfaces\ResponseInterface;
use DevCraft\Core\Support\DataManager;
use DevCraft\Core\Application;
use DevCraft\Modules\dle_faker\Services\ConfigNormalizer;
use DevCraft\Modules\dle_faker\Services\XfieldFormService;

/**
 * Сохраняет пресеты user xfields в конфиг модуля.
 */
final class SaveUserXfieldsHandler implements AjaxHandlerInterface {

	public function handle(AjaxRequest $request): ResponseInterface {
		$schema = Application::instance()->dleData()->userXfields();
		$input  = $request->data['user_xfields'] ?? [];

		if(!is_array($input)) {
			$input = [];
		}

		$form   = new XfieldFormService();
		$result = $form->normalizeIncoming($input, $schema, false);

		if($result['errors'] !== []) {
			return JsonResponse::fail(__('Ошибка'), __('Проверьте обязательные доп. поля'), 'validation', 422, [
				'fields' => $result['errors'],
			]);
		}

		$normalizer = new ConfigNormalizer();
		$config     = $normalizer->normalize(DataManager::getConfig('dle_faker'));
		$config['user_xfields'] = $result['values'];
		DataManager::saveConfig('dle_faker', $normalizer->normalize($config));
		DevCraftConfig::resetCache();

		return JsonResponse::toast(__('Доп. поля пользователей сохранены'), ['saved' => true]);
	}

}
