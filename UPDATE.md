# Обновление SEO Platform

## ⚠️ ВАЖНО: Правила безопасного обновления

### Проблема с .env файлом
При распаковке архива `.env` может быть затёрт! Это приводит к:
- **Белой странице** (потеря ASSET_URL)
- **Server Error 500** (потеря APP_KEY)

### ✅ Правильный способ обновления:

```bash
# 1. ОБЯЗАТЕЛЬНО сохраните текущий .env
cp /root/seo-platform/.env /root/.env.backup

# 2. Распакуйте архив ИСКЛЮЧАЯ .env
cd /root
rm -rf /root/seo-platform/.env  # удалить если это директория
unzip -o seo-platform-vX.X.X.zip -x "seo-platform/.env*"

# 3. Если .env был случайно затёрт - восстановите
[ -f /root/seo-platform/.env ] || cp /root/.env.backup /root/seo-platform/.env
```

---

## Обновление до v3.6.10

### Что нового:
- **DeepSeek** провайдер (R1 reasoner, V3 chat)
- Обновлённые модели **Anthropic** (Claude Opus 4.5, Sonnet 4.5)
- Обновлённые модели **OpenAI** (o1, o1-mini, o3-mini)
- Исправлены ошибки с фильтрами в разделе Сайты

### Пошаговое обновление:

```bash
# 1. Бэкап .env (ОБЯЗАТЕЛЬНО!)
cp /root/seo-platform/.env /root/.env.backup

# 2. Загрузите и распакуйте архив
cd /root
rm -rf /root/seo-platform/.env  # если это директория
# Загрузить seo-platform-v3.6.10.zip через SFTP
unzip -o seo-platform-v3.6.10.zip -x "seo-platform/.env*"

# 3. Восстановить .env если потерян
[ -f /root/seo-platform/.env ] || cp /root/.env.backup /root/seo-platform/.env

# 4. Обновить backend файлы
cd /root/seo-platform
docker compose cp app/Models/AiSetting.php app:/var/www/app/Models/
docker compose cp app/Services/AI/DeepSeekService.php app:/var/www/app/Services/AI/
docker compose cp app/Services/AI/AiManager.php app:/var/www/app/Services/AI/
docker compose cp app/Http/Controllers/Api/SiteController.php app:/var/www/app/Http/Controllers/Api/

# 5. Обновить frontend
cat resources/js/pages/SettingsAi.vue | docker compose exec -T app sh -c "cat > /var/www/resources/js/pages/SettingsAi.vue"
docker compose exec app npm run build

# 6. Очистить кеш
docker compose exec app php artisan optimize:clear

# 7. Перезапустить
docker compose restart app nginx
```

---

## 🔧 Решение проблем

### Белая страница
```bash
# Проверить ASSET_URL
docker compose exec app cat /var/www/.env | grep ASSET_URL
# Если нет - добавить:
docker compose exec app sh -c "echo 'ASSET_URL=/' >> /var/www/.env"
docker compose exec app php artisan config:clear
docker compose restart app nginx
```

### Server Error 500 (APP_KEY)
```bash
# Проверить APP_KEY
docker compose exec app cat /var/www/.env | grep APP_KEY
# Если пустой - сгенерировать:
docker compose exec app php artisan key:generate --force
docker compose restart app
```

### Нет storage директорий
```bash
mkdir -p /root/seo-platform/storage/{logs,framework/{cache/data,sessions,views,testing},app/{public,sites}}
chmod -R 777 /root/seo-platform/storage
docker compose restart app
```

---

## История версий

### v3.6.10 (2026-01-21)
- DeepSeek провайдер
- Claude 4.5 Opus/Sonnet
- OpenAI o1/o3-mini
- Исправлены фильтры в Сайтах

### v3.6.9 (2026-01-21)
- Полная страница AI провайдеров
- Исправлен SiteController

### v3.6.8 (2026-01-20)
- Исправлен Domain casts
- Исправлен SiteController search
