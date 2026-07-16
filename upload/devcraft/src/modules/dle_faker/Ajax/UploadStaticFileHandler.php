<?php

declare(strict_types=1);

namespace DevCraft\Modules\dle_faker\Ajax;

use DateTimeImmutable;
use DevCraft\Core\Application;
use DevCraft\Core\Http\AjaxRequest;
use DevCraft\Core\Http\JsonResponse;
use DevCraft\Core\Interfaces\AjaxHandlerInterface;
use DevCraft\Core\Interfaces\ResponseInterface;
use DevCraft\Modules\dle_faker\Models\FakerStaticFile;
use DevCraft\Modules\dle_faker\Services\StaticFileStorage;

/**
 * Загрузка файла в библиотеку статичных ресурсов.
 */
final class UploadStaticFileHandler implements AjaxHandlerInterface {

	public function handle(AjaxRequest $request): ResponseInterface {
		$kind = (string) ($request->data['kind'] ?? ($_POST['kind'] ?? 'file'));
		$file = $_FILES['file'] ?? null;

		if(!is_array($file)) {
			return JsonResponse::fail(__('Ошибка'), __('Файл не передан'), 'validation', 422);
		}

		$storage = new StaticFileStorage();

		try {
			$stored = $storage->storeLibraryUpload($kind, $file);
			$entity = new FakerStaticFile();
			$entity->kind          = $storage->normalizeKind($kind);
			$entity->original_name = $stored['original_name'];
			$entity->stored_name   = $stored['stored_name'];
			$entity->mime          = $stored['mime'];
			$entity->size_bytes    = $stored['size_bytes'];
			$entity->created_at    = new DateTimeImmutable();

			Application::instance()->database()->create($entity);
		} catch(\Throwable $e) {
			return JsonResponse::fail(__('Ошибка'), $e->getMessage(), 'validation', 422);
		}

		return JsonResponse::toast(__('Файл загружен'), [
			'id'            => $entity->id,
			'kind'          => $entity->kind,
			'original_name' => $entity->original_name,
			'url'           => '',
		]);
	}

}
