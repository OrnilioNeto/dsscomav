# Módulo de Rede Social - Plataforma DSS

Este documento explica as funcionalidades implementadas no novo módulo de **Rede Social**, como configurá-lo, como utilizá-lo no dia a dia e quais foram as alterações técnicas realizadas na plataforma.

---

## 🌟 O que foi feito?

Foi criada uma rede social interna integrada na Plataforma DSS, inspirada na dinâmica do Instagram, permitindo interações entre os colaboradores para incentivar a cultura de saúde, segurança e engajamento.

### Funcionalidades do Módulo:
1. **Feed de Publicações**:
   - **Meu Feed**: Exibe as publicações dos colaboradores que o usuário segue, além de suas próprias postagens.
   - **Explorar**: Mostra todas as publicações da plataforma cronologicamente, facilitando a descoberta de novos contatos.
2. **Criação de Posts**:
   - Postagens com foto (upload de imagem), legenda e marcação opcional de localização (ex: "Base Operacional X", "Rodovia SP-300").
   - Otimização automática de tamanho e peso de imagem no servidor utilizando a biblioteca GD do PHP (com fallback para preservação do arquivo original).
3. **Curtidas e Comentários**:
   - Curtidas interativas que atualizam o contador na hora via AJAX (sem recarregar a página).
   - Seção de comentários expansível em cada postagem, permitindo o envio de novas mensagens em tempo real via AJAX.
4. **Sistema de Seguidores (Follow/Unfollow)**:
   - Os colaboradores podem seguir uns aos outros.
   - Caixa lateral no feed contendo sugestões de contatos para seguir.
   - Página de perfil individual exibindo contadores de publicações, seguidores e contatos que o colaborador segue.
5. **Compartilhamento de Treinamentos Concluídos**:
   - Ao concluir a avaliação de um treinamento com sucesso no player de vídeo, o sistema exibe um box especial perguntando se o usuário deseja compartilhar a conquista.
   - Ao clicar, o colaborador é redirecionado ao feed e um modal é aberto com um card de visualização estilizado contendo o título do treinamento concluído e a **posição atual dele no Ranking de Engajamento**.
   - No feed, o post é renderizado dinamicamente como um **card de conquista premium com bordas e ícone de troféu**, emitido de forma legítima pelo sistema.

---

## 🔑 Como acessar e configurar?

### 1. Habilitando a Permissão (Administrador)
Por padrão de segurança do sistema de controle de acessos (RBAC) da plataforma:
1. Faça login com uma conta com perfil **Super Admin**.
2. Acesse a tela de **Permissões** (Controle de Acesso) na barra de navegação superior (`/admin/permissoes`).
3. Localize o módulo **Rede Social (Feed, Postagens, Seguidores)** na matriz de acessos.
4. Marque a caixa de seleção **Visualizar** e **Editar** para o perfil de usuário desejado (ex: `usuario` ou perfis operacionais personalizados).
5. Clique em **Salvar Alterações**.

> [!NOTE]
> Se a permissão não for concedida a um perfil, o link "Feed Social" não aparecerá no menu e qualquer tentativa de acesso direto à rota resultará em um erro `403 Acesso Negado`.

---

### 2. Acessando como Colaborador
Uma vez liberada a permissão pelo administrador, o menu **Feed Social** com o ícone de hashtag (`#`) ficará visível na barra de navegação:
- **No Desktop**: Localizado no menu superior principal.
- **No Mobile**: Localizado no menu hambúrguer suspenso.
- **Rota Direta**: Pode ser acessado diretamente através da URL `/social/feed`.

---

## 🛠️ Detalhes Técnicos (Arquivos Modificados & Criados)

### Tabelas Criadas (Migrations)
*   `database/migrations/2026_07_09_000001_create_social_tables.php`: Define as tabelas de posts, likes, comentários e seguidor-seguido.
*   `database/migrations/2026_07_09_000002_add_social_to_role_permissions.php`: Cria e inicializa os registros de permissões para as roles do banco.

### Modelos Eloquent
*   `app/Models/SocialPost.php`: Representação do Post com lógica de imagem e conquistas.
*   `app/Models/SocialLike.php`: Representação das curtidas.
*   `app/Models/SocialComment.php`: Representação dos comentários.
*   `app/Models/SocialFollow.php`: Representação dos relacionamentos de seguidores.
*   `app/Models/User.php` *(Modificado)*: Adicionados os relacionamentos Eloquent e contadores auxiliares de rede social.

### Rotas e Controladores
*   `routes/web.php` *(Modificado)*: Registro das rotas da rede social sob o middleware `permission:social`.
*   `app/Http/Controllers/SocialController.php`: Lógica de uploads de fotos, curtidas AJAX, seguir/deseguir, listagem de feed filtrada, inserção de comentários e carregamento do card de conquistas com dados de ranking.
*   `app/Http/Controllers/PermissionController.php` *(Modificado)*: Registro da constante do módulo `social` na matriz de permissões do painel.

### Telas (Views)
*   `resources/views/social/feed.blade.php`: O Feed Principal com o formulário de post, sugestões e abas.
*   `resources/views/social/profile.blade.php`: Visualização de perfil de outros colaboradores.
*   `resources/views/treinamentos/player.blade.php` *(Modificado)*: Adicionado o widget de parabéns e compartilhamento de conquista para o feed na conclusão do treinamento.
*   `resources/views/layout.blade.php` *(Modificado)*: Links para a rede social adicionados ao cabeçalho.

---

## 🔄 Auto-Migrations Automáticas (Novidade no Core)

Para garantir que o banco de dados esteja sempre sincronizado ao subir novas atualizações para o servidor de produção, implementamos um sistema de **Auto-Migrations no ciclo de boot do framework** (`AppServiceProvider.php`):

### Como funciona:
1. **Verificação de Mudança**: O sistema calcula um hash MD5 rápido baseado nos nomes e datas de modificação de todos os arquivos dentro do diretório `database/migrations/`.
2. **Otimização via Cache**: Esse hash é verificado contra o valor armazenado no cache. Se o hash for igual, a checagem é pulada em menos de **1ms**, garantindo overhead zero.
3. **Execução Automática**: Se novos arquivos forem detectados (ou algum arquivo de migration for editado), o sistema chama programaticamente o comando `php artisan migrate --force` em background.
4. **Sincronização Segura**: O histórico de migrations anteriores foi sincronizado com as tabelas pré-existentes de ranking/fotos de perfil no banco para garantir que o comando execute limpo sem conflitos.

