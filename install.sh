#!/bin/bash

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

print_header() {
    echo -e "${BLUE}"
    echo "╔═══════════════════════════════════════════════════════════════════╗"
    echo "║           SEO Landing Platform - Installation v3.6               ║"
    echo "╚═══════════════════════════════════════════════════════════════════╝"
    echo -e "${NC}"
}

print_step() {
    echo -e "${YELLOW}[STEP]${NC} $1"
}

print_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

check_docker() {
    print_step "Проверка Docker..."
    
    if ! command -v docker &> /dev/null; then
        print_error "Docker не установлен. Установите Docker и повторите."
        exit 1
    fi
    
    # Проверяем docker compose (новый синтаксис)
    if docker compose version &> /dev/null; then
        DOCKER_COMPOSE="docker compose"
    elif command -v docker-compose &> /dev/null; then
        DOCKER_COMPOSE="docker-compose"
    else
        print_error "Docker Compose не найден."
        exit 1
    fi
    
    print_success "Docker найден: $($DOCKER_COMPOSE version 2>/dev/null | head -1)"
}

create_env() {
    print_step "Настройка окружения..."
    
    if [ ! -f .env ]; then
        cp .env.example .env
        
        # Generate random password for DB
        DB_PASS=$(openssl rand -base64 12 | tr -dc 'a-zA-Z0-9' | head -c 16)
        sed -i "s/DB_PASSWORD=secret/DB_PASSWORD=$DB_PASS/" .env
        
        # Set correct APP_URL based on server IP
        SERVER_IP=$(hostname -I | awk '{print $1}')
        APP_PORT=$(grep APP_PORT .env | cut -d '=' -f2)
        APP_PORT=${APP_PORT:-8080}
        sed -i "s|APP_URL=.*|APP_URL=http://$SERVER_IP:$APP_PORT|" .env
        
        print_success ".env создан (APP_URL=http://$SERVER_IP:$APP_PORT)"
    else
        print_info ".env уже существует"
    fi
}

build_containers() {
    print_step "Сборка Docker контейнеров (это может занять 3-5 минут)..."
    
    $DOCKER_COMPOSE build --no-cache
    
    print_success "Контейнеры собраны"
}

start_containers() {
    print_step "Запуск контейнеров..."
    
    $DOCKER_COMPOSE up -d
    
    print_info "Ожидание готовности сервисов..."
    
    # Ждём готовности БД
    for i in {1..30}; do
        if $DOCKER_COMPOSE exec -T db pg_isready -U seo_user -d seo_platform &>/dev/null; then
            print_success "PostgreSQL готов"
            break
        fi
        echo -n "."
        sleep 2
    done
    echo ""
    
    # Ждём готовности Redis
    for i in {1..15}; do
        if $DOCKER_COMPOSE exec -T redis redis-cli ping &>/dev/null; then
            print_success "Redis готов"
            break
        fi
        sleep 1
    done
    
    print_success "Контейнеры запущены"
}

fix_permissions() {
    print_step "Установка прав доступа..."
    
    $DOCKER_COMPOSE exec -T app chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache
    $DOCKER_COMPOSE exec -T app chmod -R 775 /var/www/storage /var/www/bootstrap/cache
    
    print_success "Права установлены"
}

check_vite_build() {
    print_step "Проверка Vite assets..."
    
    # Ждём пока entrypoint скопирует assets (до 30 сек)
    for i in {1..15}; do
        if $DOCKER_COMPOSE exec -T app test -f /var/www/public/build/manifest.json 2>/dev/null; then
            print_success "Vite assets найдены"
            return 0
        fi
        echo -n "."
        sleep 2
    done
    echo ""
    
    # Если assets всё ещё нет - копируем из кэша или собираем
    print_info "Vite assets не найдены, копируем из кэша..."
    $DOCKER_COMPOSE exec -T app sh -c "cp -r /var/www/build-cache/* /var/www/public/build/ 2>/dev/null || true"
    
    if ! $DOCKER_COMPOSE exec -T app test -f /var/www/public/build/manifest.json 2>/dev/null; then
        print_info "Собираем Vite assets..."
        $DOCKER_COMPOSE exec -T app npm run build 2>/dev/null || {
            $DOCKER_COMPOSE exec -T app npm install
            $DOCKER_COMPOSE exec -T app npm run build
        }
    fi
    
    $DOCKER_COMPOSE exec -T app chown -R www-data:www-data /var/www/public/build
    print_success "Vite assets готовы"
}

