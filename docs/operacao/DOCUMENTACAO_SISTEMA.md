# Documentação do Sistema - Plataforma DSS

## 1. Visão geral

A Plataforma DSS é um sistema web em Laravel para gestão de treinamentos corporativos e DSS (Diálogo Semanal de Segurança). O sistema cobre todo o ciclo de vida do conteúdo: cadastro de usuários, cadastro de treinamentos, liberação por perfil, exibição do vídeo, acompanhamento de progresso, avaliação, emissão de certificados com QR Code, validação pública do certificado e relatórios gerenciais.

O projeto foi estruturado para atender três perfis principais:

- **Super Admin**: acesso total ao sistema, com visão completa de usuários, treinamentos, certificados e relatórios.
- **Admin**: acesso administrativo, com gerenciamento de cadastros e também participação em treinamentos quando a flag `participa_treinamentos` estiver habilitada.
- **Usuário comum**: motorista, funcionário ou terceirizado, com acesso apenas aos treinamentos permitidos para o seu tipo.

## 2. Stack tecnológica

- **Backend**: Laravel 10
- **Frontend**: Blade + Tailwind CSS + Font Awesome
- **Banco de dados**: SQLite no ambiente atual de desenvolvimento; o README original menciona PostgreSQL, então o sistema pode ser adaptado para outro banco via `.env`
- **PDF**: TCPDF
- **Autenticação**: login por CPF com `Auth::attempt`
- **Containerização**: Docker / Docker Compose

## 3. Fluxo de funcionamento do sistema

### 3.1 Acesso inicial

O usuário entra pela página inicial (`welcome`). A partir dela pode acessar a página de login ou, se já autenticado, ir para o dashboard.

### 3.2 Autenticação

O login é feito com CPF sem máscara e senha.

Após autenticação, a sessão é regenerada e o usuário é redirecionado para o dashboard.

### 3.3 Direcionamento por perfil

O dashboard identifica o usuário pelo papel e pela função auxiliar do model `User`:

- `isSuperAdmin()`
- `isAdmin()`

Com isso o sistema apresenta dashboards distintos para super admin, admin e usuário comum.

### 3.4 Treinamentos

Os treinamentos possuem tipo, público permitido, vídeo, carga horária, avaliação e status. O usuário só vê o que estiver liberado pelo tipo de usuário e pelas regras do treinamento.

### 3.5 Progresso

Ao entrar em um treinamento, o sistema cria ou recupera um registro em `user_progress`. Esse registro armazena:

- início da assistência
- tempo assistido
- porcentagem assistida
- conclusão
- aprovação da avaliação
- tentativas de avaliação

### 3.6 Certificado

Quando o usuário conclui o conteúdo e é aprovado na avaliação, o sistema gera um certificado. O certificado contém:

- código único
- data de emissão
- início e fim da assistência
- tempo assistido
- porcentagem assistida
- status de validade

O certificado pode ser visualizado como PDF, baixado e validado publicamente por código.

## 4. Estrutura de pastas e arquivos

### 4.1 `app/`

Pasta principal da aplicação Laravel. Contém controllers, models, middleware e regras centrais do sistema.

#### 4.1.1 `app/Http/Controllers/`

- `AuthController.php`
  - Mostra a tela de login
  - Faz autenticação por CPF
  - Faz logout e invalida sessão

- `DashboardController.php`
  - Decide qual dashboard mostrar conforme perfil
  - Monta indicadores do super admin, admin e usuário comum
  - Calcula progresso, certificados e distribuição por tipo de usuário

- `TrainingController.php`
  - CRUD de treinamentos
  - Valida campos de criação e edição
  - Salva vídeo, avaliação, tipo de treinamento e tipos de usuário permitidos

- `TrainingPlayerController.php`
  - Abre a tela do treinamento
  - Atualiza progresso
  - Controla avaliação
  - Marca conclusão
  - Dispara emissão de certificado quando aplicável

- `CertificateController.php`
  - Gera certificado individual
  - Faz download do PDF do certificado
  - Valida certificado publicamente via código
  - Lista certificados do próprio usuário

- `CertificateManagementController.php`
  - Gerenciamento avançado de certificados para admin e super admin
  - Relatórios gerenciais de treinamentos, usuários e auditoria
  - Exportação CSV
  - Filtros por usuário, tipo de usuário, treinamento, tipo de treinamento, validade e datas

