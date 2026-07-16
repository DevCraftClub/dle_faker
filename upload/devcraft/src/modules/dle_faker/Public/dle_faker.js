(function(window) {
	'use strict';

	if (!window.DevCraft) {
		console.error('[DLE Faker] Сначала должен быть загружен DevCraft core.');
		return;
	}

	const DevCraftAjax = window.DevCraft.Ajax;
	const DevCraftMetro = window.DevCraft.Metro;

	function moduleMod() {
		return document.body.dataset.mod || 'dle_faker';
	}

	function ajaxUrl(method) {
		return DevCraftAjax.url(DevCraftAjax.baseUrl(), {
			mod: moduleMod(),
			controller: 'admin',
			method: method,
		});
	}

	function postAction(method, data) {
		return fetch(ajaxUrl(method), {
			method: 'POST',
			headers: {'Content-Type': 'application/x-www-form-urlencoded'},
			body: new URLSearchParams({
				user_hash: DevCraftAjax.getUserHash(),
				data: JSON.stringify(data || {}),
			}).toString(),
		}).then(DevCraftAjax.parseResponse);
	}

	function postMultipart(method, formData) {
		formData.append('user_hash', DevCraftAjax.getUserHash());

		return fetch(ajaxUrl(method), {
			method: 'POST',
			body: formData,
		}).then(DevCraftAjax.parseResponse);
	}

	function assignNested(target, path, value) {
		const keys = path.replace(/\]/g, '').split('[');
		let cursor = target;

		for (let i = 0; i < keys.length; i += 1) {
			const key = keys[i];
			const last = i === keys.length - 1;

			if (last) {
				if (key === 'random') {
					cursor[key] = value === '1' || value === 'true' || value === true;
				} else if (key === 'static_id' || key === 'asset_id') {
					cursor[key] = parseInt(value || '0', 10) || 0;
				} else {
					cursor[key] = value;
				}
				return;
			}

			if (!cursor[key] || typeof cursor[key] !== 'object') {
				cursor[key] = {};
			}

			cursor = cursor[key];
		}
	}

	function collectFormPayload(form) {
		const payload = {};
		const formData = new FormData(form);

		formData.forEach(function (value, key) {
			if (key.endsWith('[]')) {
				const plainKey = key.slice(0, -2);
				payload[plainKey] = payload[plainKey] || [];
				payload[plainKey].push(value);
				return;
			}

			if (key.indexOf('[') !== -1) {
				assignNested(payload, key, value);
				return;
			}

			payload[key] = value;
		});

		return payload;
	}

	function initXfieldSourcePanels(root) {
		(root || document).querySelectorAll('.js-xfield-field').forEach(function (field) {
			const select = field.querySelector('.js-xfield-source');

			if (!select) {
				return;
			}

			function sync() {
				const source = select.value;
				field.querySelectorAll('.js-xfield-source-panel').forEach(function (panel) {
					panel.style.display = panel.getAttribute('data-panel') === source ? '' : 'none';
				});
			}

			select.addEventListener('change', sync);
			sync();
		});
	}

	function initTemplateAssetUploads(root) {
		(root || document).querySelectorAll('.js-xfield-asset-file').forEach(function (input) {
			input.addEventListener('change', function () {
				if (!input.files || !input.files[0]) {
					return;
				}

				const wrap = input.closest('.js-xfield-source-panel');
				const idInput = wrap ? wrap.querySelector('.js-xfield-asset-id') : null;
				const label = wrap ? wrap.querySelector('.js-xfield-asset-label') : null;
				const templateId = (document.querySelector('.js-dle-faker-template-form input[name="id"]') || {}).value || 0;
				const fd = new FormData();
				fd.append('data', JSON.stringify({
					template_id: parseInt(templateId, 10) || 0,
					kind: input.dataset.kind || 'file',
				}));
				fd.append('file', input.files[0]);

				postMultipart('upload_template_asset', fd)
					.then(function (response) {
						DevCraftAjax.handleNotice(response);

						if (response.success && response.data && idInput) {
							idInput.value = response.data.asset_id || 0;

							if (label) {
								label.textContent = response.data.original_name || ('#' + response.data.asset_id);
							}
						}
					})
					.catch(function (error) {
						DevCraftMetro.notifyError('Ошибка', 'Не удалось загрузить вложение', error);
					});
			});
		});
	}

	function initTemplateForm() {
		const form = document.querySelector('.js-dle-faker-template-form');

		if (!form) {
			return;
		}

		initXfieldSourcePanels(form);
		initTemplateAssetUploads(form);

		form.addEventListener('submit', function (event) {
			event.preventDefault();
			const payload = collectFormPayload(form);

			postAction('create_template', payload)
				.then(function (response) {
					DevCraftAjax.handleNotice(response);

					if (response.success && response.data && response.data.redirect) {
						window.location.href = response.data.redirect;
					}
				})
				.catch(function (error) {
					DevCraftMetro.notifyError('Ошибка', 'Сеть или сервер недоступен', error);
				});
		});
	}

	function initTemplateList() {
		document.querySelectorAll('.js-template-delete').forEach(function (button) {
			button.addEventListener('click', function () {
				if (!window.confirm('Удалить шаблон?')) {
					return;
				}

				postAction('delete_template', {id: button.dataset.id})
					.then(function (response) {
						DevCraftAjax.handleNotice(response);

						if (response.success) {
							window.location.reload();
						}
					});
			});
		});

		document.querySelectorAll('.js-template-toggle').forEach(function (button) {
			button.addEventListener('click', function () {
				postAction('toggle_template', {id: button.dataset.id})
					.then(function (response) {
						DevCraftAjax.handleNotice(response);

						if (response.success) {
							window.location.reload();
						}
					});
			});
		});
	}

	function renderResults(container, rows, formatter) {
		if (!container) {
			return;
		}

		container.innerHTML = rows.map(formatter).join('');
	}

	function runBatch(method, payload, count, onItem) {
		let index = 0;
		const limit = Math.max(1, count);

		function step() {
			if (index >= limit) {
				return Promise.resolve();
			}

			index += 1;

			return postAction(method, payload)
				.then(function (response) {
					DevCraftAjax.handleNotice(response);

					if (response.success) {
						onItem(response.data || {}, index, limit);
					}

					if (!response.success) {
						throw new Error('Batch stopped');
					}

					return step();
				});
		}

		return step();
	}

	function initUsersGenerator() {
		const form = document.querySelector('.js-dle-faker-users-form');
		const results = document.querySelector('.js-dle-faker-users-results');
		const saveBtn = document.querySelector('.js-dle-faker-save-user-xfields');

		if (!form) {
			return;
		}

		initXfieldSourcePanels(form);

		if (saveBtn) {
			saveBtn.addEventListener('click', function () {
				const payload = collectFormPayload(form);
				postAction('save_user_xfields', {user_xfields: payload.xfields || {}})
					.then(function (response) {
						DevCraftAjax.handleNotice(response);
					})
					.catch(function (error) {
						DevCraftMetro.notifyError('Ошибка', 'Не удалось сохранить доп. поля', error);
					});
			});
		}

		form.addEventListener('submit', function (event) {
			event.preventDefault();

			const payload = collectFormPayload(form);
			const count = parseInt(payload.count || '1', 10);
			delete payload.count;

			if (payload.xfields) {
				payload.user_xfields = payload.xfields;
				delete payload.xfields;
			}

			const rows = [];

			runBatch('generate_users', payload, count, function (data) {
				rows.unshift(data.user || {});
				renderResults(results, rows, function (item) {
					return '<div class="remark mb-2"><b>#' + item.id + '</b> ' + (item.username || '') + ' &lt;' + (item.email || '') + '&gt;</div>';
				});
			}).catch(function (error) {
				DevCraftMetro.notifyError('Ошибка', 'Генерация пользователей была прервана', error);
			});
		});
	}

	function initNewsGenerator() {
		const form = document.querySelector('.js-dle-faker-news-form');
		const results = document.querySelector('.js-dle-faker-news-results');

		if (!form) {
			return;
		}

		form.addEventListener('submit', function (event) {
			event.preventDefault();

			const payload = collectFormPayload(form);
			const count = parseInt(payload.count || '1', 10);
			delete payload.count;
			const rows = [];

			runBatch('generate_posts', payload, count, function (data) {
				rows.unshift(data.post || {});
				renderResults(results, rows, function (item) {
					return '<div class="remark mb-2"><b>#' + item.id + '</b> ' + (item.name || '') + ' <span class="fg-gray">(' + (item.date || '') + ')</span></div>';
				});
			}).catch(function (error) {
				DevCraftMetro.notifyError('Ошибка', 'Генерация новостей была прервана', error);
			});
		});
	}

	function initStaticFiles() {
		document.querySelectorAll('.js-dle-faker-static-upload').forEach(function (form) {
			form.addEventListener('submit', function (event) {
				event.preventDefault();
				const fileInput = form.querySelector('input[type="file"]');

				if (!fileInput || !fileInput.files || !fileInput.files[0]) {
					return;
				}

				const fd = new FormData();
				fd.append('data', JSON.stringify({kind: form.dataset.kind || 'file'}));
				fd.append('file', fileInput.files[0]);

				postMultipart('upload_static_file', fd)
					.then(function (response) {
						DevCraftAjax.handleNotice(response);

						if (response.success) {
							window.location.reload();
						}
					})
					.catch(function (error) {
						DevCraftMetro.notifyError('Ошибка', 'Не удалось загрузить файл', error);
					});
			});
		});

		document.querySelectorAll('.js-dle-faker-static-delete').forEach(function (button) {
			button.addEventListener('click', function () {
				if (!window.confirm('Удалить файл?')) {
					return;
				}

				postAction('delete_static_file', {id: button.dataset.id})
					.then(function (response) {
						DevCraftAjax.handleNotice(response);

						if (response.success) {
							window.location.reload();
						}
					});
			});
		});
	}

	function init() {
		initTemplateForm();
		initTemplateList();
		initUsersGenerator();
		initNewsGenerator();
		initStaticFiles();
	}

	window.DevCraft.Modules = window.DevCraft.Modules || {};
	window.DevCraft.Modules.dleFaker = {batchState: null};

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})(window);
