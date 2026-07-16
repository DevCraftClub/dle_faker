<?php

declare(strict_types=1);

namespace DevCraft\Modules\dle_faker\Ajax;

use DevCraft\Core\Config\DevCraftConfig;
use DevCraft\Core\Support\DataManager;
use DevCraft\Core\Http\AjaxRequest;
use DevCraft\Core\Http\JsonResponse;
use DevCraft\Core\Interfaces\AjaxHandlerInterface;
use DevCraft\Core\Interfaces\ResponseInterface;
use DevCraft\Core\Application;
use DevCraft\Modules\dle_faker\Services\ConfigNormalizer;
use DevCraft\Modules\dle_faker\Services\UserGeneratorService;
use DevCraft\Modules\dle_faker\Services\XfieldFormService;

/**
 * Генерация пользователя по шаблонам DLE Faker.
 */
final class GenerateUsersHandler implements AjaxHandlerInterface {

	public function handle(AjaxRequest $request): ResponseInterface {
		$normalizer = new ConfigNormalizer();
		$config     = $normalizer->normalize(DataManager::getConfig('dle_faker'));
		$data       = $request->data;

		if(isset($data['user_xfields']) && is_array($data['user_xfields'])) {
			$schema   = Application::instance()->dleData()->userXfields();
			$xfResult = (new XfieldFormService())->normalizeIncoming($data['user_xfields'], $schema, false);

			if($xfResult['errors'] !== []) {
				return JsonResponse::fail(__('Ошибка'), __('Проверьте обязательные доп. поля'), 'validation', 422, [
					'fields' => $xfResult['errors'],
				]);
			}

			$config['user_xfields'] = $xfResult['values'];
			DataManager::saveConfig('dle_faker', $normalizer->normalize($config));
			DevCraftConfig::resetCache();
			$data['user_xfields'] = $xfResult['values'];
		}

		try {
			$result = (new UserGeneratorService())->generate($data, $config);
		} catch(\Throwable $e) {
			return JsonResponse::fail(__('Ошибка'), $e->getMessage(), 'validation', 400);
		}

		return JsonResponse::toast(__('Генерация пользователя завершена'), [
			'generated' => 1,
			'user'      => $result,
		]);
	}

}