- `UserController.php`
  - CRUD de usuários
  - Valida CPF, email, senha e tipo de usuário
  - Permite salvar empresa, cargo e participação em treinamentos para admins

#### 4.1.2 `app/Http/Middleware/`

- `CheckRole.php`
  - Garante que apenas usuários com os papéis informados acessem certas rotas
  - Redireciona para login se não houver autenticação
  - Retorna 403 se o papel não for permitido

#### 4.1.3 `app/Models/`

- `User.php`
  - Modelo principal de usuários
  - Guarda nome, CPF, email, senha, tipo de usuário, role, empresa, cargo, responsável e outros campos auxiliares
  - Contém métodos de negócio:
    - `isSuperAdmin()`
    - `isAdmin()`
    - `canAccessTraining()`
    - `getCpfFormatted()`

- `Role.php`
  - Representa os papéis do sistema
  - Relaciona um papel com vários usuários

- `Training.php`
  - Representa um treinamento ou DSS
  - Guarda título, descrição, tipo, vídeo, carga horária, avaliação e usuários permitidos
  - Possui helpers para embed de vídeo, verificação de acesso e taxa de conclusão

- `UserProgress.php`
  - Guarda o progresso do usuário por treinamento
  - Relaciona usuário e treinamento

- `Certificate.php`
  - Representa o certificado emitido
  - Relaciona usuário e treinamento
  - Gera URL de validação e QR Code

#### 4.1.4 `app/Policies/`

No estado atual do projeto, essa pasta existe, mas o controle de acesso principal está concentrado no middleware, nos métodos auxiliares do model `User` e nas checagens dentro dos controllers.

### 4.2 `database/`

#### 4.2.1 `database/migrations/`

- `2014_10_12_000000_create_users_table.php`
  - Cria a tabela `users`
  - Campos principais: nome, CPF, email, senha, telefone, data de nascimento, tipo de usuário, status, role, CNH, setor, cargo, empresa e responsável

- `2024_01_01_000001_create_roles_table.php`
  - Cria a tabela de papéis do sistema

- `2024_01_01_000003_create_trainings_table.php`
  - Cria a tabela de treinamentos

- `2024_01_01_000004_create_user_progress_table.php`
  - Cria a tabela de progresso do usuário

- `2024_01_01_000005_create_certificates_table.php`
  - Cria a tabela de certificados

- `2026_04_29_000006_add_audit_fields_to_progress_and_certificates.php`
  - Adiciona campos de auditoria nas tabelas de progresso e certificados

- `2026_04_29_000007_add_assessment_attempts_to_user_progress.php`
  - Adiciona controle de tentativas de avaliação no progresso

- `2026_05_01_000008_add_participa_treinamentos_to_users.php`
  - Adiciona a flag `participa_treinamentos` em usuários admin

#### 4.2.2 `database/seeders/`

- `DatabaseSeeder.php`
  - Orquestra a carga inicial dos seeders

- `RoleSeeder.php`
  - Cria os papéis base do sistema

- `UserSeeder.php`
  - Cria usuários padrão para teste e inicialização

- `TrainingSeeder.php`
  - Cria treinamentos de exemplo e conteúdos DSS

### 4.3 `resources/views/`

#### 4.3.1 Layout base

- `layout.blade.php`
  - Template global da aplicação quando o usuário está autenticado
  - Contém navbar, área de mensagens, conteúdo principal e footer
  - Também concentra as variáveis visuais da marca (`--primary`, `--accent`)

#### 4.3.2 Autenticação

- `auth/login.blade.php`
  - Tela de login por CPF e senha
  - Exibe logo, identidade visual da empresa e credenciais de teste

#### 4.3.3 Página inicial

- `welcome.blade.php`
  - Landing page pública do sistema
  - Mostra a marca, slogan, botão de login e cards de funcionalidades

#### 4.3.4 Dashboard

- `dashboard/super_admin.blade.php`
  - Visão completa do sistema para super admin

- `dashboard/admin.blade.php`
  - Painel do admin
  - Inclui área de gerenciamento e, se habilitado, área de participação em treinamentos

- `dashboard/usuario.blade.php`
  - Painel do usuário comum
  - Exibe treinamentos disponíveis, concluídos, pendentes e não iniciados

#### 4.3.5 Usuários

- `usuarios/index.blade.php`
  - Lista de usuários

- `usuarios/create.blade.php`
  - Formulário de criação de usuário

- `usuarios/edit.blade.php`
  - Edição de usuário, empresa, cargo, tipo de usuário e participação em treinamentos

