<?php

declare(strict_types=1);

namespace DevCraft\Modules\dle_faker\Ajax;

use DevCraft\Core\Application;
use DevCraft\Core\Http\AjaxRequest;
use DevCraft\Core\Http\JsonResponse;
use DevCraft\Core\Interfaces\AjaxHandlerInterface;
use DevCraft\Core\Interfaces\ResponseInterface;
use DevCraft\Modules\dle_faker\Models\FakerTemplate;
use DevCraft\Modules\dle_faker\Models\FakerTemplateAsset;
use DevCraft\Modules\dle_faker\Repositories\FakerTemplateAssetRepository;
use DevCraft\Modules\dle_faker\Services\StaticFileStorage;

/**
 * Удаляет шаблон генерации и связанные вложения.
 */
final class DeleteTemplateHandler implements AjaxHandlerInterface {

	public function handle(AjaxRequest $request): ResponseInterface {
		$id = (int) ($request->data['id'] ?? 0);

		if($id <= 0) {
			return JsonResponse::fail(__('Ошибка'), __('Некорректный идентификатор шаблона'), 'validation', 422);
		}

		$database = Application::instance()->database();
		$storage  = new StaticFileStorage();

		try {
			/** @var FakerTemplateAssetRepository $assets */
			$assets = $database->repository(FakerTemplateAsset::class);

			foreach($assets->findByTemplateId($id) as $asset) {
				$storage->deleteTemplateFile($asset->template_id, $asset->stored_name);
				$database->delete(FakerTemplateAsset::class, $asset->id());
			}

			$storage->deleteTemplateDirectory($id);
			$database->delete(FakerTemplate::class, $id);
		} catch(\Throwable $e) {
			return JsonResponse::fail(__('Ошибка'), $e->getMessage(), 'validation', 400);
		}

		return JsonResponse::toast(__('Шаблон удалён'), ['deleted' => true]);
	}

}
