# 🎯 Guia Rápido - Iniciar em 5 Minutos (Windows)

## ⚡ Instalação Rápida

### 1️⃣ Pré-requisitos
- Windows 10/11
- PHP 8.1+
- Composer
- PostgreSQL ou MySQL (opcional - SQLite funciona para testes)

### 2️⃣ Execute o Setup (MAIS FÁCIL)

```batch
# No Prompt de Comando ou PowerShell, vá até a pasta do projeto
cd plataforma_dss

# Execute o arquivo setup.bat
setup.bat
```

Pronto! Ele fará tudo automaticamente.

---

## 📝 Instalação Manual (Passo a Passo)

Se o `setup.bat` não funcionar:

### 1. Instale Dependências
```batch
composer install
```

### 2. Configure o .env
```batch
copy .env.example .env
```

Abra o arquivo `.env` e configure:
```
DB_CONNECTION=sqlite  (ou pgsql/mysql)
```

### 3. Gere a Chave
```batch
php artisan key:generate
```

### 4. Crie o Banco de Dados
```batch
php artisan migrate --seed
```

### 5. Inicie o Servidor
```batch
php artisan serve
```

Acesse: **http://localhost:8000**

---

## 🔐 Faça Login!

### Super Administrador
- **CPF:** 00000000000
- **Senha:** admin123

### Motorista (Teste)
- **CPF:** 22222222222
- **Senha:** senha123

---

## 📡 Estrutura do Projeto

```
✓ Backend: Laravel 10 (PHP)
✓ Frontend: Tailwind CSS + Blade
✓ Banco: PostgreSQL/MySQL/SQLite
✓ Certificados: PDF com QR Code
✓ Responsivo: Desktop + Mobile
```

---

## 🎨 Principais Features

1. **Autenticação por CPF** ✓
2. **3 Tipos de Usuário** (Motorista, Funcionário, Terceirizado) ✓
3. **Player de Vídeos** (YouTube/Vimeo/Upload) ✓
4. **Certificados Automáticos** (PDF + QR Code) ✓
5. **Dashboard Inteligente** (Por tipo de usuário) ✓
6. **Relatórios** (Progresso, Taxa de Conclusão) ✓

---

## 🐛 Problemas Comuns?

### Erro: "composer not found"
👉 Instale Composer: https://getcomposer.org/download/

### Erro: "Connection refused"
👉 Configure `DB_CONNECTION=sqlite` no `.env` para testes

### Página em branco
👉 Execute: `php artisan cache:clear`

### Permissão negada em storage/
👉 Crie manualmente as pastas:
```batch
mkdir storage\certificates
mkdir public\uploads
```

---

## 🚀 Próximas Operações

Após instalar, você pode:

1. **Criar Usuários**
   - Acesse: Admin → Usuários → Novo Usuário

2. **Criar Treinamentos**
   - Acesse: Admin → Treinamentos → Novo Treinamento
   - Cole um link do YouTube

3. **Atribuir Treinamento a Motoristas**
   - Selecione "Motorista" ao criar

4. **Testar com Usuário**
   - Faça logout
   - Login com CPF: 22222222222
   - Veja os treinamentos disponíveis

---

## 📧 Suporte

Documentação completa em:
- `README.md` - Visão geral
- `INSTALACAO.md` - Detalhes completos
- `CHECKLIST.md` - Funcionalidades

---

## 💡 Dica Pro

Quer adicionar um novo treinamento manualmente?

```bash
php artisan tinker

# Cole isto:
App\Models\Training::create([
    'titulo' => 'Meu Novo Treinamento',
    'descricao' => 'Descrição aqui',
    'tipo' => 'dss',
    'tipo_usuario_permitido' => ['motorista'],
    'url_video' => 'https://www.youtube.com/watch?v=VIDEO_ID',
    'tipo_video' => 'youtube',
    'carga_horaria' => 30,
    'status' => 'ativo',
]);
```

---

**Pronto? Clique em http://localhost:8000 e comece! 🎉**
