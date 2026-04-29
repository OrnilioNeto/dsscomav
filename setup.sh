#!/bin/bash

# Setup Rápido - Plataforma DSS
# Execute este script para configurar o projeto rapidamente

echo "🚀 Iniciando setup da Plataforma DSS..."
echo ""

# Check se composer está instalado
if ! command -v composer &> /dev/null; then
    echo "❌ Composer não está instalado!"
    echo "Instale em: https://getcomposer.org/download/"
    exit 1
fi

echo "✓ Composer encontrado"
echo ""

# Instalar dependências
echo "📦 Instalando dependências..."
composer install
echo "✓ Dependências instaladas"
echo ""

# Copiar .env
echo "⚙️  Configurando arquivo .env..."
if [ ! -f .env ]; then
    cp .env.example .env
    echo "✓ Arquivo .env criado"
else
    echo "ℹ️  Arquivo .env já existe"
fi
echo ""

# Gerar chave
echo "🔑 Gerando chave da aplicação..."
php artisan key:generate
echo "✓ Chave gerada"
echo ""

# Criar diretorios
echo "📁 Criando diretórios necessários..."
mkdir -p storage/certificates
mkdir -p public/uploads
chmod -R 775 storage bootstrap/cache
echo "✓ Diretórios criados"
echo ""

# Migrations
echo "🗄️  Executando migrations..."
php artisan migrate --seed
echo "✓ Banco de dados configurado"
echo ""

echo "✅ Setup completo!"
echo ""
echo "📝 Próximos passos:"
echo "1. Configure o banco de dados no arquivo .env"
echo "2. Execute: php artisan serve"
echo "3. Acesse: http://localhost:8000"
echo ""
echo "👤 Credenciais de teste:"
echo "   Super Admin - CPF: 00000000000 | Senha: admin123"
echo "   Admin - CPF: 11111111111 | Senha: admin123"
echo "   Motorista - CPF: 22222222222 | Senha: senha123"
echo ""
