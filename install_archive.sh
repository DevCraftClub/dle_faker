#!/bin/bash

# Создание временной директории
mkdir -p temp

# Копирование содержимого папки "upload" в "temp"
cp -r upload/* temp/

# Перейти в папку temp
cd temp || exit 1

# Упаковать содержимое в ZIP без сжатия (аналог -mx0 в 7-Zip)
zip -0 -r -o dle_faker.zip *

# Вернуться в родительскую директорию
cd ..

# Копировать файл в нужное место с перезаписью
cp -f temp/dle_faker.zip dle_faker_install.zip

# Удаление временной директории
rm -rf temp

# Завершение
exit 0
