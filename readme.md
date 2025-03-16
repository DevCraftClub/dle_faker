[![GitHub issues](https://img.shields.io/github/issues/DevCraftClub/dle_faker.svg?style=flat-square)](https://github.com/DevCraftClub/dle_faker/issues)[![GitHub forks](https://img.shields.io/github/forks/DevCraftClub/dle_faker.svg?style=flat-square)](https://github.com/DevCraftClub/dle_faker/network)[![GitHub license](https://img.shields.io/github/license/DevCraftClub/dle_faker.svg?style=flat-square)](https://github.com/DevCraftClub/dle_faker/blob/master/LICENSE)
# DLE Faker

![Текущая версия](https://img.shields.io/github/manifest-json/v/DevCraftClub/dle_faker/main?style=for-the-badge&label=%D0%92%D0%B5%D1%80%D1%81%D0%B8%D1%8F)![Статус разработки](https://img.shields.io/badge/dynamic/json?url=https%3A%2F%2Fraw.githubusercontent.com%2FDevCraftClub%2Fdle_faker%2Frefs%2Fheads%2Fmain%2Fmanifest.json&query=%24.status&style=for-the-badge&label=%D0%A1%D1%82%D0%B0%D1%82%D1%83%D1%81&color=orange)

![Версия DLE](https://img.shields.io/badge/dynamic/json?url=https%3A%2F%2Fraw.githubusercontent.com%2FDevCraftClub%2Fdle_faker%2Frefs%2Fheads%2Fmain%2Fmanifest.json&query=%24.dle&style=for-the-badge&label=DLE)![Версия PHP](https://img.shields.io/badge/dynamic/json?url=https%3A%2F%2Fraw.githubusercontent.com%2FDevCraftClub%2Fdle_faker%2Frefs%2Fheads%2Fmain%2Fmanifest.json&query=%24.php&style=for-the-badge&logo=php&logoColor=777BB4&label=PHP&color=777BB4)![Версия MHAdmin](https://img.shields.io/badge/dynamic/json?url=https%3A%2F%2Fraw.githubusercontent.com%2FDevCraftClub%2Fdle_faker%2Frefs%2Fheads%2Fmain%2Fmanifest.json&query=%24.mhadmin&style=for-the-badge&label=MH-ADMIN&color=red)

DLE Faker - лагин, что позволяет вам генерировать случайные данные для DLE. На данный момент поддерживаются следующие компоненты: новости и пользователи.

## **Установка / Обновление**

**У вас три варианта для установки:**

### 1. **При помощи bat-Скрипта. Для пользователей Windows**

Для этого устанавливаем [7Zip](https://www.7-zip.org/download.html).
После установки запускаем скрипт install_archive.bat.
После завершения установки - загружаем install.zip в менеджер плагинов.

### 2. **При помощи sh-Скрипта. Для пользователей Linux/MacOS**

1. Устанавливаем права
```bash
chmod +x install_archive.sh
```
2. Запускаем скрипт
```bash
./install_archive.sh
```
3. Устанавливаем плагин через менеджер плагинов

### 3. **Упаковать самому**

Любым архиватором запаковать всё содержимое в папке upload (нужен формат zip!), причём так, чтобы в корне архива был
файл install.xml и папка engine.
Затем устанавливаем архив через менеджер плагинов.

### 4. **Просто залить**

Залейте папку engine в корень сайта и установите плагин через менеджер плагинов.

### 5. **Установка зависимостей** (Опционально)

Если в ходе использования выскочит ошибка связанная с `Faker` или / и `Faker\Factory` - установите зависимости через [композер](https://readme.devcraft.club/latest/dev/composer/).

Нужно установить следующую зависимость:

```bash
composer require fakerphp/faker
```

В теории она должна прописаться через файл `init.php`.
