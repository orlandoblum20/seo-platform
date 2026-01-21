#!/bin/bash
#
# SEO Platform - Скрипт установки и обновления
# Версия: 2.0 (с защитой APP_KEY)
# 
# Использование:
#   Первая установка:  ./deploy.sh install
#   Обновление:        ./deploy.sh update /path/to/seo-platform-vX.X.X.zip
#   Только фронтенд:   ./deploy.sh rebuild
#   Откат:             ./deploy.sh rollback
#
# КРИТИЧНО: APP_KEY защищён многоуровневой системой резервирования
#

set -e

# ==================== КОНФИГУРАЦИЯ ====================
PROJECT_DIR="/root/seo-platform"
BACKUP_DIR="/root/seo-backups"
DOCKER_COMPOSE="docker compose"

# ЗАЩИТА APP_KEY - несколько мест хранения
ENV_KEY_FILE="/root/.seo-platform-key"        # Отдельный файл с APP_KEY (главное хранилище)
ENV_KEY_BACKUP="/root/seo-backups/.app_key"   # Бэкап ключа в папке бэкапов

# Цвета для вывода
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m'

# ==================== ФУНКЦИИ ЛОГИРОВАНИЯ ====================

log_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

log_success() {
    echo -e "${GREEN}[OK]${NC} $1"
}

