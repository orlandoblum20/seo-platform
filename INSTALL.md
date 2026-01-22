# Установка SEO Platform v3.6.17

## Требования

### Минимальные
- Ubuntu 22.04 / 24.04
- 2 CPU, 2 GB RAM
- 20 GB SSD
- Открытые порты: 80, 443, 8080

### Рекомендуемые
- 4 CPU, 4 GB RAM
- 40 GB SSD

## Быстрая установка (1 команда)
```bash
wget https://github.com/orlandoblum20/seo-platform/archive/refs/heads/main.zip && \
unzip main.zip && cd seo-platform-main && chmod +x quick-install.sh && ./quick-install.sh
```

Или из архива:
```bash
unzip seo-platform-v3.6.17.zip && cd seo-platform && chmod +x quick-install.sh && ./quick-install.sh
```

## Что делает установщик

1. Запрашивает email для SSL сертификатов (Let's Encrypt)
2. Устанавливает Docker (если нет)
3. Настраивает базу данных PostgreSQL
4. Настраивает Caddy для SSL
5. Создаёт Primary Server
6. Создаёт администратора

## После установки

- **Админ-панель:** http://YOUR_IP:8080
- **SSL домены:** https://your-domain.com (порты 80/443)

## Протестированный функционал

✅ AI Провайдеры (OpenAI, Anthropic, DeepSeek)
✅ DNS Аккаунты (Cloudflare, DNSPOD)  
✅ Импорт доменов
✅ Проверка DNS статуса
✅ SSL Cloudflare (Universal SSL)
✅ SSL Let's Encrypt (через Caddy)

## Команды управления
```bash
# Логи
docker compose logs -f

# Перезапуск
docker compose restart

# Остановка
docker compose down

# Обновление
git pull && docker compose up -d --build
```

## Решение проблем

### SSL не выпускается
1. Проверьте что домен указывает на IP сервера
2. Проверьте что порты 80/443 открыты
3. Проверьте логи: `docker compose logs caddy`

### Ошибка Permission denied
```bash
docker compose exec app chown -R www-data:www-data /etc/caddy/sites
```
