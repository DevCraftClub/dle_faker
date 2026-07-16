<?php

declare(strict_types=1);

namespace DevCraft\Modules\dle_faker\Ajax;

use DevCraft\Core\Application;
use DevCraft\Core\Support\DataManager;
use DevCraft\Core\Http\AjaxRequest;
use DevCraft\Core\Http\JsonResponse;
use DevCraft\Core\Interfaces\AjaxHandlerInterface;
use DevCraft\Core\Interfaces\ResponseInterface;
use DevCraft\Modules\dle_faker\Models\FakerTemplate;
use DevCraft\Modules\dle_faker\Repositories\FakerTemplateRepository;
use DevCraft\Modules\dle_faker\Services\ConfigNormalizer;
use DevCraft\Modules\dle_faker\Services\NewsGeneratorService;

/**
 * Генерация новости по выбранному шаблону.
 */
final class GeneratePostsHandler implements AjaxHandlerInterface {

	public function handle(AjaxRequest $request): ResponseInterface {
		$templateId = (int) ($request->data['template'] ?? 0);

		if($templateId <= 0) {
			return JsonResponse::fail(__('Ошибка'), __('Шаблон не может быть пустым'), 'validation', 422, [
				'fields' => ['template' => __('Поле обязательно')],
			]);
		}

		/** @var FakerTemplateRepository $repository */
		$repository = Application::instance()->database()->repository(FakerTemplate::class);
		$template   = $repository->findOneById($templateId);

		if($template === null) {
			return JsonResponse::fail(__('Ошибка'), __('Такого шаблона не существует'), 'not_found', 404);
		}

		$decoded = json_decode($template->template, true);

		if(!is_array($decoded)) {
			return JsonResponse::fail(__('Ошибка'), __('Не удалось прочитать данные шаблона'), 'validation', 400);
		}

		$config = (new ConfigNormalizer())->normalize(DataManager::getConfig('dle_faker'));

		try {
			$result = (new NewsGeneratorService())->generate($decoded, $config);
		} catch(\Throwable $e) {
			return JsonResponse::fail(__('Ошибка'), $e->getMessage(), 'validation', 400);
		}

		return JsonResponse::toast(__('Генерация новости завершена'), [
			'generated' => 1,
			'post'      => $result,
		]);
	}

}
