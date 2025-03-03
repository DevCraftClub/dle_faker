<?php
require 'vendor/autoload.php';

use phpDocumentor\Reflection\DocBlockFactory;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

function parsePHPFiles(string $directory): array
{
	$files = [];
	$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));

	foreach ($iterator as $file) {
		if (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
			$files[] = $file->getPathname();
		}
	}

	return $files;
}

function generateMarkdownDocs(string $sourceDir, string $outputDir): void
{
	$docFactory = DocBlockFactory::createInstance();
	$phpFiles = parsePHPFiles($sourceDir);

	foreach ($phpFiles as $filePath) {
		$relativePath = str_replace($sourceDir, '', $filePath);
		$markdownPath = $outputDir . '/' . pathinfo($relativePath, PATHINFO_DIRNAME);
		$markdownFile = $markdownPath . '/' . pathinfo($relativePath, PATHINFO_FILENAME) . '.md';

		if (!is_dir($markdownPath)) {
			mkdir($markdownPath, 0777, true);
		}

		$markdownContent = generateFileDocumentation($filePath, $docFactory);
		file_put_contents($markdownFile, $markdownContent);
	}

	echo "Markdown-документация сгенерирована в папке $outputDir\n";
}

function generateFileDocumentation(string $filePath, $docFactory): string
{
	$fileContent = file_get_contents($filePath);
	$tokens = token_get_all($fileContent);
	$markdown = "# Документация для " . basename($filePath) . "\n\n";

	$currentNamespace = '';
	$functionDetails = [];

	foreach ($tokens as $index => $token) {
		if (is_array($token) && $token[0] === T_NAMESPACE) {
			$currentNamespace = getNamespace($tokens, $index);
			$markdown .= "## Пространство имен: `$currentNamespace`\n\n";
		}

		if (is_array($token) && $token[0] === T_FUNCTION) {
			$functionDetails = getFunctionDetails($tokens, $index, $docFactory);
			$markdown .= formatFunctionMarkdown($functionDetails);
		}
	}

	return $markdown;
}

function getNamespace(array $tokens, int $startIndex): string
{
	$namespace = '';
	$collectNamespace = false;

	for ($i = $startIndex; $i < count($tokens); $i++) {
		if (is_array($tokens[$i]) && $tokens[$i][0] === T_NAMESPACE) {
			$collectNamespace = true;
		} elseif ($collectNamespace) {
			if (is_array($tokens[$i])) {
				$namespace .= $tokens[$i][1];
			} elseif ($tokens[$i] === ';') {
				break;
			}
		}
	}

	return trim($namespace);
}

function getFunctionDetails(array &$tokens, int $startIndex, $docFactory): array
{
	$details = [
		'name' => '',
		'parameters' => [],
		'returnType' => 'void',
		'docComment' => null,
	];

	$collectParams = false;
	$collectName = true;
	$paramDetails = [];
	$currentType = '';
	$currentName = '';

	for ($i = $startIndex; $i < count($tokens); $i++) {
		$token = $tokens[$i];

		if (is_array($token) && $token[0] === T_DOC_COMMENT) {
			$details['docComment'] = $token[1];
		}

		if ($collectName && is_array($token) && $token[0] === T_STRING) {
			$details['name'] = $token[1];
			$collectName = false;
			$collectParams = true;
		} elseif ($collectParams) {
			if (is_array($token) && $token[0] === T_VARIABLE) {
				$paramDetails[] = [
					'type' => $currentType,
					'name' => $token[1],
				];
				$currentType = '';
			} elseif (is_array($token) && $token[0] === T_STRING) {
				$currentType = $token[1];
			} elseif ($token === ')') {
				$details['parameters'] = $paramDetails;
				$collectParams = false;
			}
		} elseif ($token === ':' && isset($tokens[$i + 1]) && is_array($tokens[$i + 1])) {
			$details['returnType'] = $tokens[$i + 1][1];
		}

		if (!$collectParams && !$collectName) {
			break;
		}
	}

	return $details;
}

function formatFunctionMarkdown(array $details): string
{
	$markdown = "### Функция: `{$details['name']}`\n\n";

	if ($details['docComment']) {
		try {
			$docBlock = DocBlockFactory::createInstance()->create($details['docComment']);
			$markdown .= "**Описание:** " . $docBlock->getDescription() . "\n\n";
			$markdown .= "**Краткое описание:** " . ($docBlock->getSummary() ?: 'Нет заголовка') . "\n\n";

			// Обработка дополнительных тегов
			$tags = $docBlock->getTags();
			if (!empty($tags)) {
				$markdown .= "**Теги:**\n";
				foreach ($tags as $tag) {
					$markdown .= "- `@" . $tag->getName() . "` " . $tag . "\n";
				}
				$markdown .= "\n";
			}
		} catch (\Exception $e) {
			$markdown .= "**Описание:** Неверный PHPDoc комментарий.\n\n";
		}
	} else {
		$markdown .= "**Описание:** Документация отсутствует.\n\n";
	}

	$markdown .= "**Параметры:**\n";
	if (!empty($details['parameters'])) {
		foreach ($details['parameters'] as $param) {
			$markdown .= "- `{$param['name']}` (" . ($param['type'] ?: 'mixed') . ")\n";
		}
	} else {
		$markdown .= "- Нет\n";
	}

	$markdown .= "\n**Возвращаемый тип:** `" . ($details['returnType'] ?: 'void') . "`\n\n";

	return $markdown;
}

$sourceDir = './upload'; // Путь к исходной папке
$outputDir = './docs'; // Путь для вывода документации

generateMarkdownDocs($sourceDir, $outputDir);
