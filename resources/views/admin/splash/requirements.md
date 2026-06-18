# Requirements Document

## Introduction

A Plataforma DSS exibe atualmente uma mensagem de boas-vindas (splash screen) com conteúdo fixo no código após o login. O objetivo desta funcionalidade é tornar esses conteúdos de splash completamente administráveis pelo super admin: criar, editar, ordenar, ativar/inativar e excluir conteúdos, definindo título, material (imagem/PDF), texto, período de exibição e status. Ao logar, o usuário verá os conteúdos ativos e dentro do período configurado, exibidos em sequência (um após o outro na ordem definida). Esta especificação cobre a **fase 1**: o CRUD completo de conteúdos no painel do super admin.

---

## Glossary

- **SplashContent**: Registro de conteúdo de boas-vindas/splash configurável pelo super admin. Contém título, material, texto, período de exibição, status e posição de ordenação.
- **SplashContentManager**: Módulo do painel do super admin responsável por criar, listar, editar, reordenar e excluir SplashContents.
- **Super_Admin**: Usuário autenticado com role `super_admin` na plataforma DSS.
- **Material**: Arquivo de mídia (imagem ou PDF) associado a um SplashContent. Armazenado em disco e referenciado por caminho.
- **Período_de_Exibição**: Intervalo de datas (`data_inicio` e `data_fim`) que define quando um SplashContent é elegível para exibição.
- **Status**: Campo que define se um SplashContent está `ativo` ou `inativo`. Conteúdos inativos nunca são exibidos, independente do período.
- **Sequência**: Ordenação dos SplashContents definida pelo campo `ordem` (inteiro). Controla a ordem de apresentação ao usuário após o login.
- **Splash_Session_Flag**: Flag de sessão (`show_splash_contents`) definida no login, que controla se os conteúdos de splash devem ser exibidos na sessão atual.

---

## Requirements

### Requirement 1: Listagem de Conteúdos de Splash

**User Story:** Como Super_Admin, quero visualizar todos os conteúdos de splash cadastrados em uma listagem, para que eu possa ter visão geral e gerenciar cada um deles.

#### Acceptance Criteria

1. THE SplashContentManager SHALL exibir uma página de listagem acessível somente a usuários autenticados com role `super_admin`.
2. THE SplashContentManager SHALL listar todos os SplashContents cadastrados ordenados pelo campo `ordem` de forma crescente.
3. WHEN a listagem é exibida, THE SplashContentManager SHALL mostrar para cada SplashContent: título, status (ativo/inativo), `data_inicio`, `data_fim`, posição de ordem, e os botões de ações disponíveis.
4. WHEN não existir nenhum SplashContent cadastrado, THE SplashContentManager SHALL exibir uma mensagem informando que não há conteúdos cadastrados e um botão para criar o primeiro.
5. THE SplashContentManager SHALL exibir um botão de acesso à tela de criação de novo SplashContent.

---

### Requirement 2: Criação de Conteúdo de Splash

**User Story:** Como Super_Admin, quero criar um novo conteúdo de splash, para que eu possa exibir mensagens, imagens ou PDFs aos usuários após o login.

#### Acceptance Criteria

1. THE SplashContentManager SHALL disponibilizar um formulário de criação com os campos: `titulo` (obrigatório), `texto_conteudo` (opcional), upload de `material` (opcional, aceita imagens e PDFs), `data_inicio` (obrigatório), `data_fim` (obrigatório), e `status` (obrigatório, padrão `ativo`).
2. WHEN o Super_Admin submeter o formulário de criação, THE SplashContentManager SHALL validar que `titulo` não está vazio e tem no máximo 255 caracteres.
3. WHEN o Super_Admin submeter o formulário de criação, THE SplashContentManager SHALL validar que `data_fim` é igual ou posterior a `data_inicio`.
4. WHEN o Super_Admin fizer upload de um `material`, THE SplashContentManager SHALL validar que o arquivo é do tipo imagem (jpeg, png, gif, webp) ou PDF, com tamanho máximo de 10 MB.
5. WHEN o formulário de criação for submetido com dados válidos, THE SplashContentManager SHALL persistir o SplashContent no banco de dados e atribuir automaticamente o valor de `ordem` como o maior valor existente mais 1.
6. WHEN o formulário de criação for submetido com dados válidos e um arquivo de `material` for enviado, THE SplashContentManager SHALL armazenar o arquivo no diretório `uploads/splash/` e salvar o caminho relativo no campo `material_path`.
7. IF a validação do formulário de criação falhar, THEN THE SplashContentManager SHALL retornar ao formulário exibindo as mensagens de erro correspondentes e preservando os valores preenchidos.
8. WHEN o SplashContent for criado com sucesso, THE SplashContentManager SHALL redirecionar para a listagem exibindo uma mensagem de sucesso.

