@echo off
echo Очистка кэша Laravel...
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

echo Генерация нового кэша...
php artisan config:cache
php artisan route:cache

echo Перезагрузка сессий...
php artisan session:table
php artisan migrate --force

echo Готово!
