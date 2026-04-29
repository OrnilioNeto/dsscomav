@echo off
REM Setup Rápido - Plataforma DSS (Windows)
REM Execute este arquivo para configurar o projeto rapidamente

echo.
echo Iniciando setup da Plataforma DSS...
echo.

REM Verificar se composer está instalado
where composer >nul 2>nul
if %ERRORLEVEL% NEQ 0 (
    echo Composer nao esta instalado!
    echo Instale em: https://getcomposer.org/download/
    pause
    exit /b 1
)

echo Composer encontrado
echo.

REM Instalar dependências
echo Instalando dependencias...
call composer install
echo Dependencias instaladas
echo.

REM Copiar .env
echo Configurando arquivo .env...
if not exist .env (
    copy .env.example .env
    echo Arquivo .env criado
) else (
    echo Arquivo .env ja existe
)
echo.

REM Gerar chave
echo Gerando chave da aplicacao...
php artisan key:generate
echo Chave gerada
echo.

REM Criar diretórios
echo Criando diretorios necessarios...
if not exist storage\certificates mkdir storage\certificates
if not exist public\uploads mkdir public\uploads
echo Diretorios criados
echo.

REM Migrations
echo Executando migrations...
php artisan migrate --seed
echo Banco de dados configurado
echo.

echo.
echo Setup completo!
echo.
echo Proximos passos:
echo 1. Configure o banco de dados no arquivo .env
echo 2. Execute: php artisan serve
echo 3. Acesse: http://localhost:8000
echo.
echo Credenciais de teste:
echo    Super Admin - CPF: 00000000000 / Senha: admin123
echo    Admin - CPF: 11111111111 / Senha: admin123
echo    Motorista - CPF: 22222222222 / Senha: senha123
echo.
pause
