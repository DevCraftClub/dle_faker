<?php

declare(strict_types=1);

namespace DevCraft\Modules\dle_faker\Ajax;

use DevCraft\Core\Application;
use DevCraft\Core\Http\AjaxRequest;
use DevCraft\Core\Http\JsonResponse;
use DevCraft\Core\Http\UploadedFile;
use DevCraft\Core\Interfaces\AjaxHandlerInterface;
use DevCraft\Core\Interfaces\ResponseInterface;
use DevCraft\Modules\dle_faker\Models\FakerTemplateAsset;
use DevCraft\Modules\dle_faker\Services\StaticFileStorage;

/**
 * Загрузка вложения для медиа-поля шаблона новостей.
 */
final class UploadTemplateAssetHandler implements AjaxHandlerInterface {

	public function handle(AjaxRequest $request): ResponseInterface {
		$templateId = (int) ($request->data['template_id'] ?? ($_POST['template_id'] ?? 0));
		$kind       = (string) ($request->data['kind'] ?? ($_POST['kind'] ?? 'file'));
		$storage    = new StaticFileStorage();

		try {
			$uploaded = UploadedFile::fromFilesKey('file');
			$stored   = $storage->storeTemplateUpload($templateId, $kind, $uploaded->toArray());
			$entity   = new FakerTemplateAsset();
			$entity->template_id   = max(0, $templateId);
			$entity->kind          = $storage->normalizeKind($kind);
			$entity->original_name = $stored['original_name'];
			$entity->stored_name   = $stored['stored_name'];
			$entity->mime          = $stored['mime'];
			$entity->size_bytes    = $stored['size_bytes'];

			Application::instance()->database()->create($entity);
		} catch(\Throwable $e) {
			return JsonResponse::fail(__('Ошибка'), $e->getMessage(), 'validation', 422);
		}

		return JsonResponse::toast(__('Вложение загружено'), [
			'asset_id'      => $entity->id(),
			'kind'          => $entity->kind,
			'original_name' => $entity->original_name,
		]);
	}

}
