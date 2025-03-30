<?php

$composerJson     = MH_ADMIN . '/composer.json';
$updateLock       = __DIR__ . '/composer.lock';
$requiredPackages = [
	"fakerphp/faker" => "*"
];

if (!file_exists($updateLock)) {
	$composerData = json_decode(file_get_contents($composerJson), true);

	foreach ($requiredPackages as $name => $version) {
		if (!isset($composerData['require'][$name])) {
			ComposerAction::requirePackage($name, $version);
			$composerData['require'][$name] = $version;
		}
	}

	try {
		ComposerAction::updateDependencies();
	} catch (Exception $e) {
		LogGenerator::generateLog(
			'DLE Faker',
			'DleFaker/ComposerUpdate',
				$e->getMessage()
		);
	}

	try {
		DataManager::createLockFile($updateLock);
	} catch (Exception|Throwable $e) {
		LogGenerator::generateLog(
			'DLE Faker',
			'DleFaker/createLockerFile',
			$e->getMessage()
		);
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