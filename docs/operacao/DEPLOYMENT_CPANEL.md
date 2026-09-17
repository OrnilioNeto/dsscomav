# 🚀 Deploy no cPanel - Checklist Pronto para Produção

## ✅ Pré-Deploy (Faça AGORA, antes de subir)

### 1. Validar composer.json
```bash
composer validate
```

### 2. Instalar dependências localmente (teste)
```bash
composer install --no-dev --optimize-autoloader
```
Isso garante que tudo funciona antes de subir.

### 3. Limpar caches locais (antes do deploy)
```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### 4. Gerar .env local ou copiar template
```bash
cp .env.example .env
php artisan key:generate
```

### 5. Verificar .gitignore
Certifique-se de que isso está ali (padrão do Laravel):
```
/vendor/
/node_modules/
/bootstrap/cache/
.env
.env.*.php
.DS_Store
```

---

## 🖥️ No cPanel (SSH)

### Passo 1: Entrar na pasta do domínio
```bash
cd ~/public_html
# ou se tiver subdomínio:
cd ~/public_html/subdominio
```

### Passo 2: Clonar repositório
```bash
git clone https://github.com/SEU_USUARIO/SEU_REPO.git .
```
Ou, se já existe pasta:
```bash
git clone https://github.com/SEU_USUARIO/SEU_REPO.git plataforma_dss
cd plataforma_dss
```

### Passo 3: Instalar Composer no servidor
```bash
composer install --no-dev --optimize-autoloader
```

### Passo 4: Criar e configurar .env
```bash
cp .env.example .env
php artisan key:generate
```

Edite o .env com dados do cPanel:
```
APP_ENV=production
APP_DEBUG=false
APP_URL=https://seu-dominio.com

# BANCO DE DADOS - Pegue do cPanel > MySQL Databases
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=seu_usuario_banco
DB_USERNAME=seu_usuario_banco
DB_PASSWORD=sua_senha_segura

# Cache e Session - em cPanel compartilhado use file
CACHE_DRIVER=file
SESSION_DRIVER=file
QUEUE_DRIVER=sync
```

### Passo 5: Rodar Migrações e Permissões
```bash
# Gerar chave do app (se não foi feito)
php artisan key:generate

# Rodar migrations e seed (PRIMEIRA VEZ APENAS)
php artisan migrate --force --seed

# Criar link simbólico para storage (faz storage/app/public acessível)
php artisan storage:link

# Permissões corretas
chmod -R 775 storage bootstrap/cache

# Se a pasta storage/certificates não existir, criar
mkdir -p storage/certificates
chmod 775 storage/certificates

# Se a pasta public/uploads não existir, criar
mkdir -p public/uploads
chmod 755 public/uploads
```

### Passo 6: Cachear config (melhora performance)
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## 🔧 Configurar DocumentRoot no cPanel

**CRÍTICO**: O DocumentRoot DEVE apontar para a pasta `/public` do Laravel.

### Opção A: Via cPanel (Recomendado)
1. Log in no cPanel
2. Vá para **Addon Domains** ou **Parked Domains**
3. Edite o domínio e aponte `Document Root` para:
   ```
   /home/seu_usuario/public_html/plataforma_dss/public
   ```
   ou se clonou direto em public_html:
   ```
   /home/seu_usuario/public_html/public
   ```
4. Salve.
5. Teste acessando https://seu-dominio.com

### Opção B: Se NÃO conseguir mudar DocumentRoot
1. Mude todos os arquivos da pasta `public` para `public_html`
2. Edite o arquivo `public_html/index.php` e altere os paths:
```php
require __DIR__.'/../bootstrap/app.php';
```
Para:
```php
require __DIR__.'/bootstrap/app.php';
```
E no mesmo arquivo, localize:
```php
'base_path' => __DIR__.'/../',
```
E mude para:
```php
'base_path' => __DIR__.'/',
```

---

## 🔐 Segurança Pré-Produção

### 1. Trocar credenciais padrão
- CPF Super Admin: `00000000000` → TROCAR SENHA IMEDIATAMENTE
- CPF Admin: `11111111111` → TROCAR SENHA IMEDIATAMENTE

Acesse a aplicação e altere via painel administrativo.

### 2. Ativar SSL/HTTPS
- No cPanel, vá para **SSL/TLS**
- Instale certificado (AutoSSL é gratuito)
- Confirme que APP_URL no .env aponta para `https://`

### 3. Desabilitar Debug em Produção
No `.env`, confirme:
```
APP_DEBUG=false
```

### 4. Logs
Os logs vão para `storage/logs/`. cPanel geralmente limpa logs antigos automaticamente.

---

## 📝 Atualizar depois (Deploy Futuro)

Sempre que tiver mudanças no GitHub:

```bash
cd ~/public_html/plataforma_dss  # (ou seu caminho)

# Trazer código novo
git pull origin main

# Instalar/atualizar dependências
composer install --no-dev --optimize-autoloader

# Se houver migrations novas
php artisan migrate --force

# Cachear tudo novamente
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## 🆘 Troubleshooting cPanel

| Erro | Solução |
|------|---------|
| **"Class not found"** | `composer dump-autoload` |
| **"Permission denied" em storage/** | `chmod -R 775 storage bootstrap/cache` |
| **Página em branco** | `php artisan cache:clear` e verifique logs em `storage/logs/` |
| **"SQLSTATE: Connection refused"** | Confirme DB_HOST, DB_USERNAME, DB_PASSWORD no .env |
| **Certificado SSL erro** | Acesse aplicação com https, cPanel pode redirecionar de http→https |
| **Imagens não aparecem** | Rode `php artisan storage:link` e confirme que `public/storage` aponta para `storage/app/public` |

---

## ✨ Pronto?

Depois de seguir todos os passos, acesse:
```
https://seu-dominio.com
```

Se ligar, faça login com:
- CPF: `00000000000`
- Senha: `admin123`

Depois **MUDE ESSA SENHA IMEDIATAMENTE**.

---

**Nota**: Este projeto usa PostgreSQL como padrão mas cPanel geralmente oferece MySQL. O `.env.example` já está configurado para MySQL - apenas ajuste os dados do seu cPanel.
