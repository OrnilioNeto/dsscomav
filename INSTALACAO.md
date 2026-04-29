# Guia de Instalação e Deploy - Plataforma DSS

## 🚀 Instalação Local

### Pré-requisitos
- PHP 8.1+
- Composer
- PostgreSQL (recomendado) ou MySQL
- Node.js e npm (opcional, para assets)

### Passos

1. **Clone o repositório**
```bash
git clone <seu-repositorio>
cd plataforma_dss
```

2. **Instale as dependências PHP**
```bash
composer install
```

3. **Configure o arquivo .env**
```bash
cp .env.example .env
php artisan key:generate
```

4. **Configure o banco de dados no .env**
```
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=plataforma_dss
DB_USERNAME=seu_usuario
DB_PASSWORD=sua_senha
```

5. **Execute as migrations com dados de exemplo**
```bash
php artisan migrate --seed
```

6. **Inicie o servidor de desenvolvimento**
```bash
php artisan serve
```

Acesse: http://localhost:8000

## 📱 Deploy no ValueHost

### 1. Preparação

1. Crie um banco de dados PostgreSQL no painel ValueHost
2. Obtenha as credenciais do banco

### 2. Upload via FTP

1. Conecte via FTP ao seu servidor
2. Faça upload de todos os arquivos EXCETO:
   - `node_modules/`
   - `vendor/` (será instalado no servidor)
   - `.env` (será criado no servidor)
   - `.git/`

### 3. Configuração no Servidor

1. **SSH no servidor** (se disponível)

```bash
# Navegue até a pasta do projeto
cd public_html/plataforma_dss

# Instale as dependências
composer install --optimize-autoloader --no-dev

# Configure o arquivo .env
cp .env.example .env

# Gere a chave da aplicação
php artisan key:generate

# Configure o banco de dados no .env

# Execute as migrations
php artisan migrate --seed

# Defina permissões
chmod -R 775 storage
chmod -R 775 bootstrap/cache
```

### 4. Configurar DocumentRoot

No painel ValueHost:
- Aponte o DocumentRoot para: `/public_html/plataforma_dss/public`

### 5. Configurar HTTPS

- Ative certificado SSL (geralmente gratuito no ValueHost)
- Atualize `APP_URL` no .env: `APP_URL=https://seu-dominio.com`

## 🗄️ Banco de Dados

### Estrutura de Tabelas

```
✓ roles (perfis de usuário)
✓ users (usuários do sistema)
✓ trainings (treinamentos/DSS)
✓ user_progress (progresso dos usuários)
✓ certificates (certificados emitidos)
```

### Seed de Dados

Ao executar `php artisan migrate --seed`, são criados:
- Super Admin (CPF: 00000000000)
- Admin (CPF: 11111111111)
- Usuários de teste (motorista, funcionário, terceirizado)
- 5 treinamentos de exemplo

## 🔐 Credenciais Padrão

**Super Administrador**
- CPF: 00000000000
- Senha: admin123

**Administrador**
- CPF: 11111111111
- Senha: admin123

⚠️ **ALTERE ESTAS SENHAS EM PRODUÇÃO!**

## 📊 Funcionalidades Principais

### Para Usuários
- ✓ Login com CPF
- ✓ Assistir treinamentos específicos do seu tipo
- ✓ Acompanhar progresso
- ✓ Baixar certificados
- ✓ Validar certificados

### Para Administradores
- ✓ CRUD completo de usuários
- ✓ CRUD completo de treinamentos
- ✓ Visualização de relatórios
- ✓ Emissão de certificados
- ✓ Gestão de permissões

### Para Super Admin
- ✓ Tudo que o Admin pode fazer
- ✓ Gerenciar outros administradores
- ✓ Relatórios completos do sistema
- ✓ Configurações do sistema

## 🔄 Fluxo de Uso

1. **Administrador** cadastra novo treinamento
   - Define tipo (DSS ou Treinamento)
   - Define para quem é (motorista, funcionário, terceirizado)
   - Define obrigatoriedade

2. **Usuários** acessam seu dashboard
   - Veem treinamentos disponíveis
   - Assistem aos vídeos
   - Progresso é registrado automaticamente

3. **Ao completar** (80%+ assistido)
   - Certificado é gerado automaticamente
   - Usuário pode baixar em PDF
   - Pode validar o certificado

## 🎨 Customizações

### Cores e Tema
Edite `resources/views/layout.blade.php` para alterar cores Tailwind

### Adicionar Novo Treinamento

```bash
php artisan tinker
```

```php
App\Models\Training::create([
    'titulo' => 'Novo Treinamento',
    'descricao' => 'Descrição...',
    'tipo' => 'treinamento',
    'tipo_usuario_permitido' => ['motorista'],
    'url_video' => 'https://youtube.com/...',
    'tipo_video' => 'youtube',
    'carga_horaria' => 30,
    'status' => 'ativo',
]);
```

## 🐛 Troubleshooting

### Erro: "Class not found"
```bash
composer install
composer dump-autoload
```

### Erro: "Connection refused"
- Verifique credenciais do banco de dados
- Verifique se o PostgreSQL está rodando

### Erro: "Permission denied" em storage/
```bash
chmod -R 775 storage
chmod -R 775 bootstrap/cache
```

## 📞 Suporte

Para dúvidas ou problemas, consulte a documentação do Laravel em https://laravel.com/docs

## 📄 Licença

MIT License - Veja LICENSE para detalhes
