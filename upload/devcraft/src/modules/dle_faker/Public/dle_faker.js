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

	function postMultipart(method, formData, onProgress) {
		return DevCraftAjax.postMultipart(ajaxUrl(method), formData, onProgress);
	}

	function setProgress(form, percent, label) {
		const bar = form.querySelector('.js-dle-faker-static-progress');
		const labelEl = form.querySelector('.js-dle-faker-static-progress-label');

		if (bar) {
			bar.classList.remove('d-none');
			const plugin = window.Metro && typeof Metro.getPlugin === 'function'
				? Metro.getPlugin(bar, 'progress')
				: null;

			if (plugin && typeof plugin.val === 'function') {
				plugin.val(Math.max(0, Math.min(100, Math.round(percent))));
			} else {
				bar.setAttribute('data-value', String(Math.round(percent)));
			}
		}

		if (labelEl) {
			labelEl.classList.toggle('d-none', !label);
			labelEl.textContent = label || '';
		}
	}

	function hideProgress(form) {
		const bar = form.querySelector('.js-dle-faker-static-progress');
		const labelEl = form.querySelector('.js-dle-faker-static-progress-label');

		if (bar) {
			bar.classList.add('d-none');
			const plugin = window.Metro && typeof Metro.getPlugin === 'function'
				? Metro.getPlugin(bar, 'progress')
				: null;

			if (plugin && typeof plugin.val === 'function') {
				plugin.val(0);
			}
		}

		if (labelEl) {
			labelEl.classList.add('d-none');
			labelEl.textContent = '';
		}
	}

	function appendStaticListItem(kind, item) {
		const list = document.querySelector('.js-dle-faker-static-list[data-kind="' + kind + '"]');

		if (!list || !item || !item.id) {
			return;
		}

		const empty = list.querySelector('.js-dle-faker-static-empty');

		if (empty) {
			empty.remove();
		}

		const li = document.createElement('li');
		li.setAttribute('data-id', String(item.id));
		li.appendChild(document.createTextNode(item.original_name || ('#' + item.id) + ' '));

		const btn = document.createElement('button');
		btn.type = 'button';
		btn.className = 'button small alert js-dle-faker-static-delete';
		btn.dataset.id = String(item.id);
		btn.textContent = 'Удалить';
		li.appendChild(btn);
		list.appendChild(li);
	}

	function removeStaticListItem(id) {
		const li = document.querySelector('.js-dle-faker-static-list li[data-id="' + id + '"]');

		if (!li) {
			return;
		}

		const list = li.parentElement;
		li.remove();

		if (list && !list.querySelector('li[data-id]')) {
			const empty = document.createElement('li');
			empty.className = 'fg-gray js-dle-faker-static-empty';
			empty.textContent = list.dataset.empty || 'Пока нет файлов';
			list.appendChild(empty);
		}
	}

	function initStaticFiles() {
		document.querySelectorAll('.js-dle-faker-static-upload').forEach(function (form) {
			form.addEventListener('submit', function (event) {
				event.preventDefault();
				const fileInput = form.querySelector('input[type="file"]');
				const submitBtn = form.querySelector('button[type="submit"]');

				if (!fileInput || !fileInput.files || !fileInput.files.length) {
					return;
				}

				const kind = form.dataset.kind || 'file';
				const files = Array.prototype.slice.call(fileInput.files);
				const total = files.length;
				let done = 0;
				let failed = false;

				if (submitBtn) {
					submitBtn.disabled = true;
				}

				setProgress(form, 0, 'Загрузка 0/' + total);

				let chain = Promise.resolve();

				files.forEach(function (file, index) {
					chain = chain.then(function () {
						if (failed) {
							return;
						}

						const fd = new FormData();
						fd.append('data', JSON.stringify({kind: kind}));
						fd.append('file', file);

						return postMultipart('upload_static_file', fd, function (loaded, fileTotal) {
							const fraction = fileTotal > 0 ? loaded / fileTotal : 0;
							const percent = ((done + fraction) / total) * 100;
							setProgress(form, percent, 'Загрузка ' + (index + 1) + '/' + total + ': ' + file.name);
						}).then(function (response) {
							DevCraftAjax.handleNotice(response);

							if (!response.success) {
								failed = true;
								return;
							}

							done += 1;
							appendStaticListItem(kind, response.data || {});
							setProgress(form, (done / total) * 100, 'Загрузка ' + done + '/' + total);
						}).catch(function (error) {
							failed = true;
							DevCraftMetro.notifyError('Ошибка', 'Не удалось загрузить файл', error);
						});
					});
				});

				chain.then(function () {
					if (submitBtn) {
						submitBtn.disabled = false;
					}

					const metroFile = window.Metro && typeof Metro.getPlugin === 'function'
						? Metro.getPlugin(fileInput, 'file')
						: null;

					if (metroFile && typeof metroFile.clear === 'function') {
						metroFile.clear();
					} else {
						fileInput.value = '';
					}

					if (!failed) {
						setProgress(form, 100, 'Готово');
						window.setTimeout(function () {
							hideProgress(form);
						}, 800);
					} else {
						hideProgress(form);
					}
				});
			});
		});

		document.addEventListener('click', function (event) {
			const button = event.target.closest('.js-dle-faker-static-delete');

			if (!button) {
				return;
			}

			if (!window.confirm('Удалить файл?')) {
				return;
			}

			const id = button.dataset.id;

			postAction('delete_static_file', {id: id})
				.then(function (response) {
					DevCraftAjax.handleNotice(response);

					if (response.success) {
						removeStaticListItem(id);
					}
				});
		});
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

		// Metro data-role=select часто disabled'ит исходный <select> — FormData его пропускает.
		form.querySelectorAll('select[disabled]').forEach(function (el) {
			el.disabled = false;
		});

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

		form.querySelectorAll('.js-xfield-source').forEach(function (select) {
			const field = select.closest('.js-xfield-field');
			const name = field ? field.getAttribute('data-field') : '';

			if (!name) {
				return;
			}

			payload.xfields = payload.xfields || {};
			payload.xfields[name] = payload.xfields[name] || {};
			payload.xfields[name].source = select.value;
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
				const source = select.value || 'faker';

				field.querySelectorAll('.js-xfield-source-panel').forEach(function (panel) {
					panel.style.display = panel.getAttribute('data-panel') === source ? '' : 'none';
				});
			}

			select.addEventListener('change', sync);
			select.addEventListener('input', sync);
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
					if (response.success) {
						DevCraftAjax.handleNotice(response);
						onItem(response.data || {}, index, limit);

						return step();
					}

					const err = (response.error && (response.error.message || response.error.title)) || 'Batch stopped';
					throw new Error(err);
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
				DevCraftMetro.notifyError('Ошибка', (error && error.message) || 'Генерация пользователей была прервана', error);
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
				DevCraftMetro.notifyError('Ошибка', (error && error.message) || 'Генерация новостей была прервана', error);
			});
		});
	}

	function initCategoriesGenerator() {
		const form = document.querySelector('.js-dle-faker-categories-form');
		const results = document.querySelector('.js-dle-faker-categories-results');

		if (!form) {
			return;
		}

		form.addEventListener('submit', function (event) {
			event.preventDefault();

			const payload = collectFormPayload(form);
			const count = parseInt(payload.count || '1', 10);
			delete payload.count;
			const rows = [];

			runBatch('generate_categories', payload, count, function (data) {
				rows.unshift(data.category || {});
				renderResults(results, rows, function (item) {
					const skipped = item.skipped ? ' <span class="fg-orange">[' + 'пропущено' + ']</span>' : '';
					const id = item.id ? '#' + item.id + ' ' : '';
					return '<div class="remark mb-2"><b>' + id + '</b>' + (item.name || '') +
						' <span class="fg-gray">(' + (item.alt_name || '') + ', parent=' + (item.parentid || 0) + ')</span>' +
						skipped + '</div>';
				});
			}).catch(function (error) {
				DevCraftMetro.notifyError('Ошибка', (error && error.message) || 'Генерация категорий была прервана', error);
			});
		});
	}

	function init() {
		initTemplateForm();
		initTemplateList();
		initUsersGenerator();
		initNewsGenerator();
		initCategoriesGenerator();
		initStaticFiles();
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})(window);
