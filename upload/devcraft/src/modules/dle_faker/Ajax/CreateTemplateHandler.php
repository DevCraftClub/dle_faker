<?php

declare(strict_types=1);

namespace DevCraft\Modules\dle_faker\Ajax;

use JsonException;
use DevCraft\Core\Application;
use DevCraft\Core\Http\AjaxRequest;
use DevCraft\Core\Http\JsonResponse;
use DevCraft\Core\Interfaces\AjaxHandlerInterface;
use DevCraft\Core\Interfaces\ResponseInterface;
use DevCraft\Modules\dle_faker\Models\FakerTemplate;
use DevCraft\Modules\dle_faker\Models\FakerTemplateAsset;
use DevCraft\Modules\dle_faker\Repositories\FakerTemplateAssetRepository;
use DevCraft\Modules\dle_faker\Repositories\FakerTemplateRepository;
use DevCraft\Modules\dle_faker\Services\XfieldFormService;

/**
 * Создаёт или обновляет шаблон генерации.
 */
final class CreateTemplateHandler implements AjaxHandlerInterface {

	public function handle(AjaxRequest $request): ResponseInterface {
		$data = $request->data;

		if(trim((string) ($data['name'] ?? '')) === '') {
			return JsonResponse::fail(__('Ошибка'), __('Название шаблона не может быть пустым'), 'validation', 422, [
				'fields' => ['name' => __('Поле обязательно')],
			]);
		}

		if(trim((string) ($data['autor'] ?? '')) === '') {
			return JsonResponse::fail(__('Ошибка'), __('Автор не может быть пустым'), 'validation', 422, [
				'fields' => ['autor' => __('Поле обязательно')],
			]);
		}

		if(trim((string) ($data['title'] ?? '')) === '') {
			return JsonResponse::fail(__('Ошибка'), __('Заголовок не может быть пустым'), 'validation', 422, [
				'fields' => ['title' => __('Поле обязательно')],
			]);
		}

		if(trim((string) ($data['category'] ?? '')) === '') {
			return JsonResponse::fail(__('Ошибка'), __('Категория не может быть пустой'), 'validation', 422, [
				'fields' => ['category' => __('Поле обязательно')],
			]);
		}

		if(trim((string) ($data['short_story'] ?? '')) === '') {
			return JsonResponse::fail(__('Ошибка'), __('Короткое описание не может быть пустым'), 'validation', 422, [
				'fields' => ['short_story' => __('Поле обязательно')],
			]);
		}

		$schema = Application::instance()->dleData()->postXfields();
		$xfieldsInput = $data['xfields'] ?? [];

		if(!is_array($xfieldsInput)) {
			$xfieldsInput = [];
		}

		$form     = new XfieldFormService();
		$xfResult = $form->normalizeIncoming($xfieldsInput, $schema, true);

		if($xfResult['errors'] !== []) {
			return JsonResponse::fail(__('Ошибка'), __('Проверьте обязательные доп. поля'), 'validation', 422, [
				'fields' => $xfResult['errors'],
			]);
		}

		$templatePayload            = $this->templatePayload($data);
		$templatePayload['xfields'] = $xfResult['values'];
		$database                   = Application::instance()->database();
		$templateId                 = (int) ($data['id'] ?? 0);

		/** @var FakerTemplateRepository $repository */
		$repository = $database->repository(FakerTemplate::class);
		$template   = $templateId > 0 ? $repository->findOneById($templateId) : null;
		$template ??= new FakerTemplate();

		try {
			$template->name     = trim((string) $data['name']);
			$template->active   = filter_var($data['active_template'] ?? true, FILTER_VALIDATE_BOOLEAN);
			$template->template = json_encode($templatePayload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

			$templateId > 0 ? $database->update($template) : $database->create($template);
			$this->bindAssets($template->id, $xfResult['values']);
		} catch(JsonException|\Throwable $e) {
			return JsonResponse::fail(__('Ошибка'), $e->getMessage(), 'validation', 500);
		}

		return JsonResponse::toast(__('Шаблон сохранён'), [
			'saved'    => true,
			'id'       => $template->id,
			'redirect' => '?mod=dle_faker&action=templates',
		]);
	}

	/**
	 * @param array<string, mixed> $data
	 *
	 * @return array<string, mixed>
	 */
	private function templatePayload(array $data): array {
		$payload = $data;
		unset($payload['id'], $payload['name'], $payload['active_template'], $payload['xfields']);

		foreach([
			'allow_main',
			'approve',
			'fixed',
			'allow_comm',
			'allow_rate',
			'disable_index',
			'disable_search',
			'allow_rss',
			'allow_rss_turbo',
			'allow_rss_dzen',
		] as $field) {
			$payload[$field] = trim((string) ($payload[$field] ?? 'random')) ?: 'random';
		}

		return $payload;
	}

	/**
	 * @param array<string, mixed> $xfields
	 */
	private function bindAssets(int $templateId, array $xfields): void {
		$database = Application::instance()->database();
		/** @var FakerTemplateAssetRepository $repo */
		$repo = $database->repository(FakerTemplateAsset::class);

		foreach($xfields as $value) {
			if(!is_array($value) || ($value['source'] ?? '') !== 'template_upload') {
				continue;
			}

			$assetId = (int) ($value['asset_id'] ?? 0);

			if($assetId <= 0) {
				continue;
			}

			$asset = $repo->findOneById($assetId);

			if($asset === NULL) {
				continue;
			}

			if($asset->template_id === $templateId) {
				continue;
			}

			$asset->template_id = $templateId;
			$database->update($asset);
		}
	}

}
