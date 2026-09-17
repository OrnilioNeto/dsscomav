# 🚀 Gerenciador de Conteúdos Splash (Boas-vindas)

## Visão Geral
Este módulo permite que o Super Admin configure mensagens e materiais que aparecem automaticamente para o usuário logo após o login. Os conteúdos podem ser imagens, PDFs ou textos, exibidos em sequência baseada em uma ordem definida.

## Regras de Negócio
1. **Período de Exibição:** O conteúdo só é exibido se a data atual estiver entre `data_inicio` e `data_fim`.
2. **Status:** Apenas conteúdos com status `ativo` são elegíveis para exibição.
3. **Sequenciamento:** Se houver múltiplos conteúdos ativos no período, eles serão exibidos um após o outro seguindo o campo `ordem`.
4. **Persistência de Sessão:** O sistema deve garantir que o usuário veja o splash apenas uma vez por login (ou conforme regra de sessão a ser implementada na Parte 2).

## Campos do Cadastro
- **Título:** Nome interno para identificação.
- **Material:** Upload de Imagem (JPG/PNG) ou PDF.
- **Texto:** Conteúdo textual opcional.
- **Data Início/Fim:** Período de validade da exibição.
- **Status:** Ativo/Inativo.
- **Ordem:** Posição na sequência de exibição.

## Ações Disponíveis (Super Admin)
- **Criar:** Novo conteúdo com upload de arquivo.
- **Editar:** Alterar dados ou substituir o arquivo.
- **Excluir:** Remove o registro e o arquivo físico.
- **Ativar/Inativar:** Alternar status rapidamente.
- **Ordenar:** Mover para cima/baixo na lista de prioridade.

## Estrutura Técnica
- **Model:** `App\Models\SplashContent`
- **Controller:** `App\Http\Controllers\Admin\SplashContentController`
- **Tabela:** `splash_contents`
- **Storage:** `public/uploads/splash/`

---
*Nota: Esta documentação refere-se à Fase 1 (Configuração). A exibição no Dashboard será tratada na Fase 2.*