- `usuarios/show.blade.php`
  - Exibição detalhada do usuário

#### 4.3.6 Treinamentos

- `treinamentos/index.blade.php`
  - Listagem de treinamentos

- `treinamentos/create.blade.php`
  - Cadastro de treinamento

- `treinamentos/edit.blade.php`
  - Edição de treinamento

- `treinamentos/show.blade.php`
  - Visualização de um treinamento específico

- `treinamentos/player.blade.php`
  - Player do treinamento para o usuário

- `treinamentos/player_fixed.blade.php`
  - Variação fixa do player, mantida como alternativa visual/técnica

#### 4.3.7 Certificados

- `certificados/meus_certificados.blade.php`
  - Lista de certificados do usuário logado

- `certificados/download.blade.php`
  - Página HTML estilizada para download/impressão do certificado

- `certificados/pdf.blade.php`
  - Template HTML convertido em PDF pelo TCPDF

- `certificados/pdf_new.blade.php`
  - Outra versão do layout de certificado em PDF, mais visual e detalhada

- `certificados/validacao.blade.php`
  - Tela pública de validação por código

#### 4.3.8 Relatórios / Gerência

- `certificados/gerencial.blade.php`
  - Tela de gerenciamento de certificados com filtros avançados

- `relatorios/treinamentos.blade.php`
  - Relatório de progresso por treinamento

- `relatorios/usuarios.blade.php`
  - Relatório de usuários e histórico

- `relatorios/auditoria.blade.php`
  - Painel de auditoria com indicadores e agregações

#### 4.3.9 Outros arquivos de apoio

- `debug.blade.php`
  - Página auxiliar para diagnóstico

- `teste-video.blade.php`
  - Página simples para testar player/vídeo

### 4.4 `routes/`

- `web.php`
  - Define toda a navegação HTTP do sistema
  - Separado em rotas públicas, rotas autenticadas e rotas com autorização por papel

### 4.5 `public/`

- `public/images/`
  - Imagens públicas da aplicação, incluindo a logo da empresa

- `public/uploads/`
  - Área de arquivos enviados pelo sistema ou pelo usuário, quando aplicável

### 4.6 `storage/`

- `storage/certificates/`
  - Pasta de armazenamento para artefatos de certificados, quando usados

## 5. Banco de dados e relacionamento entre entidades

### 5.1 `users`

Campos principais:

- `nome`
- `cpf`
- `email`
- `password`
- `telefone`
- `data_nascimento`
- `tipo_usuario`
- `status`
- `role_id`
- `cnh`
- `categoria_cnh`
- `validade_cnh`
- `setor`
- `cargo`
- `empresa`
- `responsavel`
- `participa_treinamentos`

### 5.2 `roles`

Papéis do sistema. O projeto trabalha com pelo menos:

- `super_admin`
- `admin`

### 5.3 `trainings`

Campos principais:

- `titulo`
- `descricao`
- `tipo` (`dss` ou `treinamento`)
- `tipo_usuario_permitido` (JSON)
- `url_video`
- `tipo_video` (`youtube`, `vimeo`, `upload`)
- `carga_horaria`
- `thumbnail`
- `data_publicacao`
- `status`
- campos de avaliação

### 5.4 `user_progress`

Relaciona usuário e treinamento com o estado do progresso.

### 5.5 `certificates`

Relaciona usuário e treinamento com o certificado emitido.

## 6. Regras de negócio principais

### 6.1 Permissão por perfil

O acesso a áreas administrativas é controlado por middleware `CheckRole`, funções auxiliares do model `User` e checagens dentro dos controllers.

### 6.2 Permissão por tipo de usuário

Cada treinamento pode restringir quais tipos de usuário podem assisti-lo.

### 6.3 Participação de admin em treinamentos

Admins podem também participar como alunos quando `participa_treinamentos` estiver marcado.

### 6.4 Emissão de certificado

O certificado só é emitido quando:

- o progresso foi concluído
- a avaliação foi aprovada

### 6.5 Validação pública

Qualquer pessoa pode validar um certificado por código via rota pública.

### 6.6 Progresso do vídeo

O sistema grava tempo assistido e porcentagem assistida, com controle para evitar avanço artificial excessivo em uma única requisição.

## 7. Painéis e relatórios

### 7.1 Super Admin

Tem acesso completo a:

- usuários
- treinamentos
- certificados
- relatórios gerenciais
- auditoria

### 7.2 Admin

Tem acesso a:

