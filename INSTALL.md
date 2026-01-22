# Установка SEO Platform v3.6.18

## Требования

### Минимальные
- Ubuntu 22.04 / 24.04
- 2 CPU, 2 GB RAM
- 20 GB SSD
- Открытые порты: 80, 443, 8080

### Рекомендуемые
- 4 CPU, 4 GB RAM
- 40 GB SSD

## Быстрая установка

### Шаг 1: Предустановка (на чистом сервере)
```bash
apt update && apt install -y unzip curl wget
```

### Шаг 2: Загрузка и распаковка
```bash
# Загрузите архив на сервер через SCP/SFTP, затем:
cd /root
unzip seo-platform-v3.6.18.zip
cd seo-platform
```

### Шаг 3: Установка
```bash
chmod +x quick-install.sh
./quick-install.sh
```

### Одной командой (если unzip уже есть):
```bash
cd /root && unzip seo-platform-v3.6.18.zip && cd seo-platform && chmod +x quick-install.sh && ./quick-install.sh
```

## Что делает установщик

1. Запрашивает email для SSL сертификатов (Let's Encrypt)
2. Устанавливает Docker (если нет)
3. **Настраивает Docker на IPv4** (решает проблему IPv6 unreachable)
4. Настраивает базу данных PostgreSQL
5. Настраивает Caddy для SSL
6. Создаёт Primary Server
7. Создаёт администратора

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

# Логи конкретного сервиса
docker compose logs -f app
docker compose logs -f caddy

# Перезапуск
docker compose restart

# Остановка
docker compose down

# Обновление
git pull && docker compose up -d --build
```

## Решение проблем

### unzip: command not found
```bash
apt update && apt install -y unzip
```

### Docker IPv6 network unreachable
Ошибка: `dial tcp [2600:...]:443: connect: network is unreachable`

```bash
mkdir -p /etc/docker
echo '{"ipv6":false,"ip6tables":false,"dns":["8.8.8.8","8.8.4.4"]}' > /etc/docker/daemon.json
systemctl restart docker
```
Затем повторите установку.

### SSL не выпускается
1. Проверьте что домен указывает на IP сервера
2. Проверьте что порты 80/443 открыты
3. Проверьте логи: `docker compose logs caddy`

### Ошибка Permission denied (Caddy)
```bash
docker compose exec app chown -R www-data:www-data /etc/caddy/sites
```

### Белая страница / Server Error 500
```bash
# Проверить APP_KEY
grep APP_KEY .env

# Исправить если нужно
./deploy.sh fix-key
```

## Резервное копирование

Важные данные хранятся в:
- `/root/seo-backups/.app_key` - ключ шифрования
- `/root/seo-backups/.db_password` - пароль БД
- `docker volume` - данные PostgreSQL и Redis

### Создание бэкапа
```bash
# Бэкап базы данных
docker compose exec db pg_dump -U seo_user seo_platform > backup.sql

# Бэкап всего
tar -czvf seo-backup-$(date +%Y%m%d).tar.gz \
  /root/seo-backups \
  /root/seo-platform/.env \
  backup.sql
```
