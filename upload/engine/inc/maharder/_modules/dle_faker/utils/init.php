<?php

$composerJson     = MH_ADMIN . '/composer.json';
$updateLock       = __DIR__ . '/composer.lock';
$requiredPackages = [
	"fakerphp/faker" => "*"
];

if (file_exists($composerJson) && !file_exists($updateLock)) {
	$composerData = json_decode(file_get_contents($composerJson), true);

	foreach ($requiredPackages as $name => $version) {
		if (!isset($composerData['require'][$name])) {
			ComposerAction::require($name, $version);
			$composerData['require'][$name] = $version;
		}
	}

	file_put_contents($composerJson, json_encode($composerData), LOCK_EX);

	try {
		ComposerAction::update();
	} catch (JsonException $e) {
		LogGenerator::generateLog(
			'DLE Faker',
			'DleFaker/ComposerUpdate',
				$e->getMessage()
		);
	}

	try {
		DataManager::createLockFile($updateLock);
	} catch (JsonException $e) {

	}

	if (!class_exists('Faker\Factory')) {
		LogGenerator::generateLog('DLE Faker', 'DleFaker/init', [
			__('Зависимости не были установлены!'),
			__('Установите их при помощи консоли сами! PHP должен быть выполняемым!'),
			"<code><pre>cd " . MH_ADMIN . "</pre></code>",
			"<code><pre>composer update</pre></code>",
		],                        'critical');
	}
}