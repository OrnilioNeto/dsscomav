#!/bin/bash

# Script de Deploy no cPanel
# Copie e cole tudo no terminal SSH do cPanel, um comando por vez

echo "🚀 Deploy Plataforma DSS no cPanel"
echo "=================================="

# Verificar se está na pasta correta
if [ ! -f "composer.json" ]; then
    echo "❌ Erro: composer.json não encontrado!"
    echo "Execute este script na raiz do projeto"
    exit 1
fi

echo "✅ Pasta correta identificada"
echo ""

# 1. Instalar Composer
echo "📦 Instalando dependências..."
composer install --no-dev --optimize-autoloader
if [ $? -ne 0 ]; then
    echo "❌ Erro ao instalar composer"
    exit 1
fi
echo "✅ Dependências instaladas"
echo ""

# 2. Criar .env
echo "📝 Criando .env..."
if [ ! -f ".env" ]; then
    cp .env.production.example .env
    echo "✅ Arquivo .env criado"
else
    echo "⚠️  .env já existe, pulando..."
fi
echo ""

# 3. Gerar APP_KEY
echo "🔑 Gerando APP_KEY..."
php artisan key:generate
echo "✅ APP_KEY gerado"
echo ""

# 4. Criar pastas necessárias
echo "📁 Criando pastas..."
mkdir -p storage/certificates storage/app/public public/uploads
chmod -R 775 storage bootstrap/cache
echo "✅ Pastas criadas com permissões corretas"
echo ""

# 5. Rodar migrations e seed
echo "🗄️  Executando migrations e seed..."
php artisan migrate --force --seed
if [ $? -ne 0 ]; then
    echo "❌ Erro ao executar migrations"
    exit 1
fi
echo "✅ Banco de dados configurado"
echo ""

# 6. Criar link simbólico para storage
echo "🔗 Criando link para storage..."
php artisan storage:link
echo "✅ Link criado"
echo ""

# 7. Cachear configurações
echo "⚡ Otimizando para produção..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
echo "✅ Cache configurado"
echo ""

echo "=================================="
echo "✅ DEPLOY CONCLUÍDO COM SUCESSO!"
echo "=================================="
echo ""
echo "👉 PRÓXIMOS PASSOS:"
echo "1. Edite o arquivo .env com os dados do MySQL do cPanel"
echo "2. Altere APP_DEBUG para false"
echo "3. Ajuste APP_URL para https://seu-dominio.com"
echo "4. Confirme DocumentRoot apontando para a pasta /public"
echo "5. Acesse https://seu-dominio.com"
echo ""
echo "⚠️  SEGURANÇA:"
echo "- Mude as credenciais padrão:"
echo "  CPF: 00000000000 → ALTERE SENHA"
echo "  CPF: 11111111111 → ALTERE SENHA"
echo ""
echo "📊 Selecione .env e edite os dados o banco:"
echo "   nano .env"
echo ""