- cadastro e manutenção de usuários
- manutenção de treinamentos
- gerenciamento de certificados
- relatórios

Quando `participa_treinamentos` está ativo, também entra na lógica de aluno.

### 7.3 Usuário comum

Tem acesso a:

- treinamentos liberados
- progresso
- certificados próprios

## 8. Certificados

O certificado possui três formas de consumo:

1. **PDF para download**
   - gerado pelo `CertificateController`
   - usa template `certificados/pdf.blade.php`

2. **Tela de impressão/visualização**
   - layouts HTML em `download.blade.php` e `pdf_new.blade.php`

3. **Validação pública**
   - rota `/validar/{codigo}`
   - mostra status, dados do usuário, treinamento e QR Code

## 9. Arquivos de ambiente e infra

### 9.1 `Dockerfile`

Define a imagem da aplicação e dependências do container.

### 9.2 `docker-compose.yml`

Sobe o serviço principal da aplicação e mantém volumes para banco, storage e imagens públicas.

### 9.3 `docker/entrypoint.sh`

Script de inicialização do container. Ele prepara `.env`, storage, banco, migrations, seeders e também garante a pasta de imagens.

## 10. Identidade visual

O sistema usa a identidade visual da COMAV Transportes.

As cores principais aplicadas no layout e na home são:

- Verde escuro: `#153B2E`
- Verde mais escuro: `#0F2B22`
- Laranja de destaque: `#F28C2B`

Essas cores aparecem em:

- barra superior
- footer
- botões de chamada
- cards da home
- páginas de login
- páginas e PDFs de certificado

## 11. Pontos importantes para manutenção

### 11.1 Alterar regra de acesso a treinamentos

O ponto principal é `User::canAccessTraining()` e o campo `tipo_usuario_permitido` no modelo de treinamento.

### 11.2 Ajustar o que um perfil vê no dashboard

Use `DashboardController`.

### 11.3 Mudar geração de certificado

Use `CertificateController` e os templates em `resources/views/certificados/`.

### 11.4 Mudar aparência geral

Use `resources/views/layout.blade.php` e `resources/views/welcome.blade.php`.

### 11.5 Corrigir filtros e relatórios

Use `CertificateManagementController` e as views em `resources/views/relatorios/`.

### 11.6 Adicionar novos papéis

Atualize seeders de roles, `CheckRole`, validações dos controllers, dashboard e permissões.

## 12. Pontos sensíveis já tratados no projeto

- O sistema já teve problemas com `MONTH()`/`YEAR()` em SQLite e isso foi ajustado com `strftime()`.
- O sistema também já passou por erro de Blade com seção mal fechada; hoje os templates foram reorganizados para evitar ambiguidades.
- A logo precisa estar disponível em `public/images/logo-comav-transportes.png` para aparecer corretamente em ambiente local e no container.

## 13. Como localizar rapidamente o que cada parte faz

- **Login**: `app/Http/Controllers/AuthController.php` + `resources/views/auth/login.blade.php`
- **Dashboard**: `app/Http/Controllers/DashboardController.php`
- **Usuários**: `app/Http/Controllers/UserController.php` + `resources/views/usuarios/`
- **Treinamentos**: `app/Http/Controllers/TrainingController.php` + `resources/views/treinamentos/`
- **Player**: `app/Http/Controllers/TrainingPlayerController.php` + `resources/views/treinamentos/player.blade.php`
- **Certificados**: `app/Http/Controllers/CertificateController.php` + `resources/views/certificados/`
- **Relatórios**: `app/Http/Controllers/CertificateManagementController.php` + `resources/views/relatorios/`
- **Regras de autorização**: `app/Http/Middleware/CheckRole.php`
- **Modelos de negócio**: `app/Models/`
- **Estrutura do banco**: `database/migrations/`
- **Dados iniciais**: `database/seeders/`

## 14. Observações finais para manutenção futura

1. Antes de mudar algum fluxo, verifique se a alteração afeta também o dashboard correspondente.
2. Ao criar novos campos em usuário ou treinamento, ajuste junto:
   - migration
   - model `$fillable`
   - validação no controller
   - formulário Blade
   - exibição em tela e PDF, se aplicável
3. Após alterar Blade templates, limpe e recacheie as views.
4. Se o container for recriado, confirme se a pasta `public/images` continua disponível e contendo a logo.
5. Se um relatório não exibir dados esperados, verifique primeiro os filtros em `CertificateManagementController`.
