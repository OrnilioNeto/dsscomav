# 🔧 Pacotes e Dependências - Plataforma DSS

## 📦 Dependências Laravel

### Core Laravel
```
laravel/framework: ^10.0  - Framework principal
laravel/tinker: ^2.8      - Console interativo
laravel/sanctum: ^3.0     - Autenticação API
```

### Extras para o Projeto
```
tcpdf/tcpdf: ^6.4                      - Geração de PDF para certificados
simplesoftwareio/simple-qrcode: ^4.0   - Geração de QR Code
```

---

## 🚀 Como Instalar

### Todos os Pacotes Automaticamente

```bash
composer install
```

### Instalar Pacotes Específicos

#### TCPDF (Certificados em PDF)
```bash
composer require tcpdf/tcpdf
```

#### QR Code Generator
```bash
composer require simplesoftwareio/simple-qrcode
```

---

## 📋 Aplicações do PHP

### [TCPDF](http://www.tcpdf.org)
**Uso:** Geração de certificados em PDF
**Versão:** 6.4.x

**Exemplo de Uso:**
```php
use TCPDF;

$pdf = new TCPDF();
$pdf->AddPage();
$pdf->SetFont('helvetica', 'B', 28);
$pdf->Cell(0, 20, 'CERTIFICADO', 0, 1, 'C');
$pdf->Output('certificado.pdf', 'D');
```

**Documentação:** http://www.tcpdf.org

---

### [SimpleSoftwareIO QR Code](https://www.simplesoftware.io/docs/simple-qrcode)
**Uso:** Geração de código QR para certificados
**Versão:** 4.0.x

**Exemplo de Uso:**
```php
use SimpleSoftwareIO\QrCode\Facades\QrCode;

QrCode::format('png')
    ->size(300)
    ->generate('Dados para QR Code', storage_path('qr_code.png'));
```

**Documentação:** https://www.simplesoftware.io/docs/simple-qrcode

---

## 🔌 Pacotes de Desenvolvimento

### PHPUnit
```
phpunit/phpunit: ^10.0
```
**Uso:** Testes automatizados

### Laravel Pint
```
laravel/pint: ^1.0
```
**Uso:** Formatador de código PHP

### Laravel Sail
```
laravel/sail: ^1.0
```
**Uso:** Ambiente Docker (opcional)

---

## 🛠️ Passos para Instalação Completa

### 1. Clone o repositório
```bash
git clone <seu-repositorio>
cd plataforma_dss
```

### 2. Instale as dependências
```bash
composer install
```

### 3. Configure o ambiente
```bash
cp .env.example .env
php artisan key:generate
```

### 4. Banco de dados
```bash
# Atualize as credenciais em .env
# DB_CONNECTION=pgsql
# DB_HOST=127.0.0.1
# DB_DATABASE=plataforma_dss

php artisan migrate --seed
```

### 5. Servidor de desenvolvimento
```bash
php artisan serve
```

---

## 📋 Lista de Dependências do Projeto

| Pacote | Versão | Uso |
|--------|--------|-----|
| Laravel | ^10.0 | Framework |
| PHP | ^8.1 | Backend |
| TCPDF | ^6.4 | Certificados PDF |
| SimpleQRCode | ^4.0 | QR Codes |
| Tailwind CSS | CDN | Stylesheet |
| Font Awesome | CDN | Ícones |

---

## 🌐 Dependências de CDN (Externas)

```html
<!-- Tailwind CSS -->
<script src="https://cdn.tailwindcss.com"></script>

<!-- Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<!-- jQuery (Opcional) -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
```

---

## ⚠️ Versões Compatíveis

| Componente | Versão Mínima | Testada Em |
|------------|---------------|-----------|
| PHP | 8.1 | 8.2, 8.3 |
| Laravel | 10.0 | 10.48 |
| PostgreSQL | 12.0 | 14.0, 15.0 |
| MySQL | 5.7 | 8.0 |
| SQLite | 3.0 | 3.42 |
| Node.js | 14.0 | 18.0+ (opcional) |

---

## 🔒 Segurança das Dependências

Mantenha tudo atualizado:

```bash
# Verificar dependências desatualizadas
composer outdated

# Atualizar tudo (com cuidado)
composer update

# Verificar vulnerabilidades
composer audit
```

---

## 🐳 Docker (Opcional)

Se quiser usar Docker:

```bash
# Instalar Laravel Sail
composer require laravel/sail --dev

# Setup com Docker
php artisan sail:install

# Rodar com Docker
./vendor/bin/sail up
```

---

## 📞 Suporte de Dependências

| Pacote | Link |
|--------|------|
| Laravel | https://laravel.com/docs |
| TCPDF | http://www.tcpdf.org |
| SimpleQRCode | https://www.simplesoftware.io/docs/simple-qrcode |
| Composer | https://getcomposer.org |

---

## ✅ Verificar Instalação

```bash
# listar todas as dependências
composer show

# Verificar PHP
php -v

# Verificar Laravel
php artisan tinker

# Sair de tinker
exit
```

Se tudo funcionar, você está pronto para começar! 🚀
