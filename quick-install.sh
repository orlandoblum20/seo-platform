#!/bin/bash
set -e

echo "========================================"
echo "  SEO PLATFORM - БЫСТРАЯ УСТАНОВКА"
echo "  Версия: 3.6.15"
echo "========================================"
echo ""

# Проверка root
if [ "$EUID" -ne 0 ]; then
    echo "Ошибка: запустите от root (sudo ./quick-install.sh)"
    exit 1
fi

# 1. Установка зависимостей
echo "[1/7] Установка зависимостей..."
apt update -qq
apt install -y -qq unzip curl wget > /dev/null 2>&1
echo "✓ Зависимости установлены"

# 2. Проверка Docker
echo "[2/7] Проверка Docker..."
if ! command -v docker &> /dev/null; then
    echo "Установка Docker..."
    curl -fsSL https://get.docker.com | sh
fi

if ! docker compose version &> /dev/null; then
    echo "Ошибка: Docker Compose не найден"
    exit 1
fi
echo "✓ Docker готов"

# 3. Определение параметров
SERVER_IP=$(hostname -I | awk '{print $1}')
DB_PASS=$(openssl rand -base64 16 | tr -dc 'a-zA-Z0-9' | head -c 20)
APP_KEY="base64:$(openssl rand -base64 32)"

echo "[3/7] Конфигурация..."
echo "  IP сервера: $SERVER_IP"
echo "  Пароль БД: $DB_PASS"

# 4. Создаём .env
cat > .env << ENVFILE
APP_NAME="SEO Landing Platform"
APP_ENV=production
APP_KEY=${APP_KEY}
APP_DEBUG=false
APP_TIMEZONE=Europe/Moscow
APP_URL=http://${SERVER_IP}:8080
APP_PORT=8080
ASSET_URL=/

APP_LOCALE=ru
APP_FALLBACK_LOCALE=en

LOG_CHANNEL=stack
LOG_LEVEL=error

DB_CONNECTION=pgsql
DB_HOST=db
DB_PORT=5432
DB_DATABASE=seo_platform
DB_USERNAME=seo_user
DB_PASSWORD=${DB_PASS}

REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379

CACHE_STORE=redis
CACHE_PREFIX=seo_platform
SESSION_DRIVER=redis
SESSION_LIFETIME=120
SESSION_DOMAIN=

QUEUE_CONNECTION=redis

SANCTUM_STATEFUL_DOMAINS=localhost,127.0.0.1,${SERVER_IP}
ENVFILE

# Сохраняем ключ
mkdir -p /root/seo-backups
echo "$APP_KEY" > /root/.seo-platform-key
echo "$APP_KEY" > /root/seo-backups/.app_key
chmod 600 /root/.seo-platform-key /root/seo-backups/.app_key

echo "✓ Конфигурация создана"

# 5. Запуск Docker
echo "[4/7] Запуск контейнеров (3-5 минут)..."
docker compose down -v 2>/dev/null || true
docker compose up -d --build

echo "  Ожидание готовности БД..."
for i in {1..30}; do
    if docker compose exec -T db pg_isready -U seo_user -d seo_platform &>/dev/null; then
        break
    fi
    sleep 2
done
echo "✓ Контейнеры запущены"

# 6. Инициализация Laravel
echo "[5/7] Инициализация приложения..."
sleep 10
docker compose exec -T app php artisan migrate --force --seed
docker compose exec -T app php artisan config:cache
docker compose exec -T app php artisan route:cache
docker compose exec -T app php artisan view:cache
docker compose exec -T app chown -R www-data:www-data /var/www/storage
echo "✓ Приложение инициализировано"

# 7. Создание админа
echo "[6/7] Создание администратора..."
echo ""
docker compose exec -T app php artisan admin:create

# 8. Готово
echo ""
echo "[7/7] Проверка..."
sleep 5
if curl -s -o /dev/null -w "%{http_code}" http://localhost:8080 | grep -q "200\|302"; then
    STATUS="✓ Работает"
else
    STATUS="⚠ Проверьте вручную"
fi

echo ""
echo "========================================"
echo "  УСТАНОВКА ЗАВЕРШЕНА!"
echo "========================================"
echo ""
echo "  Админ-панель: http://${SERVER_IP}:8080"
echo "  Статус: ${STATUS}"
echo ""
echo "  Пароль БД: ${DB_PASS}"
echo "  (сохраните в надёжное место!)"
echo ""
echo "  Команды:"
echo "    docker compose logs -f    # логи"
echo "    docker compose restart    # перезапуск"
echo "    docker compose down       # остановка"
echo ""