init_app() {
    print_step "Инициализация приложения..."
    
    # Generate key
    $DOCKER_COMPOSE exec -T app php artisan key:generate --force
    print_success "APP_KEY сгенерирован"
    
    # Run migrations
    print_info "Запуск миграций..."
    $DOCKER_COMPOSE exec -T app php artisan migrate --force --seed
    print_success "Миграции выполнены"
    
    # Cache
    $DOCKER_COMPOSE exec -T app php artisan config:cache
    $DOCKER_COMPOSE exec -T app php artisan route:cache
    $DOCKER_COMPOSE exec -T app php artisan view:cache
    print_success "Кэш создан"
}

create_admin() {
    print_step "Создание администратора..."
    
    echo ""
    read -p "  Email администратора: " ADMIN_EMAIL
    read -s -p "  Пароль администратора: " ADMIN_PASS
    echo ""
    read -p "  Имя администратора [Administrator]: " ADMIN_NAME
    ADMIN_NAME=${ADMIN_NAME:-Administrator}
    
    $DOCKER_COMPOSE exec -T app php artisan tinker --execute="
        \$user = new App\Models\User();
        \$user->name = '$ADMIN_NAME';
        \$user->email = '$ADMIN_EMAIL';
        \$user->password = bcrypt('$ADMIN_PASS');
        \$user->email_verified_at = now();
        \$user->is_admin = true;
        \$user->save();
        echo 'Admin created successfully';
    "
    
    print_success "Администратор создан"
}

create_primary_server() {
    print_step "Создание основного сервера..."
    
    SERVER_IP=$(hostname -I | awk '{print $1}')
    
    $DOCKER_COMPOSE exec -T app php artisan tinker --execute="
        \$server = new App\Models\Server();
        \$server->name = 'Primary Server';
        \$server->ip_address = '$SERVER_IP';
        \$server->is_active = true;
        \$server->is_primary = true;
        \$server->settings = ['web_root' => '/var/www/sites'];
        \$server->save();
        echo 'Primary server created';
    "
    
    print_success "Основной сервер создан (IP: $SERVER_IP)"
}

print_final() {
    APP_PORT=$(grep APP_PORT .env 2>/dev/null | cut -d '=' -f2)
    APP_PORT=${APP_PORT:-8080}
    SERVER_IP=$(hostname -I | awk '{print $1}')
    
    echo ""
    echo -e "${GREEN}╔═══════════════════════════════════════════════════════════════════╗${NC}"
    echo -e "${GREEN}║                    УСТАНОВКА ЗАВЕРШЕНА!                          ║${NC}"
    echo -e "${GREEN}╚═══════════════════════════════════════════════════════════════════╝${NC}"
    echo ""
    echo -e "  ${BLUE}Админ-панель:${NC}  http://$SERVER_IP:$APP_PORT"
    echo ""
    echo -e "  ${YELLOW}Полезные команды:${NC}"
    echo "    $DOCKER_COMPOSE logs -f          # Логи"
    echo "    $DOCKER_COMPOSE ps               # Статус"
    echo "    $DOCKER_COMPOSE restart          # Перезапуск"
    echo "    $DOCKER_COMPOSE down             # Остановка"
    echo ""
}

main() {
    print_header
    check_docker
    create_env
    build_containers
    start_containers
    fix_permissions
    check_vite_build
    init_app
    create_admin
    create_primary_server
    print_final
}

main
