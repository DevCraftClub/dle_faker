@echo off
mkdir temp
robocopy upload temp /E
cd temp
set PATH=%PATH%;%ProgramFiles%\7-Zip\
7z a -mx0 -r -tzip -aoa dle_faker.zip *
cd ..
copy /Y temp\dle_faker.zip dle_faker_install.zip
rd /s /q temp
exit;
