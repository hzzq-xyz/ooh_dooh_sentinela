#!/bin/bash
export HOME=/home/u697281162
export COMPOSER_HOME=$HOME/.composer
cd /home/u697281162/domains/opecs.xyz/public_html/0000NELA0000

set -e
echo "🧹 LIMPANDO TUDO E RECOMEÇANDO..."

# 1. Traz o que está no GitHub e apaga o que for diferente no servidor
git fetch origin main
git reset --hard origin/main
git clean -fd # Isso apaga arquivos que não deveriam estar lá

# 2. Instala do zero
composer install --no-dev --optimize-autoloader

# 3. MODO TRATOR NO BANCO: Apaga as tabelas e cria de novo
php artisan migrate:fresh --force

# 4. Limpa geral
php artisan optimize:clear
php artisan filament:upgrade

echo "✅ SERVIDOR LIMPO E ATUALIZADO!"