# 📦 Установка SEO Landing Platform

## Требования

| Параметр | Минимум | Рекомендуется |
|----------|---------|---------------|
| OS | Ubuntu 22.04 | Ubuntu 24.04 |
| CPU | 2 cores | 4 cores |
| RAM | 4 GB | 8 GB |
| Диск | 20 GB SSD | 50 GB SSD |

---

## 🚀 Быстрая установка (1 команда)
```bash
# 1. Скачайте и распакуйте архив
apt update && apt install -y unzip
unzip seo-platform-v3.6.15.zip -d seo-platform
cd seo-platform

# 2. Запустите установку
chmod +x quick-install.sh
./quick-install.sh
```

Установщик автоматически:
- Установит Docker (если нет)
- Настроит базу данных
- Сгенерирует безопасные пароли
- Запустит все сервисы
- Создаст администратора

---

## 📋 После установки

1. Откройте админ-панель: `http://IP_СЕРВЕРА:8080`
2. Войдите с созданными учётными данными
3. Настройте:
   - **DNS аккаунты** (Cloudflare / DNSPOD)
   - **AI провайдеры** (OpenAI / Anthropic / DeepSeek)

---

## 🔧 Полезные команды
```bash
# Логи
docker compose logs -f

# Перезапуск
docker compose restart

# Остановка
docker compose down

# Статус
docker compose ps
```

---

## 🐛 Решение проблем

### "File not found" или белая страница
```bash
docker compose down
docker compose up -d --build
```

### Ошибки базы данных
```bash
docker compose restart db
sleep 10
docker compose exec app php artisan migrate --force
```

### Сброс кэша
```bash
docker compose exec app php artisan cache:clear
docker compose exec app php artisan config:clear
```
