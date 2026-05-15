#!/usr/bin/env bash
#
# FidelizaPro - bootstrap de instalação para CloudPanel/Linux.
# Roda UMA VEZ antes de abrir o instalador web em /install.
#
# Uso:
#   chmod +x install.sh && ./install.sh

set -euo pipefail

cd "$(dirname "$0")"

echo "==> FidelizaPro - bootstrap"

# 1. .env
if [ ! -f .env ]; then
    echo "  - copiando .env.example -> .env"
    cp .env.example .env
else
    echo "  - .env já existe (mantido)"
fi

# 2. Permissões
echo "  - chmod 775 em storage/ e bootstrap/cache/"
chmod -R 775 storage bootstrap/cache 2>/dev/null || true

# 3. Composer (se vendor/ não existir)
if [ ! -f vendor/autoload.php ]; then
    echo "  - rodando composer install --no-dev"
    if command -v composer >/dev/null 2>&1; then
        composer install --no-dev --optimize-autoloader --no-interaction
    elif command -v php8.3 >/dev/null 2>&1 && [ -f /usr/bin/composer ]; then
        php8.3 /usr/bin/composer install --no-dev --optimize-autoloader --no-interaction
    else
        echo "ERRO: composer não encontrado no PATH. Instale ou rode manualmente:"
        echo "  composer install --no-dev --optimize-autoloader"
        exit 1
    fi
else
    echo "  - vendor/ já existe (composer install pulado)"
fi

# 4. APP_KEY (somente se vazio)
if ! grep -qE '^APP_KEY=base64:' .env; then
    echo "  - gerando APP_KEY"
    php artisan key:generate --force
else
    echo "  - APP_KEY já definida"
fi

# 5. Cron do scheduler (idempotente — só adiciona se ainda não existe)
PROJECT_DIR="$(pwd)"
CRON_LINE="* * * * * cd ${PROJECT_DIR} && php artisan schedule:run >> /dev/null 2>&1"

if command -v crontab >/dev/null 2>&1; then
    if crontab -l 2>/dev/null | grep -Fq "${PROJECT_DIR} && php artisan schedule:run"; then
        echo "  - cron do schedule:run já existe (mantido)"
    else
        echo "  - adicionando cron do schedule:run ao crontab"
        ( crontab -l 2>/dev/null; echo "${CRON_LINE}" ) | crontab - && \
            echo "    OK — scheduler vai rodar a cada minuto" || \
            echo "    AVISO: falha ao escrever crontab — adicione manualmente:"
        echo "    ${CRON_LINE}"
    fi
else
    echo "  - AVISO: crontab não disponível neste sistema"
    echo "    Adicione manualmente via painel (CloudPanel/CyberPanel → Cron Jobs):"
    echo "    ${CRON_LINE}"
fi

echo ""
echo "==> Pronto!"
echo ""
echo "Agora abra no navegador:"
echo ""
echo "    https://SEU_DOMINIO/install"
echo ""
echo "Para reabrir o instalador depois (caso queira refazer):"
echo "    rm storage/installed.lock"
