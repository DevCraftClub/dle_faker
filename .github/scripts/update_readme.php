<?php
// Пути к файлам
$ROOT = dirname(__FILE__, 3);

$manifestPath = $ROOT . '/manifest.json';
$readmePath = $ROOT . '/README.md';

// Проверяем наличие manifest.json
if (!file_exists($manifestPath)) {
    die("Файл manifest.json не найден.\n");
}

// Читаем и парсим manifest.json
$manifestContent = file_get_contents($manifestPath);
$manifest = json_decode($manifestContent, true);

// Проверяем корректность JSON
if (json_last_error() !== JSON_ERROR_NONE) {
    die("Ошибка парсинга JSON: " . json_last_error_msg() . "\n");
}

// Формируем badges
$badges = [];

// Badge: Текущая версия
if (isset($manifest['version'])) {
    $badges[] = "![Текущая версия](https://img.shields.io/badge/version-{$manifest['version']}-green?style=for-the-badge)";
}

// Badge: Статус
if (isset($manifest['status'])) {
    $badges[] = "![Статус](https://img.shields.io/badge/status-{$manifest['status']}-orange?style=for-the-badge)";
}

// Badge: DLE Version
if (!empty($manifest['dle']) && is_array($manifest['dle'])) {
    $dleVersions = implode(', ', $manifest['dle']);
    $badges[] = "![DLE Version](https://img.shields.io/badge/DLE-$dleVersions-blue?style=for-the-badge)";
}

// Badge: PHP Version
if (!empty($manifest['php']) && is_array($manifest['php'])) {
    $phpVersions = implode(', ', $manifest['php']);
    $badges[] = "![PHP Version](https://img.shields.io/badge/PHP-$phpVersions-red?style=for-the-badge)";
}

// Генерируем Markdown для badges
$newBadgesMarkdown = implode("", $badges);

// Читаем README.md
if (!file_exists($readmePath)) {
    die("Файл README.md не найден.\n");
}
$readmeContent = file_get_contents($readmePath);

// Обновляем badges в README.md
$updatedReadmeContent = preg_replace(
    '/(?<=# MHAdmin\n\n)(.*?)(?=\n\n\[)/s',
    $newBadgesMarkdown . "\n",
    $readmeContent
);

// Сохраняем обновленный README.md
if (file_put_contents($readmePath, $updatedReadmeContent) !== false) {
    echo "README.md успешно обновлен.\n";
} else {
    echo "Ошибка обновления README.md.\n";
}