---

### Requirement 3: Edição de Conteúdo de Splash

**User Story:** Como Super_Admin, quero editar um conteúdo de splash existente, para que eu possa atualizar título, texto, material, período ou status quando necessário.

#### Acceptance Criteria

1. THE SplashContentManager SHALL disponibilizar um formulário de edição pré-preenchido com os dados atuais do SplashContent selecionado.
2. WHEN o Super_Admin submeter o formulário de edição, THE SplashContentManager SHALL aplicar as mesmas regras de validação definidas nos critérios 2, 3 e 4 do Requirement 2.
3. WHEN o Super_Admin fizer upload de um novo `material` na edição, THE SplashContentManager SHALL substituir o arquivo anterior armazenando o novo em `uploads/splash/` e atualizando o `material_path`.
4. WHEN o Super_Admin submeter o formulário de edição sem enviar um novo arquivo de `material`, THE SplashContentManager SHALL manter o `material_path` atual sem alteração.
5. WHEN o formulário de edição for submetido com dados válidos, THE SplashContentManager SHALL atualizar o SplashContent e redirecionar para a listagem exibindo uma mensagem de sucesso.
6. IF a validação do formulário de edição falhar, THEN THE SplashContentManager SHALL retornar ao formulário exibindo as mensagens de erro e preservando os valores submetidos.
7. IF o SplashContent solicitado para edição não existir, THEN THE SplashContentManager SHALL retornar HTTP 404.

---

### Requirement 4: Exclusão de Conteúdo de Splash

**User Story:** Como Super_Admin, quero excluir um conteúdo de splash que não será mais utilizado, para que a base de dados fique organizada.

#### Acceptance Criteria

1. THE SplashContentManager SHALL disponibilizar um botão de exclusão para cada SplashContent na listagem.
2. WHEN o Super_Admin confirmar a exclusão de um SplashContent, THE SplashContentManager SHALL remover o registro do banco de dados.
3. WHEN um SplashContent com `material_path` preenchido for excluído, THE SplashContentManager SHALL remover o arquivo físico correspondente do diretório `uploads/splash/`.
4. WHEN a exclusão for concluída com sucesso, THE SplashContentManager SHALL redirecionar para a listagem exibindo uma mensagem de sucesso.
5. IF o SplashContent solicitado para exclusão não existir, THEN THE SplashContentManager SHALL retornar HTTP 404.

---

### Requirement 5: Ativação e Inativação de Conteúdo de Splash

**User Story:** Como Super_Admin, quero ativar ou inativar um conteúdo de splash sem precisar excluí-lo, para que eu possa controlar a exibição de forma rápida.

#### Acceptance Criteria

1. THE SplashContentManager SHALL disponibilizar um botão de "Inativar" para cada SplashContent com `status = ativo` na listagem.
2. THE SplashContentManager SHALL disponibilizar um botão de "Reativar" para cada SplashContent com `status = inativo` na listagem.
3. WHEN o Super_Admin acionar o botão de inativar, THE SplashContentManager SHALL alterar o `status` do SplashContent para `inativo` e redirecionar para a listagem com mensagem de sucesso.
4. WHEN o Super_Admin acionar o botão de reativar, THE SplashContentManager SHALL alterar o `status` do SplashContent para `ativo` e redirecionar para a listagem com mensagem de sucesso.
5. IF o SplashContent solicitado para inativar ou reativar não existir, THEN THE SplashContentManager SHALL retornar HTTP 404.

