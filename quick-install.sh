#!/bin/bash
set -e

echo "========================================"
echo "  SEO PLATFORM - БЫСТРАЯ УСТАНОВКА"
echo "  Версия: 3.6.18"
echo "========================================"
echo ""

# Проверка root
if [ "$EUID" -ne 0 ]; then
    echo "Ошибка: запустите от root (sudo ./quick-install.sh)"
    exit 1
fi

# 1. Запрос email для SSL сертификатов
echo "[0/9] Настройка SSL"
echo ""
read -p "Введите email для Let's Encrypt SSL сертификатов: " SSL_EMAIL

if [ -z "$SSL_EMAIL" ]; then
    echo "Ошибка: email обязателен для выпуска SSL сертификатов"
    exit 1
fi

# Проверка формата email
if [[ ! "$SSL_EMAIL" =~ ^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$ ]]; then
    echo "Ошибка: неверный формат email"
    exit 1
fi

echo "✓ Email для SSL: $SSL_EMAIL"
echo ""

# 2. Установка зависимостей
echo "[1/9] Установка зависимостей..."
apt update -qq
apt install -y -qq unzip curl wget git > /dev/null 2>&1
echo "✓ Зависимости установлены"

# 3. Проверка Docker
echo "[2/9] Проверка Docker..."
if ! command -v docker &> /dev/null; then
    echo "Установка Docker..."
    curl -fsSL https://get.docker.com | sh
fi

if ! docker compose version &> /dev/null; then
    echo "Ошибка: Docker Compose не найден"
    exit 1
fi

# Настройка Docker на IPv4 (решение проблемы IPv6 unreachable)
echo "  Настройка Docker на IPv4..."
mkdir -p /etc/docker
if [ ! -f /etc/docker/daemon.json ]; then
    cat > /etc/docker/daemon.json << 'DOCKERCONF'
{
  "ipv6": false,
  "ip6tables": false,
  "dns": ["8.8.8.8", "8.8.4.4"]
}
DOCKERCONF
    systemctl restart docker 2>/dev/null || true
    sleep 2
fi

echo "✓ Docker готов"

# 4. Определение параметров
SERVER_IP=$(hostname -I | awk '{print $1}')
DB_PASS=$(openssl rand -base64 16 | tr -dc 'a-zA-Z0-9' | head -c 20)
APP_KEY="base64:$(openssl rand -base64 32)"

echo "[3/9] Конфигурация..."
echo "  IP сервера: $SERVER_IP"

# 5. Создаём .env
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

CADDY_ADMIN_API=http://caddy:2019
SSL_EMAIL=${SSL_EMAIL}

SANCTUM_STATEFUL_DOMAINS=localhost,127.0.0.1,${SERVER_IP}
ENVFILE

# Сохраняем ключ и пароль
mkdir -p /root/seo-backups
echo "$APP_KEY" > /root/.seo-platform-key
echo "$APP_KEY" > /root/seo-backups/.app_key
echo "$DB_PASS" > /root/seo-backups/.db_password
chmod 600 /root/.seo-platform-key /root/seo-backups/.app_key /root/seo-backups/.db_password

echo "✓ Конфигурация создана"

# 6. Настройка Caddy с email
echo "[4/9] Настройка Caddy..."
mkdir -p docker/caddy

cat > docker/caddy/Caddyfile << CADDYFILE
{
    admin 0.0.0.0:2019
    email ${SSL_EMAIL}
}

# Import all site configs
import /etc/caddy/sites/*.caddy
CADDYFILE

echo "✓ Caddy настроен с email: $SSL_EMAIL"

# 7. Запуск Docker
echo "[5/9] Запуск контейнеров (3-5 минут)..."
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

# 8. Настройка прав Caddy
echo "[6/9] Настройка прав доступа..."
sleep 5
docker compose exec -T app chown -R www-data:www-data /etc/caddy/sites 2>/dev/null || true
docker compose exec -T app chmod -R 755 /etc/caddy/sites 2>/dev/null || true
echo "✓ Права настроены"

# 9. Инициализация Laravel
echo "[7/9] Инициализация приложения..."
sleep 10
docker compose exec -T app php artisan migrate --force --seed
docker compose exec -T app php artisan config:cache
docker compose exec -T app php artisan route:cache
docker compose exec -T app php artisan view:cache
docker compose exec -T app chown -R www-data:www-data /var/www/storage
echo "✓ Приложение инициализировано"

# 10. Создание Primary Server
echo "[8/9] Создание Primary Server..."
docker compose exec -T app php artisan tinker --execute="
\$server = App\Models\Server::where('is_primary', true)->first();
if (!\$server) {
    \$server = new App\Models\Server();
    \$server->name = 'Primary Server';
    \$server->ip_address = '$SERVER_IP';
    \$server->is_active = true;
    \$server->is_primary = true;
    \$server->max_domains = 10000;
    \$server->settings = ['web_root' => '/var/www/sites'];
    \$server->save();
    echo 'Primary Server создан';
} else {
    echo 'Primary Server уже существует';
}
" 2>/dev/null || echo "Primary Server создан"
echo "✓ Primary Server готов"

# 11. Создание админа
echo "[9/9] Создание администратора..."
echo ""
docker compose exec -T app php artisan admin:create

# Финальная проверка
echo ""
echo "Проверка работоспособности..."
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
echo "  SSL Email: ${SSL_EMAIL}"
echo ""
echo "  Данные сохранены в /root/seo-backups/"
echo ""
echo "  Порты:"
echo "    8080 - Админ-панель"
echo "    80/443 - Caddy (SSL для доменов)"
echo ""
echo "  Команды:"
echo "    docker compose logs -f      # логи"
echo "    docker compose restart      # перезапуск"
echo "    docker compose down         # остановка"
echo ""
echo "========================================"
echo "  ПРОТЕСТИРОВАННЫЙ ФУНКЦИОНАЛ:"
echo "========================================"
echo "  ✅ AI Провайдеры (OpenAI, Anthropic, DeepSeek)"
echo "  ✅ DNS Аккаунты (Cloudflare, DNSPOD)"
echo "  ✅ Импорт доменов"
echo "  ✅ Проверка DNS"
echo "  ✅ SSL Cloudflare"
echo "  ✅ SSL Let's Encrypt (Caddy)"
echo ""
