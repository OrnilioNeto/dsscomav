# 🎓 Plataforma DSS - Treinamento Corporativo

Sistema web completo para gestão de treinamentos e DSS (Diálogo Semanal de Segurança) desenvolvido em Laravel com Tailwind CSS.

## 📋 Características

- ✅ Autenticação por CPF
- ✅ Sistema de Roles (Super Admin, Admin, Motorista, Funcionário, Terceirizado)
- ✅ Cadastro de treinamentos por tipo de usuário
- ✅ Reprodutor de vídeos (YouTube, Vimeo, Upload)
- ✅ Acompanhamento de progresso
- ✅ Geração automática de certificados com QR Code
- ✅ Validação de certificados
- ✅ Dashboard administrativo
- ✅ Relatórios e métricas
- ✅ Interface responsiva com Tailwind CSS

## 🛠️ Tecnologias

- **Backend**: Laravel 10
- **Frontend**: Tailwind CSS
- **Banco de Dados**: PostgreSQL
- **PHP**: 8.1+

## 📦 Instalação

1. Clone o repositório
```bash
git clone <seu-repositorio>
cd plataforma_dss
```

2. Instale as dependências
```bash
composer install
```

3. Configure o arquivo `.env`
```bash
cp .env.example .env
php artisan key:generate
```

4. Execute as migrations
```bash
php artisan migrate --seed
```

5. Inicie o servidor
```bash
php artisan serve
```

## 🔐 Credenciais Padrão

**Super Administrador**
- CPF: `00000000000`
- Senha: `admin123`

**Administrador**
- CPF: `11111111111`
- Senha: `admin123`

## 📁 Estrutura do Projeto

```
plataforma_dss/
├── app/
│   ├── Models/
│   ├── Http/
│   │   ├── Controllers/
│   │   └── Middleware/
│   └── Policies/
├── resources/
│   ├── views/
│   ├── css/
│   └── js/
├── routes/
├── database/
│   ├── migrations/
│   └── seeders/
├── public/
└── storage/
```

## 🚀 Deploy no ValueHost

1. Envie os arquivos via FTP
2. Configure o banco de dados PostgreSQL
3. Execute as migrations
4. Defina permissões nas pastas `storage/` e `bootstrap/cache`

## 📞 Suporte

Para dúvidas ou problemas, entre em contato.
