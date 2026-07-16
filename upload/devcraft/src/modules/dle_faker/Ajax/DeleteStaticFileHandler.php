<?php

declare(strict_types=1);

namespace DevCraft\Modules\dle_faker\Ajax;

use DevCraft\Core\Application;
use DevCraft\Core\Http\AjaxRequest;
use DevCraft\Core\Http\JsonResponse;
use DevCraft\Core\Interfaces\AjaxHandlerInterface;
use DevCraft\Core\Interfaces\ResponseInterface;
use DevCraft\Modules\dle_faker\Models\FakerStaticFile;
use DevCraft\Modules\dle_faker\Repositories\FakerStaticFileRepository;
use DevCraft\Modules\dle_faker\Services\StaticFileStorage;

/**
 * Удаление файла из библиотеки статичных ресурсов.
 */
final class DeleteStaticFileHandler implements AjaxHandlerInterface {

	public function handle(AjaxRequest $request): ResponseInterface {
		$id = (int) ($request->data['id'] ?? 0);

		if($id <= 0) {
			return JsonResponse::fail(__('Ошибка'), __('Некорректный идентификатор файла'), 'validation', 422);
		}

		$database = Application::instance()->database();
		/** @var FakerStaticFileRepository $repo */
		$repo = $database->repository(FakerStaticFile::class);
		$file = $repo->findOneById($id);

		if($file === NULL) {
			return JsonResponse::fail(__('Ошибка'), __('Файл не найден'), 'validation', 404);
		}

		(new StaticFileStorage())->deleteLibraryFile($file->kind, $file->stored_name);
		$database->delete(FakerStaticFile::class, $id);

		return JsonResponse::toast(__('Файл удалён'), ['deleted' => true]);
	}

}
