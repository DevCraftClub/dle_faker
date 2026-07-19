# DLE Faker

Пакет переносит legacy-модуль генерации данных в структуру DevCraft Admin для DLE 20.0.

| | |
|---|---|
| Версия | **200.1.0** |
| Совместимость | DevCraft Admin ≥ **200.4.0**, DLE **20.0** |
| Сайт | https://devcraft.club/downloads/dle-faker.29/ |
| Документация | https://readme.devcraft.club/dev/dle_faker/install/ |

## Установка

1. Установите `DevCraft Admin` версии `200.4.0` или новее.
2. Упакуйте содержимое каталога `upload/` в ZIP-архив.
3. Загрузите архив через менеджер плагинов DLE.

## Ограничения текущего переноса

- AJAX-маршруты работают через `devcraft/ajax.php`.
- Legacy-пути `engine/inc/maharder/...` в канонический пакет не включаются.
- Дополнительные правки файлов DLE/DevCraft Admin не требуются; секция `<file>` в `install.xml` отсутствует намеренно.
