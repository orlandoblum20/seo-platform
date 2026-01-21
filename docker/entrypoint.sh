#!/bin/sh
set -e

# Создаём необходимые директории storage
echo "Проверка директорий storage..."
mkdir -p /var/www/storage/logs
mkdir -p /var/www/storage/framework/cache/data
mkdir -p /var/www/storage/framework/sessions
mkdir -p /var/www/storage/framework/views
mkdir -p /var/www/storage/framework/testing
mkdir -p /var/www/storage/app/public
mkdir -p /var/www/storage/app/sites
mkdir -p /var/www/bootstrap/cache

# Устанавливаем права
chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache
chmod -R 775 /var/www/storage /var/www/bootstrap/cache

# Копируем build assets в volume если там пусто
if [ -d "/var/www/build-cache" ] && [ ! -f "/var/www/public/build/manifest.json" ]; then
    echo "Копирование Vite assets в volume..."
    cp -r /var/www/build-cache/* /var/www/public/build/
    chown -R www-data:www-data /var/www/public/build
    echo "Vite assets скопированы"
fi

echo "Storage директории готовы"

# Запускаем основной процесс
exec "$@"
