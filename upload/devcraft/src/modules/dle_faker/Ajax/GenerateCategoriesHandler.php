<?php

declare(strict_types=1);

namespace DevCraft\Modules\dle_faker\Ajax;

use DevCraft\Core\Support\DataManager;
use DevCraft\Core\Http\AjaxRequest;
use DevCraft\Core\Http\JsonResponse;
use DevCraft\Core\Interfaces\AjaxHandlerInterface;
use DevCraft\Core\Interfaces\ResponseInterface;
use DevCraft\Modules\dle_faker\Services\CategoryGeneratorService;
use DevCraft\Modules\dle_faker\Services\ConfigNormalizer;

/**
 * Генерация одной категории DLE.
 */
final class GenerateCategoriesHandler implements AjaxHandlerInterface {

	public function handle(AjaxRequest $request): ResponseInterface {
		$normalizer = new ConfigNormalizer();
		$config     = $normalizer->normalize(DataManager::getConfig('dle_faker'));
		$data       = $request->data;

		try {
			$result = (new CategoryGeneratorService())->generate($data, $config);
		} catch(\Throwable $e) {
			return JsonResponse::fail(__('Ошибка'), $e->getMessage(), 'validation', 400);
		}

		$message = !empty($result['skipped'])
			? __('Категория пропущена (уже существует)')
			: __('Генерация категории завершена');

		return JsonResponse::toast($message, [
			'generated' => empty($result['skipped']) ? 1 : 0,
			'skipped'   => !empty($result['skipped']),
			'category'  => $result,
		]);
	}

}
