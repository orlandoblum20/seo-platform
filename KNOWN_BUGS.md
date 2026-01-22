# Known Bugs & Fixes - SEO Platform

## Исправленные баги

### BUG-001: Пустой ответ API при создании сайта (ИСПРАВЛЕНО v3.6.10)
**Симптом:** API возвращает пустой ответ при POST запросе
**Причина:** Отсутствие return в контроллере
**Решение:** Добавлен return response()->json()

### BUG-002: Ошибка миграции поля json (ИСПРАВЛЕНО v3.6.10)
**Симптом:** Migration failed - json column error
**Причина:** PostgreSQL требует явное указание типа
**Решение:** Использование jsonb вместо json

### BUG-003: CORS ошибки в продакшене (ИСПРАВЛЕНО v3.6.11)
**Симптом:** Blocked by CORS policy
**Причина:** Неправильная конфигурация CORS
**Решение:** Обновлён config/cors.php

### BUG-004: Не работает очередь задач (ИСПРАВЛЕНО v3.6.11)
**Симптом:** Jobs не выполняются
**Причина:** Неправильное подключение к Redis
**Решение:** Исправлена конфигурация queue в docker-compose

### BUG-005: Ошибка 419 CSRF token mismatch (ИСПРАВЛЕНО v3.6.12)
**Симптом:** 419 Page Expired при отправке форм
**Причина:** Sanctum неправильно настроен
**Решение:** Обновлена конфигурация Sanctum

### BUG-006: Не загружаются Vue компоненты (ИСПРАВЛЕНО v3.6.12)
**Симптом:** Белый экран, ошибки в консоли
**Причина:** Неправильные пути в Vite
**Решение:** Исправлен vite.config.js

### BUG-007: Ошибка подключения к PostgreSQL (ИСПРАВЛЕНО v3.6.13)
**Симптом:** SQLSTATE Connection refused
**Причина:** Контейнер БД не готов
**Решение:** Добавлен healthcheck и depends_on

### BUG-008: Не работает автопостинг (ИСПРАВЛЕНО v3.6.13)
**Симптом:** Посты не публикуются по расписанию
**Причина:** Scheduler не запущен
**Решение:** Добавлен контейнер scheduler

### BUG-009: Команды artisan не найдены (ИСПРАВЛЕНО v3.6.14)
**Симптом:** Command not found при деплое
**Причина:** Команды не зарегистрированы
**Решение:** Созданы artisan команды admin:create, server:create-primary

### BUG-010: Docker volume read-only ошибка (ИСПРАВЛЕНО v3.6.15)
**Симптом:** OCI runtime error - read-only file system
**Причина:** Проблемный volume build-data
**Решение:** Убран build-data, добавлен shared volume app-public

### BUG-011: Nginx не видит public директорию (ИСПРАВЛЕНО v3.6.15)
**Симптом:** realpath() failed - No such file or directory
**Причина:** Nginx не имеет доступа к public
**Решение:** Shared volume app-public между app и nginx

### BUG-012: unzip не установлен на чистом сервере (ИСПРАВЛЕНО v3.6.15)
**Симптом:** unzip: command not found
**Причина:** На чистой Ubuntu нет unzip
**Решение:** Автоматическая установка в quick-install.sh

### BUG-013: Cloudflare ошибка 81058 при повторном импорте (ИСПРАВЛЕНО v3.6.16)
**Симптом:** "An identical record already exists"
**Причина:** DNS запись осталась от предыдущего добавления
**Решение:** Код 81058 обрабатывается как успех, находим существующую запись
**Файл:** `app/Services/DNS/CloudflareService.php`

### BUG-014: "No server IP available" при импорте доменов (ИСПРАВЛЕНО v3.6.16)
**Симптом:** Server error при импорте
**Причина:** Не создан Primary Server
**Решение:** Автоматическое создание в quick-install.sh

### BUG-015: Caddy mkdir Permission denied (ИСПРАВЛЕНО v3.6.17)
**Симптом:** mkdir(): Permission denied при выпуске SSL
**Причина:** Директория /etc/caddy/sites недоступна для записи
**Решение:** Shared volume caddy-sites + chown www-data
**Файлы:** `docker-compose.yml`, `quick-install.sh`

### BUG-016: CaddyManager методы не найдены (ИСПРАВЛЕНО v3.6.17)
**Симптом:** Call to undefined method checkSslStatus()
**Причина:** Контроллер вызывает методы, отсутствующие в CaddyManager
**Решение:** Добавлены методы checkSslStatus() и getSslDetails()
**Файл:** `app/Services/SSL/CaddyManager.php`

### BUG-017: Let's Encrypt отклоняет email (ИСПРАВЛЕНО v3.6.17)
**Симптом:** invalidContact - email has forbidden domain
**Причина:** Использовался example.com или .local домен
**Решение:** Запрос реального email при установке, сохранение в Caddyfile
**Файлы:** `docker/caddy/Caddyfile`, `quick-install.sh`

### BUG-018: Caddy API неправильный формат JSON (ИСПРАВЛЕНО v3.6.17)
**Симптом:** json: unknown field "config"
**Причина:** Неправильный формат запроса к Caddy Admin API
**Решение:** Использование Content-Type: text/caddyfile для reload
**Файл:** `app/Services/SSL/CaddyManager.php`

---

## Текущие известные ограничения

### SSL для DNSPOD требует Caddy
Для доменов на DNSPOD используется Caddy + Let's Encrypt.
Требования:
- Домен должен указывать на IP сервера (A-запись)
- Порты 80 и 443 должны быть открыты
- Реальный email для Let's Encrypt аккаунта

### Cloudflare SSL
Для доменов на Cloudflare SSL выпускается автоматически через Cloudflare.
Требуется активация домена (NS записи должны указывать на Cloudflare).
