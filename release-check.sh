#!/bin/bash
#
# SEO Platform - Проверка перед релизом
# Запускать перед созданием каждого архива!
#

# Цвета
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

ERRORS=0

echo "============================================"
echo "  SEO Platform - Проверка перед релизом"
echo "============================================"
echo ""

# 1. DNSPod URL
echo "=== 1. DNSPod URL ==="
DNSPOD_URL=$(grep "API_BASE" app/Services/DNS/DnspodService.php 2>/dev/null | head -1)
if [[ "$DNSPOD_URL" == *"api.dnspod.com"* ]]; then
    echo -e "${GREEN}✓ OK:${NC} $DNSPOD_URL"
else
    echo -e "${RED}✗ ОШИБКА:${NC} Должен быть api.dnspod.com!"
    echo "  Найдено: $DNSPOD_URL"
    ERRORS=$((ERRORS + 1))
fi
echo ""

# 2. ASSET_URL
echo "=== 2. ASSET_URL в .env.example ==="
if grep -q "^ASSET_URL=/" .env.example 2>/dev/null; then
    echo -e "${GREEN}✓ OK:${NC} ASSET_URL=/ присутствует"
else
    echo -e "${RED}✗ ОШИБКА:${NC} ASSET_URL отсутствует в .env.example!"
    ERRORS=$((ERRORS + 1))
fi
echo ""

# 3. SiteController - filled() вместо has()
echo "=== 3. SiteController - filled() вместо has() ==="
FILLED_COUNT=$(grep -c "filled(" app/Http/Controllers/Api/SiteController.php 2>/dev/null || echo "0")
HAS_COUNT=$(grep -c "->has(" app/Http/Controllers/Api/SiteController.php 2>/dev/null || echo "0")

if [ "$FILLED_COUNT" -gt 0 ] && [ "$HAS_COUNT" -eq 0 ]; then
    echo -e "${GREEN}✓ OK:${NC} Используется filled() ($FILLED_COUNT раз), has() не найден"
else
    echo -e "${YELLOW}⚠ ВНИМАНИЕ:${NC} filled()=$FILLED_COUNT, has()=$HAS_COUNT"
    [ "$HAS_COUNT" -gt 0 ] && ERRORS=$((ERRORS + 1))
fi
echo ""

# 4. AiSetting rate limit
echo "=== 4. AiSetting rate limit (timezone fix) ==="
if grep -q "now()->timestamp" app/Models/AiSetting.php 2>/dev/null; then
    echo -e "${GREEN}✓ OK:${NC} Используется timestamp сравнение"
else
    echo -e "${YELLOW}⚠ ВНИМАНИЕ:${NC} Проверьте метод canMakeRequest()"
fi
echo ""

# 5. Domain nameservers cast
echo "=== 5. Domain model - nameservers cast ==="
if grep -A15 "protected \$casts" app/Models/Domain.php 2>/dev/null | grep -q "nameservers.*array"; then
    echo -e "${GREEN}✓ OK:${NC} nameservers => array присутствует в casts"
else
    echo -e "${RED}✗ ОШИБКА:${NC} nameservers отсутствует в casts!"
    ERRORS=$((ERRORS + 1))
fi
echo ""

# 6. Vue компоненты - кнопки
echo "=== 6. Vue компоненты - кнопки ==="
DNS_PENCIL=$(grep -c "PencilIcon" resources/js/pages/DnsAccounts.vue 2>/dev/null || echo "0")
DOMAINS_SSL=$(grep -c "SSL\|checkNs\|setupSsl" resources/js/pages/Domains.vue 2>/dev/null || echo "0")

if [ "$DNS_PENCIL" -gt 0 ]; then
    echo -e "${GREEN}✓ OK:${NC} DnsAccounts.vue: PencilIcon найден ($DNS_PENCIL раз)"
else
    echo -e "${RED}✗ ОШИБКА:${NC} DnsAccounts.vue: PencilIcon не найден!"
    ERRORS=$((ERRORS + 1))
fi

if [ "$DOMAINS_SSL" -gt 0 ]; then
    echo -e "${GREEN}✓ OK:${NC} Domains.vue: SSL/NS кнопки найдены ($DOMAINS_SSL раз)"
else
    echo -e "${YELLOW}⚠ ВНИМАНИЕ:${NC} Domains.vue: SSL/NS кнопки не найдены"
fi
echo ""

# 7. DNSPOD login_token порядок
echo "=== 7. DNSPOD login_token порядок ==="
if grep -A5 "loginToken" app/Services/DNS/DnspodService.php 2>/dev/null | grep -q "api_secret.*api_key"; then
    echo -e "${GREEN}✓ OK:${NC} Порядок: api_secret,api_key (ID,Token)"
else
    echo -e "${YELLOW}⚠ ВНИМАНИЕ:${NC} Проверьте порядок login_token"
fi
echo ""

# 8. .gitignore
echo "=== 8. .env не в git ==="
if [ -f ".gitignore" ] && grep -q "^\.env$" .gitignore 2>/dev/null; then
    echo -e "${GREEN}✓ OK:${NC} .env в .gitignore"
else
    echo -e "${RED}✗ ОШИБКА:${NC} .env не добавлен в .gitignore!"
    ERRORS=$((ERRORS + 1))
fi
echo ""

# 9. deploy.sh версия
echo "=== 9. deploy.sh версия ==="
if [ -f "deploy.sh" ]; then
    if grep -q "ENV_KEY_FILE" deploy.sh 2>/dev/null; then
        echo -e "${GREEN}✓ OK:${NC} deploy.sh v2.0 с защитой APP_KEY"
    else
        echo -e "${YELLOW}⚠ ВНИМАНИЕ:${NC} deploy.sh старой версии (без защиты APP_KEY)"
    fi
else
    echo -e "${RED}✗ ОШИБКА:${NC} deploy.sh не найден!"
    ERRORS=$((ERRORS + 1))
fi
echo ""

# Итоги
echo "============================================"
echo "  ИТОГИ ПРОВЕРКИ"
echo "============================================"
if [ $ERRORS -eq 0 ]; then
    echo -e "${GREEN}✓ Все проверки пройдены успешно!${NC}"
    echo "  Можно создавать релиз."
    echo ""
    exit 0
else
    echo -e "${RED}✗ Найдено ошибок: $ERRORS${NC}"
    echo "  Исправьте ошибки перед релизом!"
    echo ""
    exit 1
fi