---

### Requirement 6: Reordenação dos Conteúdos de Splash

**User Story:** Como Super_Admin, quero definir a ordem em que os conteúdos de splash são exibidos aos usuários, para que eu possa controlar a sequência de apresentação.

#### Acceptance Criteria

1. THE SplashContentManager SHALL exibir na listagem a posição de `ordem` atual de cada SplashContent.
2. THE SplashContentManager SHALL disponibilizar botões de "Mover para cima" e "Mover para baixo" para alterar a `ordem` de um SplashContent em relação ao elemento adjacente.
3. WHEN o Super_Admin acionar "Mover para cima" em um SplashContent, THE SplashContentManager SHALL trocar o valor de `ordem` deste SplashContent com o do SplashContent imediatamente anterior na sequência.
4. WHEN o Super_Admin acionar "Mover para baixo" em um SplashContent, THE SplashContentManager SHALL trocar o valor de `ordem` deste SplashContent com o do SplashContent imediatamente posterior na sequência.
5. WHILE um SplashContent estiver na primeira posição da sequência, THE SplashContentManager SHALL desabilitar o botão "Mover para cima" para esse registro.
6. WHILE um SplashContent estiver na última posição da sequência, THE SplashContentManager SHALL desabilitar o botão "Mover para baixo" para esse registro.
7. IF o SplashContent solicitado para reordenação não existir, THEN THE SplashContentManager SHALL retornar HTTP 404.

---

### Requirement 7: Visualização de Material (Preview)

**User Story:** Como Super_Admin, quero visualizar o material associado a um conteúdo de splash diretamente no painel, para que eu possa conferir o que será exibido aos usuários.

#### Acceptance Criteria

1. WHEN o SplashContent possuir `material_path` preenchido e o arquivo for uma imagem, THE SplashContentManager SHALL exibir uma pré-visualização (thumbnail) da imagem na listagem.
2. WHEN o SplashContent possuir `material_path` preenchido e o arquivo for um PDF, THE SplashContentManager SHALL exibir um ícone indicativo de PDF com um link para abrir o arquivo em nova aba na listagem.
3. WHEN o SplashContent não possuir `material_path`, THE SplashContentManager SHALL exibir um indicador visual de "sem material" na listagem.

---

### Requirement 8: Controle de Acesso

**User Story:** Como Super_Admin, quero que o gerenciamento de conteúdos de splash seja restrito ao meu perfil, para que outros usuários não possam alterar as configurações de splash.

#### Acceptance Criteria

1. THE SplashContentManager SHALL proteger todas as rotas de gerenciamento de splash com o middleware `CheckRole` restrito ao role `super_admin`.
2. WHEN um usuário autenticado sem role `super_admin` tentar acessar qualquer rota do SplashContentManager, THE SplashContentManager SHALL retornar HTTP 403.
3. WHEN um usuário não autenticado tentar acessar qualquer rota do SplashContentManager, THE SplashContentManager SHALL redirecionar para a rota `login`.

---

### Requirement 9: Estrutura de Dados do SplashContent

**User Story:** Como desenvolvedor, quero que o modelo SplashContent tenha uma estrutura de dados consistente, para que as operações de persistência e exibição funcionem corretamente.

#### Acceptance Criteria

1. THE SplashContent SHALL possuir os campos: `id`, `titulo` (string, obrigatório, máx. 255), `texto_conteudo` (text, nullable), `material_path` (string, nullable), `material_tipo` (enum: `imagem`, `pdf`, nullable), `data_inicio` (date, obrigatório), `data_fim` (date, obrigatório), `status` (enum: `ativo`, `inativo`, obrigatório, padrão `ativo`), `ordem` (integer, obrigatório), `created_at`, `updated_at`.
2. THE SplashContent SHALL garantir que `data_fim >= data_inicio` a nível de validação de aplicação.
3. THE SplashContent SHALL manter o campo `ordem` como valor único entre os registros, sem repetição.
