@echo off
echo Запуск сборки стилей...

REM Установка переменных
SET VITE_BIN=node_modules\.bin\vite

REM Проверяем наличие Vite
if not exist %VITE_BIN% (
    echo Vite не установлен. Устанавливаю зависимости...
    call npm install
)

REM Запускаем сборку
echo Запуск сборки ресурсов Vite...
call %VITE_BIN% build

echo Сборка завершена!
pause
