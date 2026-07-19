<?php

declare(strict_types=1);

namespace DevCraft\Modules\dle_faker\Ajax;

use DevCraft\Core\Application;
use DevCraft\Core\Http\AjaxRequest;
use DevCraft\Core\Http\JsonResponse;
use DevCraft\Core\Interfaces\AjaxHandlerInterface;
use DevCraft\Core\Interfaces\ResponseInterface;
use DevCraft\Modules\dle_faker\Models\FakerTemplate;
use DevCraft\Modules\dle_faker\Repositories\FakerTemplateRepository;

/**
 * Переключает активность шаблона.
 */
final class ToggleTemplateHandler implements AjaxHandlerInterface {

	public function handle(AjaxRequest $request): ResponseInterface {
		$id = (int) ($request->data['id'] ?? 0);

		if($id <= 0) {
			return JsonResponse::fail(__('Ошибка'), __('Некорректный идентификатор шаблона'), 'validation', 422);
		}

		/** @var FakerTemplateRepository $repository */
		$repository = Application::instance()->database()->repository(FakerTemplate::class);
		$template   = $repository->findOneById($id);

		if($template === null) {
			return JsonResponse::fail(__('Ошибка'), __('Такого шаблона не существует'), 'not_found', 404);
		}

		$template->active = !$template->active;

		try {
			Application::instance()->database()->update($template);
		} catch(\Throwable $e) {
			return JsonResponse::fail(__('Ошибка'), $e->getMessage(), 'validation', 400);
		}

		return JsonResponse::toast(
			$template->active ? __('Шаблон был включён') : __('Шаблон был выключен'),
			['toggled' => true, 'active' => $template->active],
		);
	}

}
