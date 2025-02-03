<?php

$updateLock       = __DIR__ . '/composer.lock';
$requiredPackages = [
	"fakerphp/faker" => "*"
];

ComposerAction::requirePackage($requiredPackages, lockerFile: $updateLock);

if (!class_exists('Faker\Factory')) {
	LogGenerator::generateLog('DLE Faker', 'DleFaker/init', [
		__('Зависимости не были установлены!'),
		__('Установите их при помощи консоли сами! PHP должен быть выполняемым!'),
		"<code><pre>cd " . MH_ADMIN . "</pre></code>",
		"<code><pre>composer update</pre></code>",
	],                        'critical');
}