log_warning() {
    echo -e "${YELLOW}[WARN]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

log_critical() {
    echo -e "${RED}[CRITICAL]${NC} $1"
}

# ==================== ЗАЩИТА APP_KEY ====================

# Извлечь APP_KEY из .env файла
extract_app_key() {
    local env_file="$1"
    if [ -f "$env_file" ]; then
        grep "^APP_KEY=" "$env_file" 2>/dev/null | cut -d'=' -f2- | tr -d '"' | tr -d "'"
    fi
}

# Проверить валидность APP_KEY (должен начинаться с base64:)
is_valid_app_key() {
    local key="$1"
    if [ -n "$key" ] && [[ "$key" == base64:* ]] && [ ${#key} -gt 50 ]; then
        return 0
    fi
    return 1
}

# Сохранить APP_KEY в защищённое хранилище
save_app_key_to_vault() {
    local key="$1"
    
    if ! is_valid_app_key "$key"; then
        log_warning "Попытка сохранить невалидный APP_KEY - пропущено"
        return 1
    fi
    
    # Сохраняем в главное хранилище
    echo "$key" > "$ENV_KEY_FILE"
    chmod 600 "$ENV_KEY_FILE"
    
    # Сохраняем в бэкап
    mkdir -p "$(dirname "$ENV_KEY_BACKUP")"
    echo "$key" > "$ENV_KEY_BACKUP"
    chmod 600 "$ENV_KEY_BACKUP"
    
    log_success "APP_KEY сохранён в защищённое хранилище"
    return 0
}

# Получить APP_KEY из любого доступного источника
get_app_key_from_any_source() {
    local key=""
    
    # 1. Пробуем текущий .env
    if [ -f "$PROJECT_DIR/.env" ]; then
        key=$(extract_app_key "$PROJECT_DIR/.env")
        if is_valid_app_key "$key"; then
            echo "$key"
            return 0
        fi
    fi
    
    # 2. Пробуем главное хранилище
    if [ -f "$ENV_KEY_FILE" ]; then
        key=$(cat "$ENV_KEY_FILE" 2>/dev/null | tr -d '\n')
        if is_valid_app_key "$key"; then
            echo "$key"
            return 0
        fi
    fi
    
    # 3. Пробуем бэкап хранилища
    if [ -f "$ENV_KEY_BACKUP" ]; then
        key=$(cat "$ENV_KEY_BACKUP" 2>/dev/null | tr -d '\n')
        if is_valid_app_key "$key"; then
            echo "$key"
            return 0
        fi
    fi
    
    # 4. Пробуем бэкапы .env
    if [ -d "$BACKUP_DIR" ]; then
        for backup_file in $(ls -t "$BACKUP_DIR"/.env.backup_* 2>/dev/null | head -5); do
            key=$(extract_app_key "$backup_file")
            if is_valid_app_key "$key"; then
                log_info "APP_KEY восстановлен из бэкапа: $backup_file"
                echo "$key"
                return 0
            fi
        done
    fi
    
    # Ничего не найдено
    return 1
}

# Генерировать новый APP_KEY
generate_new_app_key() {
    local key="base64:$(openssl rand -base64 32)"
    echo "$key"
}

# ГЛАВНАЯ ФУНКЦИЯ: Гарантировать наличие валидного APP_KEY
ensure_app_key() {
    log_info "Проверка APP_KEY..."
    
    local current_key=""
    local env_file="$PROJECT_DIR/.env"
    
    # Проверяем текущий .env
    if [ -f "$env_file" ]; then
        current_key=$(extract_app_key "$env_file")
    fi
    
    # Если ключ валидный - сохраняем в хранилище и выходим
    if is_valid_app_key "$current_key"; then
        log_success "APP_KEY валидный"
        save_app_key_to_vault "$current_key"
        return 0
    fi
    
    # Ключ невалидный или отсутствует - пытаемся восстановить
    log_warning "APP_KEY отсутствует или невалидный, восстанавливаем..."
    
    local restored_key=$(get_app_key_from_any_source)
    
    if is_valid_app_key "$restored_key"; then
        # Восстановили из резервного источника
        log_success "APP_KEY восстановлен из резервного хранилища"
        
        # Записываем в .env
        if [ -f "$env_file" ]; then
            # Заменяем или добавляем APP_KEY
            if grep -q "^APP_KEY=" "$env_file"; then
                sed -i "s|^APP_KEY=.*|APP_KEY=$restored_key|" "$env_file"
            else
                echo "APP_KEY=$restored_key" >> "$env_file"
            fi
        fi
        
        save_app_key_to_vault "$restored_key"
        return 0
    fi
    
    # Нигде не нашли - генерируем новый
    log_warning "APP_KEY не найден ни в одном источнике - генерируем НОВЫЙ"
    log_critical "ВНИМАНИЕ: Все ранее зашифрованные данные будут недоступны!"
    
    local new_key=$(generate_new_app_key)
    
    if [ -f "$env_file" ]; then
        if grep -q "^APP_KEY=" "$env_file"; then
            sed -i "s|^APP_KEY=.*|APP_KEY=$new_key|" "$env_file"
        else
            echo "APP_KEY=$new_key" >> "$env_file"
        fi
    fi
    
    save_app_key_to_vault "$new_key"
    log_success "Новый APP_KEY сгенерирован и сохранён"
    
    return 0
}

# Защитить .env перед любой операцией
protect_env_before_operation() {
    log_info "Защита .env перед операцией..."
    
    # 1. Сохраняем текущий APP_KEY в хранилище
    if [ -f "$PROJECT_DIR/.env" ]; then
        local key=$(extract_app_key "$PROJECT_DIR/.env")
        if is_valid_app_key "$key"; then
            save_app_key_to_vault "$key"
        fi
    fi
    
    # 2. Создаём бэкап .env с timestamp
    mkdir -p "$BACKUP_DIR"
    local timestamp=$(date +%Y%m%d_%H%M%S)
    
    if [ -f "$PROJECT_DIR/.env" ]; then
        cp "$PROJECT_DIR/.env" "$BACKUP_DIR/.env.backup_$timestamp"
        log_success "Бэкап .env создан: .env.backup_$timestamp"
    fi
    
    # 3. Сохраняем копию в /tmp для быстрого восстановления
    if [ -f "$PROJECT_DIR/.env" ]; then
        cp "$PROJECT_DIR/.env" /tmp/.env.protected
    fi
}

# Восстановить .env после операции
restore_env_after_operation() {
    log_info "Восстановление .env после операции..."
    
    # 1. Если .env исчез - восстанавливаем из /tmp
    if [ ! -f "$PROJECT_DIR/.env" ] && [ -f /tmp/.env.protected ]; then
        cp /tmp/.env.protected "$PROJECT_DIR/.env"
        log_warning ".env восстановлен из временной копии"
    fi
    
    # 2. Если .env есть но пустой APP_KEY - восстанавливаем
    ensure_app_key
    
    # 3. Проверяем обязательные переменные
    ensure_required_env_vars
    
    # 4. Очищаем временные файлы
    rm -f /tmp/.env.protected
    
    log_success ".env защищён и проверен"
}

# Проверить и добавить обязательные переменные
ensure_required_env_vars() {
    local env_file="$PROJECT_DIR/.env"
    
    if [ ! -f "$env_file" ]; then
        return 0
    fi
    
    # ASSET_URL
    if ! grep -q "^ASSET_URL=" "$env_file"; then
        echo "ASSET_URL=/" >> "$env_file"
        log_info "Добавлен ASSET_URL=/"
    fi
    
    # Проверяем что ASSET_URL не пустой
    local asset_url=$(grep "^ASSET_URL=" "$env_file" | cut -d'=' -f2-)
    if [ -z "$asset_url" ]; then
        sed -i "s|^ASSET_URL=.*|ASSET_URL=/|" "$env_file"
        log_info "Исправлен пустой ASSET_URL"
    fi
}

# ==================== ОБЩИЕ ФУНКЦИИ ====================

check_root() {
    if [ "$EUID" -ne 0 ]; then
        log_error "Скрипт должен быть запущен от root"
        exit 1
    fi
}

# Создание полного бэкапа
create_full_backup() {
    log_info "Создание полного бэкапа..."
    
    mkdir -p "$BACKUP_DIR"
    local timestamp=$(date +%Y%m%d_%H%M%S)
    
    # 1. Бэкап .env (самое важное!)
    protect_env_before_operation
    
    # 2. Бэкап базы данных
    if $DOCKER_COMPOSE -f "$PROJECT_DIR/docker-compose.yml" ps 2>/dev/null | grep -q "db.*Up"; then
        log_info "Создание дампа базы данных..."
        $DOCKER_COMPOSE -f "$PROJECT_DIR/docker-compose.yml" exec -T db \
            pg_dump -U seo_user seo_platform > "$BACKUP_DIR/db_$timestamp.sql" 2>/dev/null || true
        
        if [ -s "$BACKUP_DIR/db_$timestamp.sql" ]; then
            log_success "Дамп БД создан: db_$timestamp.sql"
        fi
    fi
    
    # 3. Бэкап сгенерированных сайтов
    if [ -d "$PROJECT_DIR/storage/app/sites" ] && [ "$(ls -A $PROJECT_DIR/storage/app/sites 2>/dev/null)" ]; then
        tar -czf "$BACKUP_DIR/sites_$timestamp.tar.gz" -C "$PROJECT_DIR/storage/app" sites 2>/dev/null || true
        log_success "Бэкап сайтов создан"
    fi
    
    log_success "Бэкап завершён (timestamp: $timestamp)"
}

# Распаковка архива (БЕЗОПАСНАЯ)
extract_update_safe() {
    local zip_file="$1"
    
    if [ ! -f "$zip_file" ]; then
        log_error "Файл не найден: $zip_file"
        exit 1
    fi
    
    log_info "Распаковка архива (безопасный режим)..."
    
    # КРИТИЧНО: Защищаем .env перед распаковкой
    protect_env_before_operation
    
    # Создаём временную директорию
    local tmp_dir=$(mktemp -d)
    
    # Распаковываем БЕЗ .env файлов
    unzip -o "$zip_file" -d "$tmp_dir" -x "*.env" -x "*.env.*" > /dev/null 2>&1 || {
        log_error "Ошибка распаковки архива"
        rm -rf "$tmp_dir"
        exit 1
    }
    
    # Находим корневую папку
    local extracted_dir=$(find "$tmp_dir" -maxdepth 1 -type d -name "seo-platform*" | head -1)
    if [ -z "$extracted_dir" ]; then
        extracted_dir="$tmp_dir"
    fi
    
    # Копируем файлы БЕЗ затирания .env и storage/app/sites
    rsync -av \
        --exclude='.env' \
        --exclude='.env.*' \
        --exclude='storage/app/sites/*' \
        --exclude='storage/logs/*' \
        "$extracted_dir/" "$PROJECT_DIR/"
    
    # Очищаем временные файлы
    rm -rf "$tmp_dir"
    
    # КРИТИЧНО: Восстанавливаем .env после распаковки
    restore_env_after_operation
    
    log_success "Архив распакован (защита .env активна)"
}

# Создание .env для новой установки
create_initial_env() {
    log_info "Создание начального .env..."
    
    local env_file="$PROJECT_DIR/.env"
    
    # Если .env уже существует - не трогаем
    if [ -f "$env_file" ]; then
        local key=$(extract_app_key "$env_file")
        if is_valid_app_key "$key"; then
            log_success ".env уже существует с валидным APP_KEY"
            save_app_key_to_vault "$key"
            return 0
        fi
    fi
    
    # Создаём из .env.example
    if [ -f "$PROJECT_DIR/.env.example" ]; then
        cp "$PROJECT_DIR/.env.example" "$env_file"
        log_info "Создан .env из .env.example"
    else
        # Создаём минимальный .env
        cat > "$env_file" << 'ENVFILE'
APP_NAME="SEO Platform"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://your-domain.com
ASSET_URL=/

LOG_CHANNEL=stack
LOG_LEVEL=debug

DB_CONNECTION=pgsql
DB_HOST=db
DB_PORT=5432
DB_DATABASE=seo_platform
DB_USERNAME=seo_user
DB_PASSWORD=your_secure_password_here

REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379

CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

SANCTUM_STATEFUL_DOMAINS=localhost,127.0.0.1,your-domain.com
SESSION_DOMAIN=.your-domain.com

CADDY_ADMIN_API=http://host.docker.internal:2019
CADDY_CADDYFILE=/etc/caddy/Caddyfile
CADDY_SITES_PATH=/etc/caddy/sites
ENVFILE
        log_info "Создан базовый .env"
    fi
    
    # Генерируем и устанавливаем APP_KEY
    ensure_app_key
    
    log_warning "ВАЖНО: Настройте .env файл (особенно DB_PASSWORD, APP_URL)!"
}

# Установка зависимостей
install_dependencies() {
    log_info "Установка зависимостей..."
    
    cd "$PROJECT_DIR"
    
    # PHP зависимости
    $DOCKER_COMPOSE exec -T app composer install --no-dev --optimize-autoloader 2>/dev/null || \
    $DOCKER_COMPOSE exec -T app composer install --optimize-autoloader
    log_success "PHP зависимости установлены"
    
    # Node.js зависимости
    $DOCKER_COMPOSE exec -T app npm ci 2>/dev/null || \
    $DOCKER_COMPOSE exec -T app npm install
    log_success "Node.js зависимости установлены"
}

# Сборка фронтенда
build_frontend() {
    log_info "Сборка фронтенда..."
    
    cd "$PROJECT_DIR"
    $DOCKER_COMPOSE exec -T app npm run build
    
    log_success "Фронтенд собран"
}

# Миграции
run_migrations() {
    log_info "Выполнение миграций..."
    
    cd "$PROJECT_DIR"
    $DOCKER_COMPOSE exec -T app php artisan migrate --force
    
    log_success "Миграции выполнены"
}

# Очистка и кэширование
clear_cache() {
    log_info "Очистка кэша..."
    
    cd "$PROJECT_DIR"
    $DOCKER_COMPOSE exec -T app php artisan config:clear
    $DOCKER_COMPOSE exec -T app php artisan cache:clear
    $DOCKER_COMPOSE exec -T app php artisan view:clear
    $DOCKER_COMPOSE exec -T app php artisan route:clear
    
    # Оптимизация
    $DOCKER_COMPOSE exec -T app php artisan config:cache
    $DOCKER_COMPOSE exec -T app php artisan route:cache
    $DOCKER_COMPOSE exec -T app php artisan view:cache
    
    log_success "Кэш очищен и пересоздан"
}

# Права доступа
fix_permissions() {
    log_info "Установка прав доступа..."
    
    cd "$PROJECT_DIR"
    
    # Создаём директории
    mkdir -p storage/app/sites
    mkdir -p storage/app/public
    mkdir -p storage/framework/{cache/data,sessions,testing,views}
    mkdir -p storage/logs
    mkdir -p bootstrap/cache
    
    # Права
    chmod -R 775 storage bootstrap/cache
    $DOCKER_COMPOSE exec -T app chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
    
    log_success "Права установлены"
}

# Запуск контейнеров
start_containers() {
    log_info "Запуск контейнеров..."
    
    cd "$PROJECT_DIR"
    $DOCKER_COMPOSE up -d
    
    log_info "Ожидание запуска сервисов..."
    sleep 10
    
    log_success "Контейнеры запущены"
}

# Перезапуск контейнеров
restart_containers() {
    log_info "Перезапуск контейнеров..."
    
    cd "$PROJECT_DIR"
    $DOCKER_COMPOSE restart
    sleep 5
    
    log_success "Контейнеры перезапущены"
}

# Проверка здоровья
health_check() {
    log_info "Проверка работоспособности..."
    
    cd "$PROJECT_DIR"
    local errors=0
    
    # Контейнеры
    if ! $DOCKER_COMPOSE ps | grep -q "app.*Up"; then
        log_error "Контейнер app не запущен!"
        errors=$((errors + 1))
    else
        log_success "Контейнер app работает"
    fi
    
    if ! $DOCKER_COMPOSE ps | grep -q "db.*Up"; then
        log_error "Контейнер db не запущен!"
        errors=$((errors + 1))
    else
        log_success "Контейнер db работает"
    fi
    
    # Laravel
    if $DOCKER_COMPOSE exec -T app php artisan --version > /dev/null 2>&1; then
        log_success "Laravel отвечает"
    else
        log_error "Laravel не отвечает!"
        errors=$((errors + 1))
    fi
    
    # APP_KEY
    local key=$(extract_app_key "$PROJECT_DIR/.env")
    if is_valid_app_key "$key"; then
        log_success "APP_KEY валидный"
    else
        log_error "APP_KEY невалидный или отсутствует!"
        errors=$((errors + 1))
    fi
    
    if [ $errors -eq 0 ]; then
        log_success "Все проверки пройдены!"
        return 0
    else
        log_error "Обнаружено ошибок: $errors"
        return 1
    fi
}

# Показать статус
show_status() {
    echo ""
    echo -e "${CYAN}==========================================${NC}"
    echo -e "${CYAN}  СТАТУС SEO PLATFORM${NC}"
    echo -e "${CYAN}==========================================${NC}"
    
    cd "$PROJECT_DIR" 2>/dev/null || {
        log_error "Проект не найден в $PROJECT_DIR"
        return 1
    }
    
    echo ""
    echo "Контейнеры:"
    $DOCKER_COMPOSE ps 2>/dev/null || echo "  Docker Compose не доступен"
    
    echo ""
    echo "Версия Laravel:"
    $DOCKER_COMPOSE exec -T app php artisan --version 2>/dev/null || echo "  Не удалось получить"
    
    echo ""
    echo "APP_KEY:"
    local key=$(extract_app_key "$PROJECT_DIR/.env")
    if is_valid_app_key "$key"; then
        echo -e "  ${GREEN}✓ Установлен и валидный${NC}"
        echo "  Первые символы: ${key:0:20}..."
    else
        echo -e "  ${RED}✗ ОТСУТСТВУЕТ ИЛИ НЕВАЛИДНЫЙ!${NC}"
    fi
    
    echo ""
    echo "Хранилище APP_KEY:"
    if [ -f "$ENV_KEY_FILE" ]; then
        echo -e "  ${GREEN}✓ $ENV_KEY_FILE${NC}"
    else
        echo -e "  ${YELLOW}○ $ENV_KEY_FILE (не создан)${NC}"
    fi
    if [ -f "$ENV_KEY_BACKUP" ]; then
        echo -e "  ${GREEN}✓ $ENV_KEY_BACKUP${NC}"
    else
        echo -e "  ${YELLOW}○ $ENV_KEY_BACKUP (не создан)${NC}"
    fi
    
    echo ""
    echo "ASSET_URL:"
    if grep -q "^ASSET_URL=/" "$PROJECT_DIR/.env" 2>/dev/null; then
        echo -e "  ${GREEN}✓ Установлен${NC}"
    else
        echo -e "  ${RED}✗ Не установлен!${NC}"
    fi
    
    echo ""
    echo "Бэкапы .env:"
    ls -la "$BACKUP_DIR"/.env.backup_* 2>/dev/null | tail -3 || echo "  Нет бэкапов"
    
    echo ""
}

# ==================== КОМАНДЫ ====================

# Установка
cmd_install() {
    echo -e "${CYAN}==========================================${NC}"
    echo -e "${CYAN}  SEO PLATFORM - УСТАНОВКА${NC}"
    echo -e "${CYAN}==========================================${NC}"
    echo ""
    
    check_root
    
    # Проверяем Docker
    if ! command -v docker &> /dev/null; then
        log_error "Docker не установлен!"
        exit 1
    fi
    
    # Создаём директории
    mkdir -p "$PROJECT_DIR"
    mkdir -p "$BACKUP_DIR"
    cd "$PROJECT_DIR"
    
    # Если передан ZIP - распаковываем
    if [ -n "$1" ] && [ -f "$1" ]; then
        extract_update_safe "$1"
    fi
    
    # Создаём/проверяем .env
    create_initial_env
    
    echo ""
    log_warning "═══════════════════════════════════════════════"
    log_warning "  НАСТРОЙТЕ .env ФАЙЛ ПЕРЕД ПРОДОЛЖЕНИЕМ!"
    log_warning "  Особенно: DB_PASSWORD, APP_URL, SESSION_DOMAIN"
    log_warning "═══════════════════════════════════════════════"
    echo ""
    read -p "Нажмите Enter после настройки .env..."
    
    # Финальная проверка APP_KEY
    ensure_app_key
    
    # Запуск
    start_containers
    install_dependencies
    build_frontend
    run_migrations
    
    # Сидеры
    log_info "Запуск сидеров..."
    $DOCKER_COMPOSE exec -T app php artisan db:seed --force
    
    # Создание админа
    log_info "Создание администратора..."
    $DOCKER_COMPOSE exec -T app php artisan admin:create
    
    # Primary сервер
    log_info "Создание primary сервера..."
    $DOCKER_COMPOSE exec -T app php artisan server:create-primary || true
    
    # Финализация
    fix_permissions
    clear_cache
    
    # Проверка
    health_check
    show_status
    
    echo ""
    log_success "═══════════════════════════════════════════════"
    log_success "  УСТАНОВКА ЗАВЕРШЕНА!"
    log_success "═══════════════════════════════════════════════"
    echo ""
}

# Обновление
cmd_update() {
    echo -e "${CYAN}==========================================${NC}"
    echo -e "${CYAN}  SEO PLATFORM - ОБНОВЛЕНИЕ${NC}"
    echo -e "${CYAN}==========================================${NC}"
    echo ""
    
    check_root
    
    if [ ! -d "$PROJECT_DIR" ]; then
        log_error "Проект не найден в $PROJECT_DIR"
        log_info "Используйте: ./deploy.sh install"
        exit 1
    fi
    
    cd "$PROJECT_DIR"
    
    # Полный бэкап
    create_full_backup
    
    # Если передан ZIP - распаковываем
    if [ -n "$1" ] && [ -f "$1" ]; then
        extract_update_safe "$1"
    else
        log_warning "ZIP файл не указан"
        log_info "Использование: ./deploy.sh update /path/to/seo-platform-vX.X.X.zip"
        echo ""
        read -p "Продолжить без распаковки (только пересборка)? [y/N] " -n 1 -r
        echo
        if [[ ! $REPLY =~ ^[Yy]$ ]]; then
            exit 0
        fi
    fi
    
    # КРИТИЧНО: Проверяем APP_KEY после любых операций
    ensure_app_key
    ensure_required_env_vars
    
    # Обновление
    restart_containers
    install_dependencies
    build_frontend
    run_migrations
    fix_permissions
    clear_cache
    
    # Проверка
    health_check
    show_status
    
    echo ""
    log_success "═══════════════════════════════════════════════"
    log_success "  ОБНОВЛЕНИЕ ЗАВЕРШЕНО!"
    log_success "═══════════════════════════════════════════════"
    echo ""
}

# Пересборка фронтенда
cmd_rebuild() {
    echo -e "${CYAN}==========================================${NC}"
    echo -e "${CYAN}  SEO PLATFORM - ПЕРЕСБОРКА ФРОНТЕНДА${NC}"
    echo -e "${CYAN}==========================================${NC}"
    echo ""
    
    check_root
    cd "$PROJECT_DIR"
    
    build_frontend
    clear_cache
    
    log_success "Фронтенд пересобран!"
}

# Откат
cmd_rollback() {
    echo -e "${CYAN}==========================================${NC}"
    echo -e "${CYAN}  SEO PLATFORM - ОТКАТ${NC}"
    echo -e "${CYAN}==========================================${NC}"
    echo ""
    
    check_root
    
    echo "Доступные бэкапы .env:"
    ls -la "$BACKUP_DIR"/.env.backup_* 2>/dev/null || echo "  Нет бэкапов"
    
    echo ""
    echo "Доступные дампы БД:"
    ls -la "$BACKUP_DIR"/db_*.sql 2>/dev/null || echo "  Нет дампов"
    
    echo ""
    read -p "Введите timestamp для отката (например 20260121_150000): " TIMESTAMP
    
    if [ -z "$TIMESTAMP" ]; then
        log_error "Timestamp не указан"
        exit 1
    fi
    
    # Восстанавливаем .env
    if [ -f "$BACKUP_DIR/.env.backup_$TIMESTAMP" ]; then
        cp "$BACKUP_DIR/.env.backup_$TIMESTAMP" "$PROJECT_DIR/.env"
        ensure_app_key
        log_success ".env восстановлен"
    else
        log_warning "Бэкап .env не найден"
    fi
    
    # Восстанавливаем БД
    if [ -f "$BACKUP_DIR/db_$TIMESTAMP.sql" ]; then
        log_info "Восстановление базы данных..."
        cd "$PROJECT_DIR"
        $DOCKER_COMPOSE exec -T db psql -U seo_user -d seo_platform < "$BACKUP_DIR/db_$TIMESTAMP.sql"
        log_success "База данных восстановлена"
    else
        log_warning "Дамп БД не найден"
    fi
    
    restart_containers
    clear_cache
    health_check
    
    log_success "Откат завершён!"
}

# Восстановить APP_KEY вручную
cmd_fix_key() {
    echo -e "${CYAN}==========================================${NC}"
    echo -e "${CYAN}  SEO PLATFORM - ВОССТАНОВЛЕНИЕ APP_KEY${NC}"
    echo -e "${CYAN}==========================================${NC}"
    echo ""
    
    check_root
    cd "$PROJECT_DIR"
    
    echo "Текущий статус APP_KEY:"
    local current=$(extract_app_key "$PROJECT_DIR/.env")
    if is_valid_app_key "$current"; then
        echo -e "  ${GREEN}✓ Валидный: ${current:0:30}...${NC}"
    else
        echo -e "  ${RED}✗ Невалидный или отсутствует${NC}"
    fi
    
    echo ""
    echo "Попытка восстановления из резервных источников..."
    
    ensure_app_key
    
    echo ""
    echo "Результат:"
    current=$(extract_app_key "$PROJECT_DIR/.env")
    if is_valid_app_key "$current"; then
        echo -e "  ${GREEN}✓ APP_KEY установлен: ${current:0:30}...${NC}"
    else
        echo -e "  ${RED}✗ Не удалось восстановить APP_KEY${NC}"
    fi
    
    # Перезапускаем кэш
    clear_cache
    
    log_success "Готово!"
}

# Справка
cmd_help() {
    echo "SEO Platform - Скрипт установки и обновления v2.0"
    echo ""
    echo "Использование:"
    echo "  $0 install [zip]    - Первая установка"
    echo "  $0 update [zip]     - Обновление"
    echo "  $0 rebuild          - Пересборка фронтенда"
    echo "  $0 rollback         - Откат из бэкапа"
    echo "  $0 fix-key          - Восстановить APP_KEY"
    echo "  $0 status           - Показать статус"
    echo "  $0 help             - Эта справка"
    echo ""
    echo "Примеры:"
    echo "  $0 install /root/seo-platform-v3.6.13.zip"
    echo "  $0 update /root/seo-platform-v3.6.14.zip"
    echo "  $0 fix-key"
    echo ""
    echo "Защита APP_KEY:"
    echo "  - Главное хранилище: $ENV_KEY_FILE"
    echo "  - Резервное хранилище: $ENV_KEY_BACKUP"
    echo "  - Бэкапы .env: $BACKUP_DIR/.env.backup_*"
    echo ""
}

# ==================== MAIN ====================

case "${1:-help}" in
    install)
        cmd_install "$2"
        ;;
    update)
        cmd_update "$2"
        ;;
    rebuild)
        cmd_rebuild
        ;;
    rollback)
        cmd_rollback
        ;;
    fix-key|fixkey|fix_key)
        cmd_fix_key
        ;;
    status)
        check_root
        show_status
        health_check
        ;;
    help|--help|-h)
        cmd_help
        ;;
    *)
        log_error "Неизвестная команда: $1"
        cmd_help
        exit 1
        ;;
esac
